<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Servicio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Servicio */
class ServicioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'categoria_migratoria_id' => $this->categoria_migratoria_id,
            'requiere_cita' => $this->requiere_cita,
            'dias_sla' => $this->dias_sla,
            'canal' => $this->canal,
            'activo' => $this->activo,
        ];
    }
}
