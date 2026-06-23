<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Persona */
class PersonaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo_documento' => $this->tipo_documento,
            'numero_documento' => $this->numero_documento,
            'nacionalidad' => $this->nacionalidad,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'fecha_nacimiento' => $this->fecha_nacimiento?->toDateString(),
            'sexo' => $this->sexo,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'pasaporte_vence' => $this->pasaporte_vence?->toDateString(),
            'categoria_migratoria' => CategoriaResource::make($this->whenLoaded('categoriaMigratoria')),
            'categoria_migratoria_id' => $this->categoria_migratoria_id,
            'estatus_migratorio' => $this->estatus_migratorio,
            'creado_en' => $this->created_at?->toIso8601String(),
        ];
    }
}
