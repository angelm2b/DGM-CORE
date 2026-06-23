<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Secuencia;
use Illuminate\Support\Facades\DB;

/**
 * Generación de numeración correlativa sin colisiones bajo concurrencia.
 * MySQL no tiene SEQUENCE: se usa SELECT ... FOR UPDATE sobre la tabla
 * secuencias dentro de una transacción.
 */
class SecuenciaService
{
    public const EXPEDIENTE = 'EXPEDIENTE';

    public const COMPROBANTE = 'COMPROBANTE';

    public const SERIE_DOC = 'SERIE_DOC';

    /**
     * Devuelve el siguiente valor correlativo para una clave y año.
     * Debe invocarse (idealmente) dentro de la transacción de la operación.
     */
    public function siguiente(string $clave, ?int $anio = null): int
    {
        $anio = $anio ?? (int) now()->year;

        return DB::transaction(function () use ($clave, $anio) {
            // Garantiza la existencia de la fila (la restricción única evita duplicados en carrera).
            Secuencia::firstOrCreate(['clave' => $clave, 'anio' => $anio], ['ultimo_valor' => 0]);

            // Bloquea la fila para incrementar de forma atómica.
            $secuencia = Secuencia::where('clave', $clave)
                ->where('anio', $anio)
                ->lockForUpdate()
                ->first();

            $secuencia->ultimo_valor++;
            $secuencia->save();

            return (int) $secuencia->ultimo_valor;
        });
    }

    /** Número de expediente con formato DGM-AAAA-NNNNNN. */
    public function numeroExpediente(?int $anio = null): string
    {
        $anio = $anio ?? (int) now()->year;
        $n = $this->siguiente(self::EXPEDIENTE, $anio);

        return sprintf('DGM-%04d-%06d', $anio, $n);
    }

    /** Número de comprobante de pago con formato CMP-AAAA-NNNNNN. */
    public function numeroComprobante(?int $anio = null): string
    {
        $anio = $anio ?? (int) now()->year;
        $n = $this->siguiente(self::COMPROBANTE, $anio);

        return sprintf('CMP-%04d-%06d', $anio, $n);
    }

    /** Número de serie de documento emitido con formato DOC-AAAA-NNNNNN. */
    public function numeroSerie(?int $anio = null): string
    {
        $anio = $anio ?? (int) now()->year;
        $n = $this->siguiente(self::SERIE_DOC, $anio);

        return sprintf('DOC-%04d-%06d', $anio, $n);
    }
}
