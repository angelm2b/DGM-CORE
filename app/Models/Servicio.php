<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servicio extends Model
{
    protected $table = 'servicios';

    protected $fillable = [
        'codigo',
        'nombre',
        'categoria_migratoria_id',
        'requiere_cita',
        'dias_sla',
        'canal',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'requiere_cita' => 'boolean',
            'activo' => 'boolean',
            'dias_sla' => 'integer',
        ];
    }

    public function categoriaMigratoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaMigratoria::class, 'categoria_migratoria_id');
    }

    public function tarifas(): HasMany
    {
        return $this->hasMany(Tarifa::class, 'servicio_id');
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class, 'servicio_id');
    }
}
