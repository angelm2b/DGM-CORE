<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Persona extends Model implements AuditableContract
{
    use Auditable;
    use HasUuids;

    protected $table = 'personas';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nacionalidad',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'sexo',
        'email',
        'telefono',
        'pasaporte_vence',
        'categoria_migratoria_id',
        'estatus_migratorio',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'pasaporte_vence' => 'date',
        ];
    }

    public function categoriaMigratoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaMigratoria::class, 'categoria_migratoria_id');
    }

    public function expedientes(): HasMany
    {
        return $this->hasMany(Expediente::class, 'persona_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoMigratorio::class, 'persona_id');
    }

    /** Solicitudes de la persona a través de sus expedientes. */
    public function solicitudes(): HasManyThrough
    {
        return $this->hasManyThrough(
            Solicitud::class,
            Expediente::class,
            'persona_id',    // expedientes.persona_id
            'expediente_id', // solicitudes.expediente_id
        );
    }

    /** Edad en años a una fecha dada (por defecto hoy). RN-09. */
    public function edad(?\DateTimeInterface $aFecha = null): int
    {
        return $this->fecha_nacimiento->age;
    }

    public function esMenorDeEdad(): bool
    {
        return $this->edad() < (int) config('dgm.reglas.mayoria_edad', 18);
    }
}
