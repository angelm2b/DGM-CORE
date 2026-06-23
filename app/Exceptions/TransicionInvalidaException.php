<?php

declare(strict_types=1);

namespace App\Exceptions;

class TransicionInvalidaException extends DominioException
{
    public function status(): int
    {
        return 409;
    }

    public function tipo(): string
    {
        return 'https://dgm.gob.do/problems/transicion-invalida';
    }

    public function titulo(): string
    {
        return 'Transición de estado no permitida';
    }
}
