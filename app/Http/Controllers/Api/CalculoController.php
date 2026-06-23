<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CalculadoraEstadiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CalculoController extends Controller
{
    public function __construct(private readonly CalculadoraEstadiaService $estadia) {}

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
}
