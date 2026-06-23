<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaMigratoria extends Model
{
    protected $table = 'categorias_migratorias';

    protected $fillable = [
        'codigo',
        'nombre',
        'grupo',
        'vigencia_meses',
        'permite_renovacion',
        'permite_cambio_a_id',
    ];

    protected function casts(): array
    {
        return [
            'permite_renovacion' => 'boolean',
            'vigencia_meses' => 'integer',
        ];
    }

    /** Categoría destino del único cambio permitido (RT-9 -> RP-1). */
    public function permiteCambioA(): BelongsTo
    {
        return $this->belongsTo(self::class, 'permite_cambio_a_id');
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'categoria_migratoria_id');
    }

    public function personas(): HasMany
    {
        return $this->hasMany(Persona::class, 'categoria_migratoria_id');
    }
}
