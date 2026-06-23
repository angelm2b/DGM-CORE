<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'orden_pago_id' => ['required', 'uuid', 'exists:ordenes_pago,id'],
            'monto' => ['required', 'numeric', 'min:0'],
            'metodo' => ['required', Rule::in(['EFECTIVO', 'TARJETA', 'TRANSFERENCIA', 'PORTAL'])],
            'referencia_externa' => ['nullable', 'string', 'max:255'],
        ];
    }
}
