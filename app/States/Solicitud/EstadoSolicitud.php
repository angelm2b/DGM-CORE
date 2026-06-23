<?php

declare(strict_types=1);

namespace App\States\Solicitud;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * Máquina de estados de la solicitud (spatie/laravel-model-states).
 *
 * Transiciones de éxito:
 *   BORRADOR -> ENVIADA -> EN_DEPURACION -> APROBADA_PAGO_PENDIENTE
 *   -> PAGADA -> EN_PROCESO -> APROBADA -> DOCUMENTO_EMITIDO -> ENTREGADO
 * Ciclo de observación: EN_DEPURACION <-> DOCS_OBSERVADOS
 * Desde cualquier estado activo: -> RECHAZADA | CADUCADA | ANULADA
 */
abstract class EstadoSolicitud extends State
{
    /** Estados terminales (no admiten más transiciones). */
    public const TERMINALES = [
        Entregado::class,
        Rechazada::class,
        Caducada::class,
        Anulada::class,
    ];

    public static function config(): StateConfig
    {
        $config = parent::config()
            ->default(Borrador::class)
            ->allowTransition(Borrador::class, Enviada::class)
            ->allowTransition(Enviada::class, EnDepuracion::class)
            ->allowTransition(EnDepuracion::class, DocsObservados::class)
            ->allowTransition(DocsObservados::class, EnDepuracion::class)
            ->allowTransition(EnDepuracion::class, AprobadaPagoPendiente::class)
            ->allowTransition(AprobadaPagoPendiente::class, Pagada::class)
            ->allowTransition(Pagada::class, EnProceso::class)
            ->allowTransition(EnProceso::class, Aprobada::class)
            ->allowTransition(Aprobada::class, DocumentoEmitido::class)
            ->allowTransition(DocumentoEmitido::class, Entregado::class);

        // Cualquier estado activo puede ir a RECHAZADA, CADUCADA o ANULADA.
        $activos = [
            Borrador::class,
            Enviada::class,
            EnDepuracion::class,
            DocsObservados::class,
            AprobadaPagoPendiente::class,
            Pagada::class,
            EnProceso::class,
            Aprobada::class,
            DocumentoEmitido::class,
        ];

        foreach ($activos as $estado) {
            $config->allowTransition($estado, Rechazada::class)
                ->allowTransition($estado, Caducada::class)
                ->allowTransition($estado, Anulada::class);
        }

        return $config;
    }

    /** Indica si el estado es terminal (no admite más transiciones). */
    public function esTerminal(): bool
    {
        return in_array(static::class, self::TERMINALES, true);
    }

    /** Indica si el estado es activo (puede caducar, RN-07). */
    public function esActivo(): bool
    {
        return ! $this->esTerminal();
    }
}
