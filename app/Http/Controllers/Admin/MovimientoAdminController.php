<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MovimientoMigratorio;
use App\Models\PuntoControl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/** Consulta de movimientos migratorios (solo lectura desde el panel). */
class MovimientoAdminController extends Controller
{
    public function index(Request $request)
    {
        $movimientos = MovimientoMigratorio::with(['persona', 'puntoControl', 'oficial'])
            ->when($request->query('tipo'), fn ($q, $tipo) => $q->where('tipo', $tipo))
            ->when($request->query('punto'), fn ($q, $punto) => $q->where('punto_control_id', $punto))
            ->when($request->query('buscar'), function ($q, $texto) {
                $q->whereHas('persona', fn ($p) => $p
                    ->where('numero_documento', 'like', "%{$texto}%")
                    ->orWhere('apellidos', 'like', "%{$texto}%"));
            })
            ->latest('fecha_hora')
            ->paginate(25)
            ->withQueryString();

        $puntosControl = PuntoControl::orderBy('nombre')->get(['id', 'nombre']);

        return View::make('admin.movimientos.index', [
            'movimientos' => $movimientos,
            'puntosControl' => $puntosControl,
            'tipoFiltro' => (string) $request->query('tipo'),
            'puntoFiltro' => (string) $request->query('punto'),
            'buscar' => (string) $request->query('buscar'),
        ]);
    }
}
