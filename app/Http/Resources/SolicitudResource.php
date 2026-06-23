<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Solicitud;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Solicitud */
class SolicitudResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'expediente_id' => $this->expediente_id,
            'numero_expediente' => $this->whenLoaded('expediente', fn () => $this->expediente->numero_expediente),
            'servicio_id' => $this->servicio_id,
            'servicio' => ServicioResource::make($this->whenLoaded('servicio')),
            'canal_origen' => $this->canal_origen,
            'estado_actual' => $this->estado_actual->getValue(),
            'oficina_id' => $this->oficina_id,
            'fecha_cita' => $this->fecha_cita?->toIso8601String(),
            'observaciones' => $this->observaciones,
            'fecha_creacion' => $this->fecha_creacion?->toIso8601String(),
            'fecha_ultima_accion' => $this->fecha_ultima_accion?->toIso8601String(),
            'historial' => SolicitudEstadoResource::collection($this->whenLoaded('estados')),
            'adjuntos' => DocumentoAdjuntoResource::collection($this->whenLoaded('adjuntos')),
            'ordenes_pago' => OrdenPagoResource::collection($this->whenLoaded('ordenesPago')),
        ];
    }
}
