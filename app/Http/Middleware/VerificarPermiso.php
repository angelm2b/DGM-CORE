<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\RespuestaProblema;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autoriza el acceso a una ruta exigiendo un permiso por su código.
 * Uso: ->middleware('permiso:pagos.registrar')
 */
class VerificarPermiso
{
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! method_exists($usuario, 'tienePermiso') || ! $usuario->tienePermiso($permiso)) {
            return RespuestaProblema::desde(
                request: $request,
                status: 403,
                title: 'Permiso insuficiente',
                detail: "Se requiere el permiso '{$permiso}'.",
                type: 'https://dgm.gob.do/problems/sin-permiso',
            );
        }

        return $next($request);
    }
}
