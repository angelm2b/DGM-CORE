<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DocumentoAdjunto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DocumentoAdjunto */
class DocumentoAdjuntoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'solicitud_id' => $this->solicitud_id,
            'tipo_documento' => $this->tipo_documento,
            'formato' => $this->formato,
            'ruta' => $this->ruta,
            'validado' => $this->validado,
            'validado_por' => $this->validado_por,
            'creado_en' => $this->created_at?->toIso8601String(),
        ];
    }
}
