<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\TransicionInvalidaException;
use App\Models\Expediente;
use App\Models\Solicitud;
use App\Models\SolicitudEstado;
use App\States\Solicitud\EstadoSolicitud;
use Illuminate\Support\Facades\DB;

/**
 * Orquesta el ciclo de vida de la solicitud a través de la máquina de estados.
 */
class SolicitudService
{
    public function __construct(
        private readonly OrdenPagoService $ordenesPago,
        private readonly SecuenciaService $secuencias,
    ) {}

    /**
     * Crea una solicitud. Si no se indica expediente, abre uno para la persona.
     * Registra el estado inicial en el historial.
     *
     * @param  array{persona_id?:string, expediente_id?:string, servicio_id:int, oficina_id:int, canal_origen:string, fecha_cita?:?string, observaciones?:?string}  $datos
     */
    public function crear(array $datos, string $sistemaOrigen = 'INTEGRACION', ?int $usuarioId = null): Solicitud
    {
        return DB::transaction(function () use ($datos, $sistemaOrigen, $usuarioId) {
            $expedienteId = $datos['expediente_id'] ?? null;

            if (! $expedienteId) {
                $expediente = Expediente::create([
                    'persona_id' => $datos['persona_id'],
                    'numero_expediente' => $this->secuencias->numeroExpediente(),
                    'fecha_apertura' => now()->toDateString(),
                    'oficina_id' => $datos['oficina_id'],
                ]);
                $expedienteId = $expediente->id;
            }

            $solicitud = Solicitud::create([
                'expediente_id' => $expedienteId,
                'servicio_id' => $datos['servicio_id'],
                'canal_origen' => $datos['canal_origen'],
                'oficina_id' => $datos['oficina_id'],
                'fecha_cita' => $datos['fecha_cita'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            $this->registrarHistorialInicial($solicitud, $sistemaOrigen, $usuarioId);

            // Refresca para exponer los valores por defecto generados en BD
            // (fecha_creacion, fecha_ultima_accion).
            return $solicitud->refresh();
        });
    }

    /**
     * Ejecuta una transición de estado de forma atómica:
     *  - valida que la transición sea legal,
     *  - registra una fila append-only en solicitud_estados,
     *  - actualiza fecha_ultima_accion (base de la caducidad RN-07).
     *
     * @param  string  $destino  Nombre del estado destino (p. ej. "ENVIADA") o su clase.
     */
    public function transicionar(
        Solicitud $solicitud,
        string $destino,
        string $sistemaOrigen = 'CORE',
        ?int $usuarioId = null,
        ?string $motivo = null,
    ): Solicitud {
        $claseDestino = $this->resolverClaseEstado($destino);
        $nombreDestino = $claseDestino::$name;

        return DB::transaction(function () use ($solicitud, $claseDestino, $nombreDestino, $sistemaOrigen, $usuarioId, $motivo) {
            /** @var EstadoSolicitud $actual */
            $actual = $solicitud->estado_actual;
            $estadoAnterior = $actual->getValue();

            if (! $actual->canTransitionTo($claseDestino)) {
                throw new TransicionInvalidaException(
                    "No se permite la transición de {$estadoAnterior} a {$nombreDestino}."
                );
            }

            $solicitud->estado_actual->transitionTo($claseDestino);
            $solicitud->fecha_ultima_accion = now();
            $solicitud->save();

            SolicitudEstado::create([
                'solicitud_id' => $solicitud->id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $nombreDestino,
                'usuario_id' => $usuarioId,
                'sistema_origen' => $sistemaOrigen,
                'motivo' => $motivo,
            ]);

            // Al aprobar para pago se genera automáticamente la orden de pago.
            if ($nombreDestino === 'APROBADA_PAGO_PENDIENTE') {
                $this->ordenesPago->generarParaSolicitud($solicitud);
            }

            return $solicitud->refresh();
        });
    }

    /**
     * Registra el estado inicial en el historial al crear una solicitud.
     */
    public function registrarHistorialInicial(Solicitud $solicitud, string $sistemaOrigen = 'CORE', ?int $usuarioId = null): void
    {
        SolicitudEstado::create([
            'solicitud_id' => $solicitud->id,
            'estado_anterior' => null,
            'estado_nuevo' => $solicitud->estado_actual->getValue(),
            'usuario_id' => $usuarioId,
            'sistema_origen' => $sistemaOrigen,
            'motivo' => 'Creación de la solicitud',
        ]);
    }

    /**
     * Resuelve el nombre de estado (o clase) a la clase concreta del estado.
     *
     * @return class-string<EstadoSolicitud>
     */
    private function resolverClaseEstado(string $destino): string
    {
        if (is_subclass_of($destino, EstadoSolicitud::class)) {
            return $destino;
        }

        $mapa = EstadoSolicitud::getStateMapping(); // nombre => clase
        $clave = strtoupper($destino);

        if (! $mapa->has($clave)) {
            throw new TransicionInvalidaException("Estado destino desconocido: {$destino}.");
        }

        return $mapa->get($clave);
    }
}
