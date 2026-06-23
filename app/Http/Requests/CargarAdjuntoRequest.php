<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CargarAdjuntoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_documento' => ['required', Rule::in([
                'PASAPORTE_FOTO', 'FOTO_2X2', 'CERT_NO_ANTECEDENTES', 'POLIZA',
                'CERT_MEDICO', 'TICKET_RETORNO', 'SOLVENCIA',
            ])],
            // RN-11: los adjuntos solo se aceptan en formato JPG.
            'formato' => ['required', Rule::in(['JPG', 'jpg'])],
            // Se acepta un archivo subido (JPG, máx. 5 MB) o una ruta ya almacenada.
            'archivo' => ['nullable', 'file', 'mimes:jpg,jpeg', 'max:5120'],
            'ruta' => ['required_without:archivo', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'formato.in' => 'RN-11: los adjuntos solo se aceptan en formato JPG.',
            'archivo.mimes' => 'RN-11: los adjuntos solo se aceptan en formato JPG.',
        ];
    }
}
