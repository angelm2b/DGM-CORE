<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Excepción base de reglas de negocio del dominio. El manejador de
 * excepciones la convierte en una respuesta problem+json.
 */
abstract class DominioException extends RuntimeException
{
    /** Código HTTP asociado. */
    public function status(): int
    {
        return 422;
    }

    /** URI del tipo de problema (problem+json). */
    public function tipo(): string
    {
        return 'https://dgm.gob.do/problems/regla-negocio';
    }

    /** Título legible del problema. */
    public function titulo(): string
    {
        return 'Regla de negocio incumplida';
    }
}
