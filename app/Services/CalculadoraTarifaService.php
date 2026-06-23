<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tarifa;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Resuelve la tarifa vigente de un servicio para un concepto a una fecha dada,
 * respetando el versionado por vigente_desde / vigente_hasta.
 */
class CalculadoraTarifaService
{
    /**
     * Devuelve la tarifa vigente o null si no hay ninguna aplicable.
     */
    public function tarifaVigente(int $servicioId, string $concepto, ?CarbonInterface $fecha = null): ?Tarifa
    {
        $fecha = $fecha ? Carbon::parse($fecha) : Carbon::now();
        $f = $fecha->toDateString();

        return Tarifa::query()
            ->where('servicio_id', $servicioId)
            ->where('concepto', $concepto)
            ->whereDate('vigente_desde', '<=', $f)
            ->where(function ($q) use ($f) {
                $q->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $f);
            })
            ->orderByDesc('vigente_desde')
            ->first();
    }

    /**
     * Monto vigente (string bcmath) del concepto, o "0.00" si no aplica.
     */
    public function montoVigente(int $servicioId, string $concepto, ?CarbonInterface $fecha = null): string
    {
        $tarifa = $this->tarifaVigente($servicioId, $concepto, $fecha);

        return $tarifa ? (string) $tarifa->monto : '0.00';
    }
}
