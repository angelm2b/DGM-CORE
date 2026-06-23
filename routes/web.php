<?php

use App\Http\Controllers\DocsAccesoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // En producción se exige autenticación antes de ver nada: si no hay sesión
    // admin, se envía primero al login. En local queda abierto para desarrollo.
    if (! app()->environment('local') && session('docs_admin_ok') !== true) {
        return redirect('/docs-acceso?next='.urlencode('/'));
    }

    return view('home');
});

// Acceso admin a la documentación de la API (Scramble) en producción.
Route::get('/docs-acceso', [DocsAccesoController::class, 'mostrar']);
// throttle: máx. 5 intentos por minuto y por IP, para frenar fuerza bruta.
Route::post('/docs-acceso', [DocsAccesoController::class, 'autenticar'])->middleware('throttle:5,1');
Route::get('/docs-acceso/salir', [DocsAccesoController::class, 'salir']);
