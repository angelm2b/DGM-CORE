<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Incumplimiento de una regla de elegibilidad/negocio (RN-01..RN-12).
 * Permite asociar el código de la regla violada.
 */
class ReglaNegocioException extends DominioException
{
    public function __construct(string $mensaje, public readonly ?string $regla = null)
    {
        parent::__construct($mensaje);
    }

    public function tipo(): string
    {
        return 'https://dgm.gob.do/problems/regla-negocio'.($this->regla ? '#'.strtolower($this->regla) : '');
    }
}
