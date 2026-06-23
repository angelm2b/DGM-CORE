<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrearPersonaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_documento' => ['required', Rule::in(['PASAPORTE', 'CEDULA'])],
            'numero_documento' => ['required', 'string', 'max:50'],
            'nacionalidad' => ['required', 'string', 'size:3'],
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],
            'fecha_nacimiento' => ['required', 'date'],
            'sexo' => ['nullable', Rule::in(['M', 'F', 'X'])],
            'email' => ['nullable', 'email'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'pasaporte_vence' => ['nullable', 'date'],
            'categoria_migratoria_id' => ['nullable', 'integer', 'exists:categorias_migratorias,id'],
            'estatus_migratorio' => ['nullable', Rule::in(['REGULAR', 'IRREGULAR', 'EN_TRAMITE'])],
        ];
    }
}
