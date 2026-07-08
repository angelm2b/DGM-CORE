<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Solicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/** Consulta de solicitudes (solo lectura desde el panel). */
class SolicitudAdminController extends Controller
{
    public function index(Request $request)
    {
        $solicitudes = Solicitud::with(['servicio', 'persona', 'expediente'])
            ->when($request->query('estado'), fn ($q, $estado) => $q->where('estado_actual', $estado))
            ->when($request->query('buscar'), function ($q, $texto) {
                $q->whereHas('expediente', fn ($e) => $e->where('numero_expediente', 'like', "%{$texto}%"))
                    ->orWhereHas('persona', fn ($p) => $p
                        ->where('numero_documento', 'like', "%{$texto}%")
                        ->orWhere('apellidos', 'like', "%{$texto}%"));
            })
            ->latest('fecha_ultima_accion')
            ->paginate(25)
            ->withQueryString();

        $estados = Solicitud::query()
            ->select('estado_actual')
            ->distinct()
            ->pluck('estado_actual')
            ->map(fn ($e) => $e->getValue())
            ->sort()
            ->values();

        return View::make('admin.solicitudes.index', [
            'solicitudes' => $solicitudes,
            'estados' => $estados,
            'estadoFiltro' => (string) $request->query('estado'),
            'buscar' => (string) $request->query('buscar'),
        ]);
    }

    public function detalle(Solicitud $solicitud)
    {
        $solicitud->load([
            'servicio', 'oficina', 'persona', 'expediente',
            'estados' => fn ($q) => $q->with('usuario')->latest(),
            'adjuntos', 'documentosEmitidos',
            'ordenesPago' => fn ($q) => $q->latest('fecha_emision'),
        ]);

        return View::make('admin.solicitudes.detalle', ['solicitud' => $solicitud]);
    }
}
