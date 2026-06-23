<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransicionarSolicitudRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado_destino' => ['required', Rule::in([
                'ENVIADA', 'EN_DEPURACION', 'DOCS_OBSERVADOS', 'APROBADA_PAGO_PENDIENTE',
                'PAGADA', 'EN_PROCESO', 'APROBADA', 'DOCUMENTO_EMITIDO', 'ENTREGADO',
                'RECHAZADA', 'CADUCADA', 'ANULADA',
            ])],
            'motivo' => ['nullable', 'string', 'max:255'],
            'sistema_origen' => ['nullable', Rule::in(['CORE', 'INTEGRACION', 'CAJA', 'WEB'])],
            'usuario_id' => ['nullable', 'integer', 'exists:usuarios,id'],
        ];
    }
}
