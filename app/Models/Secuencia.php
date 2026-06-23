<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Secuencia extends Model
{
    protected $table = 'secuencias';

    protected $fillable = ['clave', 'anio', 'ultimo_valor'];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'ultimo_valor' => 'integer',
        ];
    }
}
