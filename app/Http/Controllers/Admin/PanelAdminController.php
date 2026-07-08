<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentoEmitido;
use App\Models\MovimientoMigratorio;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\Solicitud;
use App\Models\Usuario;
use Illuminate\Support\Facades\View;

/** Tablero principal del panel de administración. */
class PanelAdminController extends Controller
{
    public function index()
    {
        $solicitudesPorEstado = Solicitud::query()
            ->selectRaw('estado_actual, count(*) as total')
            ->groupBy('estado_actual')
            ->orderByDesc('total')
            ->pluck('total', 'estado_actual');

        return View::make('admin.panel', [
            'totales' => [
                'personas' => Persona::count(),
                'solicitudes' => Solicitud::count(),
                'pagos' => Pago::count(),
                'documentos' => DocumentoEmitido::count(),
                'movimientos' => MovimientoMigratorio::count(),
                'usuarios_activos' => Usuario::where('activo', true)->count(),
            ],
            'solicitudesPorEstado' => $solicitudesPorEstado,
            'ultimasSolicitudes' => Solicitud::with(['servicio', 'persona'])
                ->latest('fecha_ultima_accion')
                ->limit(8)
                ->get(),
        ]);
    }
}
