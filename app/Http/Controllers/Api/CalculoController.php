<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CalculadoraEstadiaService;
use App\Services\CalculadoraPenalidadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CalculoController extends Controller
{
    public function __construct(
        private readonly CalculadoraEstadiaService $estadia,
        private readonly CalculadoraPenalidadService $penalidades,
    ) {}

    /** Tasa de estadía para una persona a una fecha de salida. */
    public function tasaEstadia(Request $request): JsonResponse
    {
        $request->validate([
            'persona_id' => ['required', 'uuid', 'exists:personas,id'],
            'fecha_salida' => ['required', 'date'],
        ]);

        $personaId = (string) $request->input('persona_id');
        $fechaSalida = Carbon::parse($request->input('fecha_salida'));

        $calculo = $this->estadia->calcularParaPersona($personaId, $fechaSalida);

        return new JsonResponse([
            'data' => [
                'persona_id' => $personaId,
                'fecha_salida' => $fechaSalida->toDateString(),
                'dias_sobreestadia' => $calculo['dias'],
                'monto_base' => $calculo['monto_base'],
                'recargo_anios' => $calculo['recargo_anios'],
                'monto_total' => $calculo['monto'],
                'moneda' => config('dgm.moneda', 'DOP'),
            ],
        ]);
    }

    /** RN-04: penalidad por residencia temporal vencida (por mes o fracción). */
    public function penalidad(Request $request): JsonResponse
    {
        $request->validate([
            'fecha_vencimiento' => ['required', 'date'],
            'fecha_calculo' => ['nullable', 'date'],
        ]);

        $vencimiento = Carbon::parse($request->input('fecha_vencimiento'));
        $fechaCalculo = $request->filled('fecha_calculo') ? Carbon::parse($request->input('fecha_calculo')) : Carbon::now();

        $calculo = $this->penalidades->calcular($vencimiento, $fechaCalculo);

        return new JsonResponse([
            'data' => [
                'fecha_vencimiento' => $vencimiento->toDateString(),
                'fecha_calculo' => $fechaCalculo->toDateString(),
                'meses_vencidos' => $calculo['meses_vencidos'],
                'monto' => $calculo['monto'],
                'moneda' => config('dgm.moneda', 'DOP'),
            ],
        ]);
    }
}
