<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Dinero;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * RN-04: penalidad por residencia temporal vencida.
 * RD$1,000 por cada mes (o fracción) transcurrido desde el vencimiento.
 */
class CalculadoraPenalidadService
{
    /**
     * @return array{meses_vencidos:int, monto:string}
     */
    public function calcular(CarbonInterface $fechaVencimiento, ?CarbonInterface $fechaCalculo = null): array
    {
        $fechaCalculo = $fechaCalculo ? Carbon::parse($fechaCalculo) : Carbon::now();
        $vencimiento = Carbon::parse($fechaVencimiento);

        if ($fechaCalculo->lessThanOrEqualTo($vencimiento)) {
            return ['meses_vencidos' => 0, 'monto' => '0.00'];
        }

        // Meses completos transcurridos + 1 si hay fracción adicional.
        $mesesCompletos = $vencimiento->diffInMonths($fechaCalculo);
        $tieneFraccion = $vencimiento->copy()->addMonths($mesesCompletos)->lessThan($fechaCalculo);
        $meses = (int) $mesesCompletos + ($tieneFraccion ? 1 : 0);

        $montoMes = (string) config('dgm.reglas.penalidad_mes', '1000.00');
        $monto = Dinero::multiplicar(Dinero::normalizar($montoMes), $meses);

        return ['meses_vencidos' => $meses, 'monto' => $monto];
    }
}
