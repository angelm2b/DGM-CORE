<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Construye respuestas de error estandarizadas estilo problem+json
 * (RFC 7807) incluyendo siempre el correlationId del request.
 */
final class RespuestaProblema
{
    /**
     * @param  array<string, mixed>  $extra
     */
    public static function desde(
        Request $request,
        int $status,
        string $title,
        string $detail,
        string $type = 'about:blank',
        array $extra = [],
    ): JsonResponse {
        $correlationId = $request->attributes->get('correlation_id')
            ?? $request->header('X-Correlation-Id')
            ?? (string) Str::uuid();

        $cuerpo = array_merge([
            'type' => $type,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'correlationId' => $correlationId,
        ], $extra);

        return new JsonResponse(
            data: $cuerpo,
            status: $status,
            headers: [
                'Content-Type' => 'application/problem+json',
                'X-Correlation-Id' => $correlationId,
            ],
        );
    }
}
