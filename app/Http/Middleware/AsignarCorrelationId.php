<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garantiza que cada request tenga un X-Correlation-Id: lo toma del
 * encabezado entrante o genera uno nuevo, lo expone en los atributos
 * del request (para logs y problem+json) y lo propaga en la respuesta.
 */
class AsignarCorrelationId
{
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $request->header('X-Correlation-Id');

        if (! is_string($correlationId) || trim($correlationId) === '') {
            $correlationId = (string) Str::uuid();
        }

        $request->attributes->set('correlation_id', $correlationId);
        $request->headers->set('X-Correlation-Id', $correlationId);

        // Contexto disponible para todos los logs del request.
        Log::withContext(['correlationId' => $correlationId]);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Correlation-Id', $correlationId);

        return $response;
    }
}
