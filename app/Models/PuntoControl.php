<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PuntoControl extends Model
{
    protected $table = 'puntos_control';

    protected $fillable = ['codigo', 'nombre', 'tipo'];

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoMigratorio::class, 'punto_control_id');
    }
}
