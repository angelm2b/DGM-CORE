<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Oficina extends Model
{
    protected $table = 'oficinas';

    protected $fillable = ['codigo', 'nombre', 'localidad'];

    public function expedientes(): HasMany
    {
        return $this->hasMany(Expediente::class, 'oficina_id');
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class, 'oficina_id');
    }
}
