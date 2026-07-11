<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Ajuste;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Apagador general de la API (/core/v1). Cuando está apagada, toda petición
 * recibe un 503 sin cuerpo — como si el servicio estuviera fuera de línea —
 * sin evaluar token ni permisos. El panel /admin no pasa por aquí, así que
 * siempre se puede volver a encender desde el navegador.
 */
class VerificarApiEncendida
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Ajuste::apiEncendida()) {
            return response('', 503, ['Retry-After' => '3600']);
        }

        return $next($request);
    }
}
