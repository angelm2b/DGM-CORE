<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DocumentoEmitido;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DocumentoEmitido */
class DocumentoEmitidoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'solicitud_id' => $this->solicitud_id,
            'tipo' => $this->tipo,
            'numero_serie' => $this->numero_serie,
            'fecha_emision' => $this->fecha_emision?->toDateString(),
            'fecha_vencimiento' => $this->fecha_vencimiento?->toDateString(),
            'estado' => $this->estado,
        ];
    }
}
