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
    private const CONCEPTOS_FIJOS = ['DEPOSITO_EXPEDIENTE', 'CARNET', 'REENTRADA'];

    public function __construct(private readonly CalculadoraTarifaService $tarifas) {}

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
