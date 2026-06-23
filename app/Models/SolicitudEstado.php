<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudEstado extends Model
{
    protected $table = 'solicitud_estados';

    // Historial append-only: solo created_at.
    public const UPDATED_AT = null;

    protected $fillable = [
        'solicitud_id',
        'estado_anterior',
        'estado_nuevo',
        'usuario_id',
        'sistema_origen',
        'motivo',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
