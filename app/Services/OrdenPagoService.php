<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OrdenPago;
use App\Models\Solicitud;
use App\Support\Dinero;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Genera órdenes de pago a partir de las tarifas vigentes del servicio de la
 * solicitud. Se invoca al pasar la solicitud a APROBADA_PAGO_PENDIENTE.
 */
class OrdenPagoService
{
    /** Conceptos de tarifa de monto fijo que se incluyen automáticamente. */
    private const CONCEPTOS_FIJOS = ['DEPOSITO_EXPEDIENTE', 'CARNET', 'REENTRADA', 'PRORROGA', 'CERTIFICACION'];

    public function __construct(
        private readonly CalculadoraTarifaService $tarifas,
        private readonly CalculadoraEstadiaService $estadia,
    ) {}

    /**
     * Crea (si no existe ya una pendiente) la orden de pago de la solicitud.
     *
     * @param  array<int, array{concepto:string, monto:string}>  $conceptosExtra
     */
    public function generarParaSolicitud(Solicitud $solicitud, array $conceptosExtra = []): OrdenPago
    {
        $existente = $solicitud->ordenesPago()->where('estado', 'PENDIENTE')->first();
        if ($existente) {
            return $existente;
        }

        return DB::transaction(function () use ($solicitud, $conceptosExtra) {
            $detalle = [];
            $total = '0.00';

            foreach (self::CONCEPTOS_FIJOS as $concepto) {
                $tarifa = $this->tarifas->tarifaVigente($solicitud->servicio_id, $concepto, $solicitud->fecha_creacion);
                if ($tarifa) {
                    $monto = Dinero::normalizar((string) $tarifa->monto);
                    $detalle[] = ['concepto' => $concepto, 'monto' => $monto, 'resolucion' => $tarifa->resolucion];
                    $total = Dinero::sumar($total, $monto);
                }
            }

            foreach ($conceptosExtra as $item) {
                $monto = Dinero::normalizar($item['monto']);
                $detalle[] = ['concepto' => $item['concepto'], 'monto' => $monto, 'resolucion' => $item['resolucion'] ?? null];
                $total = Dinero::sumar($total, $monto);
            }

            // Tasa de estadía (RN-02): no es tarifa fija; se calcula por persona
            // con la tabla escalonada según los días de sobreestadía a la fecha
            // de emisión de la orden.
            if ($solicitud->servicio?->codigo === config('dgm.servicio_tasa_estadia', 'SRV-007')) {
                $persona = $solicitud->persona;
                if ($persona) {
                    $calculo = $this->estadia->calcularParaPersona($persona->id, Carbon::now());
                    if (! Dinero::esCero($calculo['monto'])) {
                        $detalle[] = [
                            'concepto' => 'TASA_ESTADIA',
                            'monto' => $calculo['monto'],
                            'resolucion' => null,
                            'dias_sobreestadia' => $calculo['dias'],
                        ];
                        $total = Dinero::sumar($total, $calculo['monto']);
                    }
                }
            }

            $vigenciaDias = (int) config('dgm.orden_pago_vigencia_dias', 15);

            return OrdenPago::create([
                'solicitud_id' => $solicitud->id,
                'detalle' => $detalle,
                'monto_total' => $total,
                'moneda' => config('dgm.moneda', 'DOP'),
                'estado' => 'PENDIENTE',
                'fecha_emision' => Carbon::now(),
                'fecha_vencimiento' => Carbon::now()->addDays($vigenciaDias),
            ]);
        });
    }
}
