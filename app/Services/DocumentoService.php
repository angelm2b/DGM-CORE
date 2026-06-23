<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DocumentoEmitido;
use App\Models\Solicitud;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Emisión, revocación y reposición de documentos oficiales, con numeración de
 * serie correlativa (secuencias).
 */
class DocumentoService
{
    public function __construct(
        private readonly SecuenciaService $secuencias,
        private readonly ElegibilidadService $elegibilidad,
    ) {}

    /**
     * Emite un documento para una solicitud. Si no se indica vencimiento, se
     * deriva según el tipo (RN-06 para el carnet RP-1).
     */
    public function emitir(
        Solicitud $solicitud,
        string $tipo,
        ?CarbonInterface $vencimiento = null,
        ?CarbonInterface $emision = null,
    ): DocumentoEmitido {
        $emision = $emision ? Carbon::parse($emision) : Carbon::now();

        return DB::transaction(function () use ($solicitud, $tipo, $vencimiento, $emision) {
            $vencimiento ??= $this->vencimientoPorTipo($solicitud, $tipo, $emision);

            $documento = DocumentoEmitido::create([
                'solicitud_id' => $solicitud->id,
                'tipo' => $tipo,
                'numero_serie' => $this->secuencias->numeroSerie((int) $emision->year),
                'fecha_emision' => $emision->toDateString(),
                'fecha_vencimiento' => $vencimiento?->toDateString(),
                'estado' => 'VIGENTE',
            ]);

            return $documento;
        });
    }

    /** Revoca un documento vigente. */
    public function revocar(DocumentoEmitido $documento, ?string $motivo = null): DocumentoEmitido
    {
        $documento->update(['estado' => 'REVOCADO']);

        return $documento;
    }

    /**
     * Repone un documento (pérdida/robo/deterioro): marca el original como
     * REPUESTO y emite uno nuevo con serie nueva, conservando tipo y vencimiento.
     */
    public function reponer(DocumentoEmitido $original): DocumentoEmitido
    {
        return DB::transaction(function () use ($original) {
            $original->update(['estado' => 'REPUESTO']);

            $nuevo = DocumentoEmitido::create([
                'solicitud_id' => $original->solicitud_id,
                'tipo' => $original->tipo,
                'numero_serie' => $this->secuencias->numeroSerie(),
                'fecha_emision' => Carbon::now()->toDateString(),
                'fecha_vencimiento' => $original->fecha_vencimiento?->toDateString(),
                'estado' => 'VIGENTE',
            ]);

            return $nuevo;
        });
    }

    /**
     * Vigencia por defecto según el tipo de documento.
     */
    private function vencimientoPorTipo(Solicitud $solicitud, string $tipo, CarbonInterface $emision): ?CarbonInterface
    {
        $personaId = $solicitud->expediente?->persona_id;

        return match ($tipo) {
            'CARNET_RT9' => $emision->copy()->addYear(),                 // 1 año
            'CARNET_RP1' => $emision->copy()->addYears(                  // RN-06: 1 o 4 años
                $personaId ? $this->elegibilidad->vigenciaCarnetRP1($personaId) : 1
            ),
            'CARNET_RD1' => $emision->copy()->addYears(10),              // RD-1: 10 años
            default => null,                                            // prórrogas/certificaciones: sin vencimiento fijo
        };
    }
}
