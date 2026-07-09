<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarPagoRequest;
use App\Http\Resources\OrdenPagoResource;
use App\Http\Resources\PagoResource;
use App\Models\OrdenPago;
use App\Models\Pago;
use App\Services\PagoService;
use Illuminate\Http\JsonResponse;

class PagoController extends Controller
{
    public function __construct(private readonly PagoService $pagos) {}

    /** Consulta una orden de pago. */
    public function show(OrdenPago $ordenPago): OrdenPagoResource
    {
        return OrdenPagoResource::make($ordenPago->load('pagos'));
    }

    /** Consulta un pago registrado (p. ej. para reimprimir el comprobante). */
    public function showPago(Pago $pago): PagoResource
    {
        return PagoResource::make($pago);
    }

    /**
     * Registra un pago. Idempotente por la cabecera Idempotency-Key:
     * concilia la orden, genera el comprobante y avanza la solicitud a PAGADA.
     */
    public function store(RegistrarPagoRequest $request): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        $pago = $this->pagos->registrar($request->validated(), $idempotencyKey);

        $status = $pago->wasRecentlyCreated ? 201 : 200;

        return PagoResource::make($pago)->response()->setStatusCode($status);
    }
}
