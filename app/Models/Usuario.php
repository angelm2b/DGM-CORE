<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Usuario interno de la DGM (Administrador, Analista de Extranjería, Auditor)
 * y también el cliente "integrador" que consume la API mediante token Sanctum.
 */
class Usuario extends Authenticatable
{
    use HasApiTokens;
    use Notifiable;

    protected $table = 'usuarios';

    protected $fillable = ['nombre', 'email', 'password', 'rol_id', 'activo'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    /** Verifica si el usuario tiene un permiso por su código. */
    public function tienePermiso(string $codigo): bool
    {
        return (bool) $this->rol?->permisos()->where('codigo', $codigo)->exists();
    }

    public function tieneRol(string $codigoRol): bool
    {
        return $this->rol?->codigo === $codigoRol;
    }
}
