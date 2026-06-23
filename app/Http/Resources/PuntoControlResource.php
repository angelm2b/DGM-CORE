<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PuntoControl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PuntoControl */
class PuntoControlResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
        ];
    }
}
