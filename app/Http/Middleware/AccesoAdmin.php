<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege el panel de administración (/admin): exige sesión iniciada con un
 * usuario activo cuyo rol sea ADMIN_DGM. Cualquier otro caso vuelve al login.
 */
class AccesoAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = Auth::guard('web')->user();

        if (! $usuario) {
            return redirect('/admin/login');
        }

        if (! $usuario->activo || ! $usuario->tieneRol('ADMIN_DGM')) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/admin/login')
                ->withErrors(['email' => 'Tu cuenta no tiene acceso al panel de administración.']);
        }

        return $next($request);
    }
}
