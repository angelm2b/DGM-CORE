<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Protege la documentación de la API (Scramble) en entornos no-local. */
class AccesoDocumentacion
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local') || session('docs_admin_ok') === true) {
            return $next($request);
        }

        return redirect('/docs-acceso?next='.urlencode('/'.$request->path()));
    }
}
