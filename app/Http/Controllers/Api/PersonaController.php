<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CrearPersonaRequest;
use App\Http\Resources\PersonaResource;
use App\Models\Persona;
use Illuminate\Http\Request;

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
