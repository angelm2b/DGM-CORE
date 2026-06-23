<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MovimientoMigratorio;
use App\Models\TablaEstadia;
use App\Support\Dinero;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * RN-02: tasa de sobreestadía.
 *
 * El monto base sale de la tabla escalonada (tabla_estadia) según los días de
 * exceso. Adicionalmente, a partir de 10 años de sobreestadía se aplica un
 * recargo de +RD$5,000 por cada año o fracción que exceda ese umbral.
 */
class CalculadoraEstadiaService
{
    private const DIAS_POR_ANIO = 365;

    /**
     * Calcula la tasa para una cantidad de días de sobreestadía.
     *
     * @return array{dias:int, monto_base:string, recargo_anios:string, monto:string}
     */
    public function calcularPorDias(int $diasSobreestadia): array
    {
        if ($diasSobreestadia <= 0) {
            return ['dias' => 0, 'monto_base' => '0.00', 'recargo_anios' => '0.00', 'monto' => '0.00'];
        }

        $rango = TablaEstadia::query()
            ->where('dias_desde', '<=', $diasSobreestadia)
            ->where(function ($q) use ($diasSobreestadia) {
                $q->whereNull('dias_hasta')->orWhere('dias_hasta', '>=', $diasSobreestadia);
            })
            ->orderByDesc('dias_desde')
            ->first();

        $montoBase = $rango ? (string) $rango->monto : '0.00';

        // Recargo por año o fracción a partir de 10 años.
        $umbralAnios = (int) config('dgm.reglas.estadia_anios_recargo_desde', 10);
        $recargoAnual = Dinero::normalizar((string) config('dgm.reglas.estadia_recargo_anual', '5000.00'));
        $umbralDias = $umbralAnios * self::DIAS_POR_ANIO;

        $recargo = '0.00';
        if ($diasSobreestadia > $umbralDias) {
            $aniosExtra = (int) ceil(($diasSobreestadia - $umbralDias) / self::DIAS_POR_ANIO);
            $recargo = Dinero::multiplicar($recargoAnual, $aniosExtra);
        }

        return [
            'dias' => $diasSobreestadia,
            'monto_base' => Dinero::normalizar($montoBase),
            'recargo_anios' => $recargo,
            'monto' => Dinero::sumar(Dinero::normalizar($montoBase), $recargo),
        ];
    }

    /**
     * Días de sobreestadía a la fecha de salida, en función del último ingreso
     * y los días autorizados en ese movimiento.
     */
    public function diasSobreestadia(MovimientoMigratorio $ultimaEntrada, CarbonInterface $fechaSalida): int
    {
        $diasAutorizados = (int) ($ultimaEntrada->dias_autorizados ?? 0);
        $limite = Carbon::parse($ultimaEntrada->fecha_hora)->copy()->addDays($diasAutorizados)->startOfDay();
        $salida = Carbon::parse($fechaSalida)->startOfDay();

        if ($salida->lessThanOrEqualTo($limite)) {
            return 0;
        }

        return (int) $limite->diffInDays($salida);
    }

    /**
     * Calcula la tasa para una persona a una fecha de salida, usando su
     * último movimiento de entrada registrado.
     *
     * @return array{dias:int, monto_base:string, recargo_anios:string, monto:string}
     */
    public function calcularParaPersona(string $personaId, CarbonInterface $fechaSalida): array
    {
        $entrada = MovimientoMigratorio::query()
            ->where('persona_id', $personaId)
            ->where('tipo', 'E')
            ->where('fecha_hora', '<=', Carbon::parse($fechaSalida))
            ->orderByDesc('fecha_hora')
            ->first();

        if (! $entrada) {
            return ['dias' => 0, 'monto_base' => '0.00', 'recargo_anios' => '0.00', 'monto' => '0.00'];
        }

        $dias = $this->diasSobreestadia($entrada, $fechaSalida);

        return $this->calcularPorDias($dias);
    }
}
