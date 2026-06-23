<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\OrdenPago;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrdenPago */
class OrdenPagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'solicitud_id' => $this->solicitud_id,
            'detalle' => $this->detalle,
            'monto_total' => (string) $this->monto_total,
            'moneda' => $this->moneda,
            'estado' => $this->estado,
            'fecha_emision' => $this->fecha_emision?->toIso8601String(),
            'fecha_vencimiento' => $this->fecha_vencimiento?->toIso8601String(),
            'pagos' => PagoResource::collection($this->whenLoaded('pagos')),
        ];
    }
}
