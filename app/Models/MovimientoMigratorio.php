<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoMigratorio extends Model
{
    protected $table = 'movimientos_migratorios';

    protected $fillable = [
        'persona_id',
        'tipo',
        'punto_control_id',
        'fecha_hora',
        'medio',
        'eticket_codigo',
        'dias_autorizados',
        'oficial_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
            'dias_autorizados' => 'integer',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function puntoControl(): BelongsTo
    {
        return $this->belongsTo(PuntoControl::class, 'punto_control_id');
    }
}
