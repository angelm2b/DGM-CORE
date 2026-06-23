<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarMovimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'persona_id' => ['required', 'uuid', 'exists:personas,id'],
            'tipo' => ['required', Rule::in(['E', 'S'])],
            'punto_control_id' => ['required', 'integer', 'exists:puntos_control,id'],
            'fecha_hora' => ['required', 'date'],
            'medio' => ['nullable', 'string', 'max:255'],
            'eticket_codigo' => ['nullable', 'string', 'max:255'],
            'dias_autorizados' => ['nullable', 'integer', 'min:0'],
            'oficial_id' => ['nullable', 'integer', 'exists:usuarios,id'],
        ];
    }
}
