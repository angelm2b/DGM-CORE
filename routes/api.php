<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CalculoController;
use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\DocumentoController;
use App\Http\Controllers\Api\MovimientoController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\PersonaController;
use App\Http\Controllers\Api\SolicitudController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API interna del CORE — prefijo /core/v1
|--------------------------------------------------------------------------
| Consumida exclusivamente por el Sistema de Integración (cliente
| "integrador"), autenticado con Laravel Sanctum. Cada ruta exige el
| permiso correspondiente del rol del token.
*/

Route::middleware('auth:sanctum')->group(function () {
    // Personas
    Route::post('/personas', [PersonaController::class, 'store'])->middleware('permiso:personas.crear');
    Route::get('/personas', [PersonaController::class, 'index'])->middleware('permiso:personas.ver');
    Route::get('/personas/{persona}', [PersonaController::class, 'show'])->middleware('permiso:personas.ver');

    // Solicitudes
    Route::post('/solicitudes', [SolicitudController::class, 'store'])->middleware('permiso:solicitudes.crear');
    Route::get('/solicitudes/{solicitud}', [SolicitudController::class, 'show'])->middleware('permiso:solicitudes.ver');
    Route::post('/solicitudes/{solicitud}/transicion', [SolicitudController::class, 'transicion'])->middleware('permiso:solicitudes.transicionar');
    Route::post('/solicitudes/{solicitud}/adjuntos', [SolicitudController::class, 'adjuntos'])->middleware('permiso:adjuntos.cargar');

    // Órdenes de pago y pagos
    Route::get('/ordenes-pago/{ordenPago}', [PagoController::class, 'show'])->middleware('permiso:ordenes.ver');
    Route::post('/pagos', [PagoController::class, 'store'])->middleware('permiso:pagos.registrar');

    // Movimientos migratorios
    Route::post('/movimientos', [MovimientoController::class, 'store'])->middleware('permiso:movimientos.registrar');
    Route::get('/movimientos', [MovimientoController::class, 'index'])->middleware('permiso:movimientos.ver');

    // Documentos emitidos
    Route::post('/documentos/emitir', [DocumentoController::class, 'emitir'])->middleware('permiso:documentos.emitir');
    Route::get('/documentos/{numeroSerie}/verificar', [DocumentoController::class, 'verificar'])->middleware('permiso:documentos.verificar');

    // Cálculos
    Route::get('/calculos/tasa-estadia', [CalculoController::class, 'tasaEstadia'])->middleware('permiso:calculos.ver');

    // Catálogos
    Route::middleware('permiso:catalogos.ver')->prefix('catalogos')->group(function () {
        Route::get('/servicios', [CatalogoController::class, 'servicios']);
        Route::get('/tarifas', [CatalogoController::class, 'tarifas']);
        Route::get('/categorias', [CatalogoController::class, 'categorias']);
        Route::get('/puntos-control', [CatalogoController::class, 'puntosControl']);
    });
});
