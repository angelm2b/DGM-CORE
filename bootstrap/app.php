<?php

use App\Exceptions\DominioException;
use App\Http\Middleware\AsignarCorrelationId;
use App\Http\Middleware\ForzarJson;
use App\Http\Middleware\SinCacheNavegador;
use App\Http\Middleware\VerificarApiEncendida;
use App\Http\Middleware\VerificarPermiso;
use App\Support\RespuestaProblema;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'core/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Toda la API interna fuerza JSON y propaga el X-Correlation-Id.
        // El apagador general va primero: con la API apagada nada más se evalúa.
        $middleware->api(prepend: [
            VerificarApiEncendida::class,
            AsignarCorrelationId::class,
            ForzarJson::class,
        ]);

        // Las páginas web (home, login y documentación) no se cachean en el
        // navegador, para que no queden accesibles tras cerrar sesión.
        $middleware->web(append: [
            SinCacheNavegador::class,
        ]);

        $middleware->alias([
            'permiso' => VerificarPermiso::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Errores estandarizados estilo problem+json para la API interna.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('core/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! ($request->is('core/*') || $request->expectsJson())) {
                return null;
            }

            return RespuestaProblema::desde(
                request: $request,
                status: 422,
                title: 'Datos de entrada inválidos',
                detail: 'La solicitud contiene errores de validación.',
                type: 'https://dgm.gob.do/problems/validacion',
                extra: ['errors' => $e->errors()],
            );
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! ($request->is('core/*') || $request->expectsJson())) {
                return null;
            }

            return RespuestaProblema::desde(
                request: $request,
                status: 401,
                title: 'No autenticado',
                detail: 'Se requiere un token válido del integrador.',
                type: 'https://dgm.gob.do/problems/no-autenticado',
            );
        });

        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) {
            if (! ($request->is('core/*') || $request->expectsJson())) {
                return null;
            }

            return RespuestaProblema::desde(
                request: $request,
                status: 404,
                title: 'Recurso no encontrado',
                detail: 'El recurso solicitado no existe.',
                type: 'https://dgm.gob.do/problems/no-encontrado',
            );
        });

        $exceptions->render(function (DominioException $e, Request $request) {
            if (! ($request->is('core/*') || $request->expectsJson())) {
                return null;
            }

            return RespuestaProblema::desde(
                request: $request,
                status: $e->status(),
                title: $e->titulo(),
                detail: $e->getMessage(),
                type: $e->tipo(),
            );
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! ($request->is('core/*') || $request->expectsJson())) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            return RespuestaProblema::desde(
                request: $request,
                status: $status,
                title: $status >= 500 ? 'Error interno' : 'Error de solicitud',
                detail: config('app.debug') || $status < 500
                    ? $e->getMessage()
                    : 'Ocurrió un error procesando la solicitud.',
                type: 'https://dgm.gob.do/problems/error',
            );
        });
    })->create();
