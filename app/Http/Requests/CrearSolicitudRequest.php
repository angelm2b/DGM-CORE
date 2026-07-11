<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrearSolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Se puede indicar un expediente existente o una persona (se le abre expediente).
            'expediente_id' => ['nullable', 'uuid', 'exists:expedientes,id'],
            'persona_id' => ['required_without:expediente_id', 'uuid', 'exists:personas,id'],
            // Solo servicios activos: desactivar un servicio en el admin bloquea
            'servicio_id' => ['required', 'integer', Rule::exists('servicios', 'id')->where('activo', true)],
            'oficina_id' => ['required', 'integer', 'exists:oficinas,id'],
            'canal_origen' => ['required', Rule::in(['WEB', 'CAJA'])],
            'fecha_cita' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}
