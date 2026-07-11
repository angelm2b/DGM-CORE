<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ajuste;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\View;

/** Página de sistema: apagador general de la API interna. */
class SistemaAdminController extends Controller
{
    public function index()
    {
        return View::make('admin.sistema', [
            'apiEncendida' => Ajuste::apiEncendida(),
        ]);
    }

    /** Apagador general: enciende o apaga la API interna (queda auditado). */
    public function alternarApi(): RedirectResponse
    {
        $encendida = Ajuste::alternarApi();

        return back()->with('exito', $encendida
            ? 'API encendida: el integrador vuelve a tener servicio.'
            : 'API apagada: toda petición a /core/v1 recibe 503, como si el servicio estuviera fuera de línea.');
    }
}
