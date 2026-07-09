<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpedienteResource;
use App\Models\Expediente;
use App\Models\Persona;

class ExpedienteController extends Controller
{
    /** Detalle de un expediente con su persona y solicitudes. */
    public function show(Expediente $expediente): ExpedienteResource
    {
        return ExpedienteResource::make(
            $expediente->load(['persona', 'solicitudes.servicio'])
        );
    }

    /** Expedientes de una persona, del más reciente al más antiguo. */
    public function porPersona(Persona $persona)
    {
        return ExpedienteResource::collection(
            $persona->expedientes()->orderByDesc('fecha_apertura')->get()
        );
    }
}
