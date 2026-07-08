<?php

use App\Http\Controllers\Admin\AccesoAdminController;
use App\Http\Controllers\Admin\AuditoriaAdminController;
use App\Http\Controllers\Admin\CatalogoAdminController;
use App\Http\Controllers\Admin\PanelAdminController;
use App\Http\Controllers\Admin\SolicitudAdminController;
use App\Http\Controllers\Admin\UsuarioAdminController;
use App\Http\Controllers\DocsAccesoController;
use App\Http\Middleware\AccesoAdmin;
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

/*
|--------------------------------------------------------------------------
| Panel de administración — /admin
|--------------------------------------------------------------------------
| Sesión web (guard "web") restringida al rol ADMIN_DGM. El login usa las
| mismas cuentas de la tabla usuarios; una cuenta inactiva no puede entrar.
*/

Route::prefix('admin')->group(function () {
    // Acceso
    Route::get('/login', [AccesoAdminController::class, 'mostrar']);
    // throttle: máx. 5 intentos por minuto y por IP, para frenar fuerza bruta.
    Route::post('/login', [AccesoAdminController::class, 'autenticar'])->middleware('throttle:5,1');
    Route::post('/salir', [AccesoAdminController::class, 'salir']);

    Route::middleware(AccesoAdmin::class)->group(function () {
        Route::get('/', [PanelAdminController::class, 'index']);

        // Usuarios internos
        Route::get('/usuarios', [UsuarioAdminController::class, 'index']);
        Route::get('/usuarios/crear', [UsuarioAdminController::class, 'crear']);
        Route::post('/usuarios', [UsuarioAdminController::class, 'guardar']);
        Route::get('/usuarios/{usuario}/editar', [UsuarioAdminController::class, 'editar']);
        Route::put('/usuarios/{usuario}', [UsuarioAdminController::class, 'actualizar']);
        Route::post('/usuarios/{usuario}/alternar-activo', [UsuarioAdminController::class, 'alternarActivo']);

        // Catálogos: servicios y tarifas
        Route::get('/servicios', [CatalogoAdminController::class, 'servicios']);
        Route::post('/servicios/{servicio}/alternar-activo', [CatalogoAdminController::class, 'alternarServicio']);
        Route::get('/tarifas', [CatalogoAdminController::class, 'tarifas']);
        Route::get('/tarifas/crear', [CatalogoAdminController::class, 'crearTarifa']);
        Route::post('/tarifas', [CatalogoAdminController::class, 'guardarTarifa']);

        // Solicitudes (solo lectura)
        Route::get('/solicitudes', [SolicitudAdminController::class, 'index']);
        Route::get('/solicitudes/{solicitud}', [SolicitudAdminController::class, 'detalle']);

        // Auditoría (solo lectura)
        Route::get('/auditoria', [AuditoriaAdminController::class, 'index']);
    });
});
