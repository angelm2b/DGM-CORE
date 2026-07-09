<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmitirDocumentoRequest;
use App\Http\Resources\DocumentoEmitidoResource;
use App\Models\DocumentoEmitido;
use App\Models\Persona;
use App\Models\Solicitud;
use App\Services\DocumentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class DocumentoController extends Controller
{
    public function __construct(private readonly DocumentoService $documentos) {}

    /** Emite un documento para una solicitud. */
    public function emitir(EmitirDocumentoRequest $request): JsonResponse
    {
        $solicitud = Solicitud::findOrFail($request->input('solicitud_id'));

        $documento = $this->documentos->emitir(
            $solicitud,
            $request->input('tipo'),
            $request->filled('fecha_vencimiento') ? Carbon::parse($request->input('fecha_vencimiento')) : null,
            $request->filled('fecha_emision') ? Carbon::parse($request->input('fecha_emision')) : null,
        );

        return DocumentoEmitidoResource::make($documento)->response()->setStatusCode(201);
    }

    /** Revoca un documento vigente (queda REVOCADO y deja de verificar como válido). */
    public function revocar(DocumentoEmitido $documento): DocumentoEmitidoResource
    {
        return DocumentoEmitidoResource::make($this->documentos->revocar($documento));
    }

    /**
     * Repone un documento vigente (pérdida/robo/deterioro): el original queda
     * REPUESTO y se emite uno nuevo con número de serie propio.
     */
    public function reponer(DocumentoEmitido $documento): JsonResponse
    {
        $nuevo = $this->documentos->reponer($documento);

        return DocumentoEmitidoResource::make($nuevo)->response()->setStatusCode(201);
    }

    /** Documentos emitidos a una persona, del más reciente al más antiguo. */
    public function porPersona(Persona $persona)
    {
        $documentos = DocumentoEmitido::query()
            ->whereHas('solicitud.expediente', fn ($q) => $q->where('persona_id', $persona->id))
            ->orderByDesc('fecha_emision')
            ->get();

        return DocumentoEmitidoResource::collection($documentos);
    }

    /** Verifica un documento por su número de serie. */
    public function verificar(string $numeroSerie): JsonResponse
    {
        $documento = DocumentoEmitido::where('numero_serie', $numeroSerie)->first();

        if (! $documento) {
            return new JsonResponse([
                'valido' => false,
                'detalle' => 'No existe un documento con ese número de serie.',
            ], 404, ['Content-Type' => 'application/json']);
        }

        return new JsonResponse([
            'valido' => $documento->estado === 'VIGENTE',
            'documento' => DocumentoEmitidoResource::make($documento)->resolve(),
        ]);
    }
}
