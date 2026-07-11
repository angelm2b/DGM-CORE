<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

/** Ajuste global del sistema (clave/valor). Cada cambio queda auditado. */
class Ajuste extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'ajustes';

    protected $fillable = ['clave', 'valor'];

    private const CLAVE_API = 'api_encendida';

    private const CACHE_API = 'ajustes.api_encendida';

    /** Estado del apagador general de la API. Sin registro se asume encendida. */
    public static function apiEncendida(): bool
    {
        return Cache::rememberForever(self::CACHE_API, function (): bool {
            $ajuste = self::query()->where('clave', self::CLAVE_API)->first();

            return $ajuste === null || $ajuste->valor === '1';
        });
    }

    /** Enciende o apaga la API y devuelve el nuevo estado. */
    public static function alternarApi(): bool
    {
        $encendida = ! self::apiEncendida();

        self::updateOrCreate(
            ['clave' => self::CLAVE_API],
            ['valor' => $encendida ? '1' : '0'],
        );

        Cache::forget(self::CACHE_API);

        return $encendida;
    }
}
