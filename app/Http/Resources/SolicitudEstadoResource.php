<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\SolicitudEstado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SolicitudEstado */
class SolicitudEstadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'estado_anterior' => $this->estado_anterior,
            'estado_nuevo' => $this->estado_nuevo,
            'sistema_origen' => $this->sistema_origen,
            'usuario_id' => $this->usuario_id,
            'motivo' => $this->motivo,
            'fecha' => $this->created_at?->toIso8601String(),
        ];
    }
}
