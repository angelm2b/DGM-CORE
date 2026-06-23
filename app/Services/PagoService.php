<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\OrdenPago;
use App\Models\Pago;
use App\Support\Dinero;
use Illuminate\Support\Facades\DB;

/**
 * Registro de pagos: concilia la orden, genera el número de comprobante y
 * avanza la solicitud a PAGADA. Idempotente por Idempotency-Key.
 */
class PagoService
{
    public function __construct(
        private readonly SecuenciaService $secuencias,
        private readonly SolicitudService $solicitudes,
    ) {}

    /**
     * @param  array{orden_pago_id:string, monto:string, metodo:string, referencia_externa?:?string}  $datos
     */
    public function registrar(array $datos, ?string $idempotencyKey = null): Pago
    {
        // Idempotencia: si ya existe un pago con esta clave, se devuelve el mismo.
        if ($idempotencyKey !== null) {
            $previo = Pago::where('idempotency_key', $idempotencyKey)->first();
            if ($previo) {
                return $previo;
            }
        }

        return DB::transaction(function () use ($datos, $idempotencyKey) {
            /** @var OrdenPago $orden */
            $orden = OrdenPago::whereKey($datos['orden_pago_id'])->lockForUpdate()->firstOrFail();

            if ($orden->estado === 'PAGADA') {
                throw new ReglaNegocioException('La orden de pago ya fue saldada.');
            }
            if (in_array($orden->estado, ['ANULADA', 'VENCIDA'], true)) {
                throw new ReglaNegocioException("La orden de pago está {$orden->estado} y no admite pagos.");
            }

            // Conciliación: el monto debe cubrir el total de la orden.
            if (Dinero::comparar(
                Dinero::normalizar($datos['monto']),
                Dinero::normalizar((string) $orden->monto_total)
            ) < 0) {
                throw new ReglaNegocioException('El monto del pago no cubre el total de la orden.');
            }

            $pago = Pago::create([
                'orden_pago_id' => $orden->id,
                'monto' => Dinero::normalizar($datos['monto']),
                'metodo' => $datos['metodo'],
                'referencia_externa' => $datos['referencia_externa'] ?? null,
                'numero_comprobante' => $this->secuencias->numeroComprobante(),
                'idempotency_key' => $idempotencyKey,
            ]);

            $orden->update(['estado' => 'PAGADA']);

            // Avanza la solicitud a PAGADA si procede.
            $solicitud = $orden->solicitud;
            if ($solicitud && $solicitud->estado_actual->getValue() === 'APROBADA_PAGO_PENDIENTE') {
                $this->solicitudes->transicionar($solicitud, 'PAGADA', 'INTEGRACION', motivo: "Pago {$pago->numero_comprobante}");
            }

            return $pago;
        });
    }
}
