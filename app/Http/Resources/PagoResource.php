<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Pago */
class PagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'orden_pago_id' => $this->orden_pago_id,
            'monto' => (string) $this->monto,
            'metodo' => $this->metodo,
            'referencia_externa' => $this->referencia_externa,
            'numero_comprobante' => $this->numero_comprobante,
            'creado_en' => $this->created_at?->toIso8601String(),
        ];
    }
}
