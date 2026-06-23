<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MovimientoMigratorio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MovimientoMigratorio */
class MovimientoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'persona_id' => $this->persona_id,
            'tipo' => $this->tipo,
            'punto_control_id' => $this->punto_control_id,
            'fecha_hora' => $this->fecha_hora?->toIso8601String(),
            'medio' => $this->medio,
            'eticket_codigo' => $this->eticket_codigo,
            'dias_autorizados' => $this->dias_autorizados,
            'oficial_id' => $this->oficial_id,
        ];
    }
}
