<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use OwenIt\Auditing\Models\Audit;

/** Consulta del registro de auditoría (owen-it/laravel-auditing). */
class AuditoriaAdminController extends Controller
{
    public function index(Request $request)
    {
        $auditorias = Audit::query()
            ->with('user')
            ->when($request->query('evento'), fn ($q, $evento) => $q->where('event', $evento))
            ->when($request->query('modelo'), fn ($q, $modelo) => $q->where('auditable_type', 'like', "%{$modelo}%"))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return View::make('admin.auditoria.index', [
            'auditorias' => $auditorias,
            'eventoFiltro' => (string) $request->query('evento'),
            'modeloFiltro' => (string) $request->query('modelo'),
        ]);
    }
}
