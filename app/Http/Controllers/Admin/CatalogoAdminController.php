<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriaMigratoria;
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

    /** Canales por los que puede solicitarse un servicio (enum de la tabla servicios). */
    private const CANALES = ['WEB', 'CAJA', 'AMBOS'];

    public function servicios()
    {
        return View::make('admin.servicios.index', [
            'servicios' => Servicio::with('categoriaMigratoria')
                ->withCount('tarifas')
                ->orderBy('codigo')
                ->paginate(25),
        ]);
    }

    public function crearServicio()
    {
        return View::make('admin.servicios.crear', [
            'categorias' => CategoriaMigratoria::orderBy('codigo')->get(),
            'canales' => self::CANALES,
        ]);
    }

    public function guardarServicio(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:20', 'unique:servicios,codigo'],
            'nombre' => ['required', 'string', 'max:255'],
            'categoria_migratoria_id' => ['nullable', 'integer', 'exists:categorias_migratorias,id'],
            'canal' => ['required', 'string', Rule::in(self::CANALES)],
            'dias_sla' => ['required', 'integer', 'min:0', 'max:65535'],
        ]);

        $datos['requiere_cita'] = $request->boolean('requiere_cita');
        $datos['activo'] = true;

        $servicio = Servicio::create($datos);

        return redirect('/admin/servicios')
            ->with('exito', "Servicio {$servicio->codigo} creado correctamente. Recuerda registrar sus tarifas.");
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
        Tarifa::create($this->validarTarifa($request));

        return redirect('/admin/tarifas')->with('exito', 'Tarifa registrada correctamente.');
    }

    public function editarTarifa(Tarifa $tarifa)
    {
        return View::make('admin.tarifas.editar', [
            'tarifa' => $tarifa,
            'servicios' => Servicio::orderBy('codigo')->get(),
            'conceptos' => self::CONCEPTOS,
        ]);
    }

    /** Actualiza una tarifa (queda auditada en laravel-auditing). */
    public function actualizarTarifa(Request $request, Tarifa $tarifa): RedirectResponse
    {
        $tarifa->update($this->validarTarifa($request));

        return redirect('/admin/tarifas')->with('exito', 'Tarifa actualizada correctamente.');
    }

    /**
     * Elimina una tarifa (queda auditada en laravel-auditing). Las órdenes de
     * pago copian el monto al generarse, así que borrar una tarifa no afecta
     * órdenes ya emitidas.
     */
    public function eliminarTarifa(Tarifa $tarifa): RedirectResponse
    {
        $tarifa->delete();

        return redirect('/admin/tarifas')->with('exito', 'Tarifa eliminada correctamente.');
    }

    /** @return array<string, mixed> */
    private function validarTarifa(Request $request): array
    {
        return $request->validate([
            'servicio_id' => ['required', 'integer', 'exists:servicios,id'],
            'concepto' => ['required', 'string', Rule::in(self::CONCEPTOS)],
            'monto' => ['required', 'numeric', 'min:0'],
            'moneda' => ['required', 'string', 'size:3'],
            'vigente_desde' => ['required', 'date'],
            'vigente_hasta' => ['nullable', 'date', 'after:vigente_desde'],
            'resolucion' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
