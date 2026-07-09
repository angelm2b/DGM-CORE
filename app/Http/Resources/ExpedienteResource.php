<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Expediente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Expediente */
class ExpedienteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_expediente' => $this->numero_expediente,
            'persona_id' => $this->persona_id,
            'persona' => PersonaResource::make($this->whenLoaded('persona')),
            'oficina_id' => $this->oficina_id,
            'fecha_apertura' => $this->fecha_apertura?->toDateString(),
            'solicitudes' => SolicitudResource::collection($this->whenLoaded('solicitudes')),
        ];
    }
}
