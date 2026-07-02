<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\ReglaNegocioException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ActualizarPersonaRequest;
use App\Http\Requests\CrearPersonaRequest;
use App\Http\Resources\PersonaResource;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PersonaController extends Controller
{
    /** Registra una persona. */
    public function store(CrearPersonaRequest $request): PersonaResource
    {
        $persona = Persona::create($request->validated());

        return PersonaResource::make($persona->load('categoriaMigratoria'));
    }

    /** Consulta una persona por id. */
    public function show(Persona $persona): PersonaResource
    {
        return PersonaResource::make($persona->load('categoriaMigratoria'));
    }

    /** Actualiza los datos de una persona (actualización parcial). */
    public function update(ActualizarPersonaRequest $request, Persona $persona): PersonaResource
    {
        $persona->update($request->validated());

        return PersonaResource::make($persona->load('categoriaMigratoria'));
    }

    /** Elimina una persona sin expedientes ni movimientos asociados. */
    public function destroy(Persona $persona): Response
    {
        if ($persona->expedientes()->exists() || $persona->movimientos()->exists()) {
            throw new ReglaNegocioException(
                'No se puede eliminar la persona: tiene expedientes o movimientos migratorios asociados.'
            );
        }

        $persona->delete();

        return response()->noContent();
    }

    /** Búsqueda por documento (?documento=&tipo=&nacionalidad=). */
    public function index(Request $request)
    {
        $personas = Persona::query()
            ->when($request->filled('documento'), fn ($q) => $q->where('numero_documento', $request->string('documento')))
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo_documento', $request->string('tipo')))
            ->when($request->filled('nacionalidad'), fn ($q) => $q->where('nacionalidad', $request->string('nacionalidad')))
            ->with('categoriaMigratoria')
            ->limit(100)
            ->get();

        return PersonaResource::collection($personas);
    }
}
