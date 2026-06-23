<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoAdjunto extends Model
{
    protected $table = 'documentos_adjuntos';

    protected $fillable = [
        'solicitud_id',
        'tipo_documento',
        'formato',
        'ruta',
        'validado',
        'validado_por',
    ];

    protected function casts(): array
    {
        return [
            'validado' => 'boolean',
        ];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }

    public function validadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'validado_por');
    }
}
