<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidarAdjuntoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // true = validado (RN-08); false = retirar la validación.
            'validado' => ['required', 'boolean'],
            'usuario_id' => ['nullable', 'integer', 'exists:usuarios,id'],
        ];
    }
}
