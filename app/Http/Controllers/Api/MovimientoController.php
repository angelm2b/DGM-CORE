<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarMovimientoRequest;
use App\Http\Resources\MovimientoResource;
use App\Models\MovimientoMigratorio;
use App\Services\MovimientoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MovimientoController extends Controller
{
    public function __construct(private readonly MovimientoService $movimientos) {}

    /**
     * Registra un movimiento migratorio. En las salidas (tipo=S) calcula la
     * sobreestadía y, si existe, la incluye en la respuesta.
     */
    public function store(RegistrarMovimientoRequest $request): JsonResponse
    {
        $resultado = $this->movimientos->registrar($request->validated());

        $datos = MovimientoResource::make($resultado['movimiento'])->resolve();
        if ($resultado['sobreestadia'] !== null) {
            $datos['sobreestadia'] = $resultado['sobreestadia'];
        }

        return new JsonResponse(['data' => $datos], 201);
    }

    /** Lista los movimientos de una persona (?persona_id=). */
    public function index(Request $request)
    {
        $request->validate(['persona_id' => ['required', 'uuid', 'exists:personas,id']]);

        $movimientos = MovimientoMigratorio::query()
            ->where('persona_id', $request->string('persona_id'))
            ->orderByDesc('fecha_hora')
            ->limit(200)
            ->get();

        return MovimientoResource::collection($movimientos);
    }
}
