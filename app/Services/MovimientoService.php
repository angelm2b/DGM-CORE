<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MovimientoMigratorio;
use App\Support\Dinero;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Registro de movimientos migratorios. En las salidas (tipo=S) calcula la
 * sobreestadía y la devuelve cuando corresponde.
 */
class MovimientoService
{
    public function __construct(
        private readonly CalculadoraEstadiaService $estadia,
    ) {}

    /**
     * @param  array{persona_id:string, tipo:string, punto_control_id:int, fecha_hora:string, medio?:?string, eticket_codigo?:?string, dias_autorizados?:?int, oficial_id?:?int}  $datos
     * @return array{movimiento:MovimientoMigratorio, sobreestadia:?array}
     */
    public function registrar(array $datos): array
    {
        return DB::transaction(function () use ($datos) {
            $movimiento = MovimientoMigratorio::create([
                'persona_id' => $datos['persona_id'],
                'tipo' => $datos['tipo'],
                'punto_control_id' => $datos['punto_control_id'],
                'fecha_hora' => Carbon::parse($datos['fecha_hora']),
                'medio' => $datos['medio'] ?? null,
                'eticket_codigo' => $datos['eticket_codigo'] ?? null,
                'dias_autorizados' => $datos['dias_autorizados'] ?? null,
                'oficial_id' => $datos['oficial_id'] ?? null,
            ]);

            $sobreestadia = null;

            // En la salida se calcula la sobreestadía respecto al último ingreso.
            if ($movimiento->tipo === 'S') {
                $calculo = $this->estadia->calcularParaPersona($movimiento->persona_id, $movimiento->fecha_hora);

                if ($calculo['dias'] > 0 && Dinero::comparar($calculo['monto'], '0.00') > 0) {
                    $sobreestadia = $calculo;
                }
            }

            return ['movimiento' => $movimiento, 'sobreestadia' => $sobreestadia];
        });
    }
}
