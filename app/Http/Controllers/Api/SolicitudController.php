<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CargarAdjuntoRequest;
use App\Http\Requests\CrearSolicitudRequest;
use App\Http\Requests\TransicionarSolicitudRequest;
use App\Http\Resources\DocumentoAdjuntoResource;
use App\Http\Resources\SolicitudResource;
use App\Models\DocumentoAdjunto;
use App\Models\Solicitud;
use App\Services\SolicitudService;
use Illuminate\Http\JsonResponse;

class SolicitudController extends Controller
{
    public function __construct(private readonly SolicitudService $solicitudes) {}

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
}
