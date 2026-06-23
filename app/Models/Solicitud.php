<?php

declare(strict_types=1);

namespace App\Models;

use App\States\Solicitud\EstadoSolicitud;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\ModelStates\HasStates;

class Solicitud extends Model implements AuditableContract
{
    use Auditable;
    use HasStates;
    use HasUuids;

    protected $table = 'solicitudes';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'expediente_id',
        'servicio_id',
        'canal_origen',
        'estado_actual',
        'fecha_creacion',
        'fecha_ultima_accion',
        'fecha_cita',
        'oficina_id',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'estado_actual' => EstadoSolicitud::class,
            'fecha_creacion' => 'datetime',
            'fecha_ultima_accion' => 'datetime',
            'fecha_cita' => 'datetime',
        ];
    }

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Expediente::class, 'expediente_id');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    public function oficina(): BelongsTo
    {
        return $this->belongsTo(Oficina::class, 'oficina_id');
    }

    public function estados(): HasMany
    {
        return $this->hasMany(SolicitudEstado::class, 'solicitud_id');
    }

    public function adjuntos(): HasMany
    {
        return $this->hasMany(DocumentoAdjunto::class, 'solicitud_id');
    }

    public function ordenesPago(): HasMany
    {
        return $this->hasMany(OrdenPago::class, 'solicitud_id');
    }

    public function documentosEmitidos(): HasMany
    {
        return $this->hasMany(DocumentoEmitido::class, 'solicitud_id');
    }

    /** Atajo a la persona dueña del expediente. */
    public function persona(): HasOneThrough
    {
        return $this->hasOneThrough(
            Persona::class,
            Expediente::class,
            'id',            // expedientes.id
            'id',            // personas.id
            'expediente_id', // solicitudes.expediente_id
            'persona_id',    // expedientes.persona_id
        );
    }
}
