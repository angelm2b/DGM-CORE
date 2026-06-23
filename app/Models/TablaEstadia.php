<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TablaEstadia extends Model
{
    protected $table = 'tabla_estadia';

    protected $fillable = ['dias_desde', 'dias_hasta', 'monto'];

    protected function casts(): array
    {
        return [
            'dias_desde' => 'integer',
            'dias_hasta' => 'integer',
            'monto' => 'decimal:2',
        ];
    }
}
