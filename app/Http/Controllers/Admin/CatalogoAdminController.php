<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Servicio;
use App\Models\Tarifa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;

/** Administración de catálogos: servicios y tarifas. */
class CatalogoAdminController extends Controller
{
    /** Conceptos tarifables (enum de la tabla tarifas). */
    private const CONCEPTOS = [
        'DEPOSITO_EXPEDIENTE',
        'CARNET',
        'PENALIDAD_MES',
        'TASA_ESTADIA',
        'REENTRADA',
    ];

    public function servicios()
    {
        return View::make('admin.servicios.index', [
            'servicios' => Servicio::with('categoriaMigratoria')
                ->withCount('tarifas')
                ->orderBy('codigo')
                ->paginate(25),
        ]);
    }

    /** Habilita o deshabilita un servicio del catálogo. */
    public function alternarServicio(Servicio $servicio): RedirectResponse
    {
        $servicio->update(['activo' => ! $servicio->activo]);

        return back()->with('exito', $servicio->activo
            ? "Servicio {$servicio->codigo} activado."
            : "Servicio {$servicio->codigo} desactivado.");
    }

    public function tarifas(Request $request)
    {
        $tarifas = Tarifa::with('servicio')
            ->when($request->query('servicio'), fn ($q, $id) => $q->where('servicio_id', $id))
            ->orderByDesc('vigente_desde')
            ->paginate(25)
            ->withQueryString();

        return View::make('admin.tarifas.index', [
            'tarifas' => $tarifas,
            'servicios' => Servicio::orderBy('codigo')->get(),
            'servicioFiltro' => (int) $request->query('servicio'),
        ]);
    }

    public function crearTarifa()
    {
        return View::make('admin.tarifas.crear', [
            'servicios' => Servicio::orderBy('codigo')->get(),
            'conceptos' => self::CONCEPTOS,
        ]);
    }

    public function guardarTarifa(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'servicio_id' => ['required', 'integer', 'exists:servicios,id'],
            'concepto' => ['required', 'string', Rule::in(self::CONCEPTOS)],
            'monto' => ['required', 'numeric', 'min:0'],
            'moneda' => ['required', 'string', 'size:3'],
            'vigente_desde' => ['required', 'date'],
            'vigente_hasta' => ['nullable', 'date', 'after:vigente_desde'],
            'resolucion' => ['nullable', 'string', 'max:255'],
        ]);

        Tarifa::create($datos);

        return redirect('/admin/tarifas')->with('exito', 'Tarifa registrada correctamente.');
    }
}
