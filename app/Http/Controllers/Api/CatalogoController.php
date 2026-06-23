<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoriaResource;
use App\Http\Resources\PuntoControlResource;
use App\Http\Resources\ServicioResource;
use App\Http\Resources\TarifaResource;
use App\Models\CategoriaMigratoria;
use App\Models\PuntoControl;
use App\Models\Servicio;
use App\Models\Tarifa;
use Illuminate\Http\Request;

class CatalogoController extends Controller
{
    /** Catálogo de servicios. */
    public function servicios()
    {
        return ServicioResource::collection(Servicio::orderBy('codigo')->get());
    }

    /** Catálogo de tarifas (opcionalmente ?servicio_id= y ?vigentes=1). */
    public function tarifas(Request $request)
    {
        $tarifas = Tarifa::query()
            ->when($request->filled('servicio_id'), fn ($q) => $q->where('servicio_id', $request->integer('servicio_id')))
            ->when($request->boolean('vigentes'), fn ($q) => $q->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', now()))
            ->orderBy('servicio_id')
            ->get();

        return TarifaResource::collection($tarifas);
    }

    /** Catálogo de categorías migratorias. */
    public function categorias()
    {
        return CategoriaResource::collection(
            CategoriaMigratoria::with('permiteCambioA')->orderBy('codigo')->get()
        );
    }

    /** Catálogo de puntos de control. */
    public function puntosControl()
    {
        return PuntoControlResource::collection(PuntoControl::orderBy('codigo')->get());
    }
}
