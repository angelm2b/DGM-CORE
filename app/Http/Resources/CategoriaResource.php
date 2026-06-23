<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\CategoriaMigratoria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CategoriaMigratoria */
class CategoriaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'grupo' => $this->grupo,
            'vigencia_meses' => $this->vigencia_meses,
            'permite_renovacion' => $this->permite_renovacion,
            'permite_cambio_a' => $this->whenLoaded('permiteCambioA', fn () => $this->permiteCambioA?->codigo),
        ];
    }
}
