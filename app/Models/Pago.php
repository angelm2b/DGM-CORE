<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Pago extends Model implements AuditableContract
{
    use Auditable;
    use HasUuids;

    protected $table = 'pagos';

    protected $keyType = 'string';

    public $incrementing = false;

    // Solo created_at (registro inmutable de pago).
    public const UPDATED_AT = null;

    protected $fillable = [
        'orden_pago_id',
        'monto',
        'metodo',
        'referencia_externa',
        'numero_comprobante',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function ordenPago(): BelongsTo
    {
        return $this->belongsTo(OrdenPago::class, 'orden_pago_id');
    }
}
