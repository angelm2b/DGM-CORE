<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmitirDocumentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'solicitud_id' => ['required', 'uuid', 'exists:solicitudes,id'],
            'tipo' => ['required', Rule::in([
                'CARNET_RT9', 'CARNET_RP1', 'CARNET_RD1', 'PRORROGA',
                'CERT_SALIDA_MENOR', 'PERMISO_REENTRADA', 'CERTIFICACION',
            ])],
            'fecha_emision' => ['nullable', 'date'],
            'fecha_vencimiento' => ['nullable', 'date'],
        ];
    }
}
