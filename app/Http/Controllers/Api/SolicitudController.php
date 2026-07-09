<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\ReglaNegocioException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CargarAdjuntoRequest;
use App\Http\Requests\CrearSolicitudRequest;
use App\Http\Requests\TransicionarSolicitudRequest;
use App\Http\Requests\ValidarAdjuntoRequest;
use App\Http\Resources\DocumentoAdjuntoResource;
use App\Http\Resources\SolicitudResource;
use App\Models\DocumentoAdjunto;
use App\Models\Solicitud;
use App\Services\ElegibilidadService;
use App\Services\SolicitudService;
use App\States\Solicitud\EstadoSolicitud;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SolicitudController extends Controller
{
    public function __construct(private readonly SolicitudService $solicitudes) {}

    /** Lista solicitudes filtrando por persona, expediente y/o estado. */
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'persona_id' => ['nullable', 'uuid', 'exists:personas,id'],
            'expediente_id' => ['nullable', 'uuid', 'exists:expedientes,id'],
            'estado' => ['nullable', 'string', Rule::in(EstadoSolicitud::getStateMapping()->keys())],
        ]);

        $solicitudes = Solicitud::query()
            ->with(['servicio', 'expediente'])
            ->when($filtros['persona_id'] ?? null, fn ($q, $id) => $q->whereHas('expediente', fn ($e) => $e->where('persona_id', $id)))
            ->when($filtros['expediente_id'] ?? null, fn ($q, $id) => $q->where('expediente_id', $id))
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->where('estado_actual', $estado))
            ->orderByDesc('fecha_creacion')
            ->paginate(25)
            ->withQueryString();

        return SolicitudResource::collection($solicitudes);
    }

    /** Crea una solicitud (abriendo expediente si es necesario). */
    public function store(CrearSolicitudRequest $request): JsonResponse
    {
        $solicitud = $this->solicitudes->crear(
            $request->validated(),
            $request->input('sistema_origen', 'INTEGRACION'),
        );

        return SolicitudResource::make($solicitud->load(['servicio', 'expediente', 'estados']))
            ->response()
            ->setStatusCode(201);
    }

    /** Detalle de una solicitud con su historial, adjuntos y órdenes. */
    public function show(Solicitud $solicitud): SolicitudResource
    {
        return SolicitudResource::make(
            $solicitud->load(['servicio', 'expediente', 'estados', 'adjuntos', 'ordenesPago.pagos'])
        );
    }

    /** Ejecuta una transición de estado. */
    public function transicion(TransicionarSolicitudRequest $request, Solicitud $solicitud): SolicitudResource
    {
        $actualizada = $this->solicitudes->transicionar(
            $solicitud,
            $request->input('estado_destino'),
            $request->input('sistema_origen', 'INTEGRACION'),
            $request->input('usuario_id'),
            $request->input('motivo'),
        );

        return SolicitudResource::make($actualizada->load(['servicio', 'estados', 'ordenesPago']));
    }

    /** Carga un adjunto (RN-11: solo JPG). */
    public function adjuntos(CargarAdjuntoRequest $request, Solicitud $solicitud): JsonResponse
    {
        $ruta = $request->input('ruta');

        if ($request->hasFile('archivo')) {
            $ruta = $request->file('archivo')->store("adjuntos/{$solicitud->id}");
        }

        $adjunto = DocumentoAdjunto::create([
            'solicitud_id' => $solicitud->id,
            'tipo_documento' => $request->input('tipo_documento'),
            'formato' => strtoupper((string) $request->input('formato')),
            'ruta' => $ruta,
            'validado' => false,
        ]);

        return DocumentoAdjuntoResource::make($adjunto)->response()->setStatusCode(201);
    }

    /** Valida (o retira la validación de) un adjunto — base de RN-08. */
    public function validarAdjunto(ValidarAdjuntoRequest $request, Solicitud $solicitud, DocumentoAdjunto $adjunto): DocumentoAdjuntoResource
    {
        $validado = $request->boolean('validado');

        $adjunto->update([
            'validado' => $validado,
            'validado_por' => $validado ? $request->input('usuario_id') : null,
        ]);

        return DocumentoAdjuntoResource::make($adjunto);
    }

    /** Elimina un adjunto cargado por error. Un adjunto validado no se elimina. */
    public function eliminarAdjunto(Solicitud $solicitud, DocumentoAdjunto $adjunto): JsonResponse
    {
        if ($adjunto->validado) {
            throw new ReglaNegocioException('No se puede eliminar un adjunto ya validado; retira primero la validación.');
        }

        Storage::delete($adjunto->ruta);
        $adjunto->delete();

        return new JsonResponse(null, 204);
    }

    /** Evalúa las reglas de elegibilidad aplicables a la solicitud (RN-03/05/08/09/10). */
    public function elegibilidad(Solicitud $solicitud, ElegibilidadService $elegibilidad): JsonResponse
    {
        return new JsonResponse(['data' => $elegibilidad->evaluarSolicitud($solicitud)]);
    }
}
