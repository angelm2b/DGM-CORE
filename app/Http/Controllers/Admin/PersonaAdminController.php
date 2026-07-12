<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/** Consulta de personas y su historial (solo lectura desde el panel). */
class PersonaAdminController extends Controller
{
    public function index(Request $request)
    {
        $personas = Persona::with('categoriaMigratoria')
            ->withCount(['solicitudes', 'movimientos'])
            ->when($request->query('estatus'), fn ($q, $estatus) => $q->where('estatus_migratorio', $estatus))
            ->when($request->query('buscar'), function ($q, $texto) {
                $q->where(fn ($p) => $p
                    ->where('numero_documento', 'like', "%{$texto}%")
                    ->orWhere('nombres', 'like', "%{$texto}%")
                    ->orWhere('apellidos', 'like', "%{$texto}%"));
            })
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(25)
            ->withQueryString();

        return View::make('admin.personas.index', [
            'personas' => $personas,
            'estatusFiltro' => (string) $request->query('estatus'),
            'buscar' => (string) $request->query('buscar'),
        ]);
    }

    public function detalle(Persona $persona)
    {
        $persona->load([
            'categoriaMigratoria',
            'expedientes.oficina',
            'solicitudes' => fn ($q) => $q->with(['servicio', 'expediente'])->latest('fecha_ultima_accion'),
            'movimientos' => fn ($q) => $q->with(['puntoControl', 'oficial'])->latest('fecha_hora'),
        ]);

        return View::make('admin.personas.detalle', ['persona' => $persona]);
    }
}
