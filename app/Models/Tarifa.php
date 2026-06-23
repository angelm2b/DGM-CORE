<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Tarifa extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'tarifas';

    protected $fillable = [
        'servicio_id',
        'concepto',
        'monto',
        'moneda',
        'vigente_desde',
        'vigente_hasta',
        'resolucion',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }
}
