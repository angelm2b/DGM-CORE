<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

/** Login y logout del panel de administración. */
class AccesoAdminController extends Controller
{
    /** Muestra el formulario de acceso (o entra directo si ya hay sesión admin). */
    public function mostrar()
    {
        $usuario = Auth::guard('web')->user();

        if ($usuario && $usuario->activo && $usuario->tieneRol('ADMIN_DGM')) {
            return redirect('/admin');
        }

        return View::make('admin.login');
    }

    /** Valida credenciales y exige el rol ADMIN_DGM. */
    public function autenticar(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // "activo" forma parte de las credenciales: una cuenta desactivada
        // no puede iniciar sesión aunque la contraseña sea correcta.
        if (! Auth::guard('web')->attempt($credenciales + ['activo' => true])) {
            return back()
                ->withErrors(['email' => 'Credenciales incorrectas o cuenta inactiva.'])
                ->withInput(['email' => $credenciales['email']]);
        }

        if (! Auth::guard('web')->user()->tieneRol('ADMIN_DGM')) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors(['email' => 'Tu cuenta no tiene acceso al panel de administración.'])
                ->withInput(['email' => $credenciales['email']]);
        }

        $request->session()->regenerate();

        return redirect()->intended('/admin');
    }

    /** Cierra la sesión del panel. */
    public function salir(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/admin/login');
    }
}
