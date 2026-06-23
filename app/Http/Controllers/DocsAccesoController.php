<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/** Puerta de acceso a la documentación de la API en producción. */
class DocsAccesoController extends Controller
{
    /** Muestra el formulario de acceso (o entra directo si ya está autorizado). */
    public function mostrar(Request $request)
    {
        $destino = $this->destinoSeguro($request->query('next'));

        if (session('docs_admin_ok') === true) {
            return redirect($destino);
        }

        return View::make('docs-acceso', ['next' => $destino]);
    }

    /** Valida la clave admin y autoriza la sesión. */
    public function autenticar(Request $request): RedirectResponse
    {
        $clave = (string) config('dgm.docs_password', '');
        $enviada = (string) $request->input('clave', '');
        $destino = $this->destinoSeguro($request->input('next'));

        if ($clave !== '' && hash_equals($clave, $enviada)) {
            $request->session()->put('docs_admin_ok', true);

            return redirect($destino);
        }

        return back()->withErrors(['clave' => 'Clave incorrecta.'])->withInput(['next' => $destino]);
    }

    private function destinoSeguro(?string $next): string
    {
        $next = (string) $next;

        if ($next !== '' && str_starts_with($next, '/') && ! str_starts_with($next, '//')) {
            return $next;
        }

        return '/';
    }

    /** Revoca el acceso a la documentación. */
    public function salir(Request $request): RedirectResponse
    {
        $request->session()->forget('docs_admin_ok');

        return redirect('/');
    }
}
