<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class OrdenPago extends Model implements AuditableContract
{
    use Auditable;
    use HasUuids;

    protected $table = 'ordenes_pago';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'solicitud_id',
        'detalle',
        'monto_total',
        'moneda',
        'estado',
        'fecha_emision',
        'fecha_vencimiento',
    ];

    protected function casts(): array
    {
        return [
            'detalle' => 'array',
            'monto_total' => 'decimal:2',
            'fecha_emision' => 'datetime',
            'fecha_vencimiento' => 'datetime',
        ];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'orden_pago_id');
    }
}
