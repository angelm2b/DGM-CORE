<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Aritmética monetaria con bcmath, escala fija de 2 decimales.
 * Nunca se usan floats para dinero.
 */
final class Dinero
{
    public const ESCALA = 2;

    public static function normalizar(string|int|float $monto): string
    {
        return bcadd((string) $monto, '0', self::ESCALA);
    }

    public static function sumar(string $a, string $b): string
    {
        return bcadd($a, $b, self::ESCALA);
    }

    public static function restar(string $a, string $b): string
    {
        return bcsub($a, $b, self::ESCALA);
    }

    public static function multiplicar(string $monto, string|int $factor): string
    {
        return bcmul($monto, (string) $factor, self::ESCALA);
    }

    /** Devuelve -1, 0 o 1 comparando a con b. */
    public static function comparar(string $a, string $b): int
    {
        return bccomp($a, $b, self::ESCALA);
    }

    public static function esMayorIgual(string $a, string $b): bool
    {
        return self::comparar($a, $b) >= 0;
    }

    public static function esCero(string $a): bool
    {
        return self::comparar($a, '0') === 0;
    }
}
