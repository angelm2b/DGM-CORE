<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Tarifa;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tarifa */
class TarifaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'servicio_id' => $this->servicio_id,
            'concepto' => $this->concepto,
            'monto' => (string) $this->monto,
            'moneda' => $this->moneda,
            'vigente_desde' => $this->vigente_desde?->toDateString(),
            'vigente_hasta' => $this->vigente_hasta?->toDateString(),
            'resolucion' => $this->resolucion,
        ];
    }
}
