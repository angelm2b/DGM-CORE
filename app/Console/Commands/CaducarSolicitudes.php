<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Solicitud;
use App\Services\SolicitudService;
use App\States\Solicitud\Anulada;
use App\States\Solicitud\Caducada;
use App\States\Solicitud\Entregado;
use App\States\Solicitud\Rechazada;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * RN-07: caduca toda solicitud en estado activo cuya última acción tenga más
 * de N días calendario (config dgm.caducidad_dias, por defecto 90).
 */
class CaducarSolicitudes extends Command
{
    protected $signature = 'solicitudes:caducar {--dias= : Sobrescribe los días de caducidad configurados}';

    protected $description = 'Marca como CADUCADA las solicitudes activas inactivas por más de N días';

    public function handle(SolicitudService $solicitudes): int
    {
        $dias = (int) ($this->option('dias') ?: config('dgm.caducidad_dias', 90));
        $limite = Carbon::now()->subDays($dias);

        // Estados terminales que NO deben caducar.
        $terminales = collect([Entregado::class, Rechazada::class, Caducada::class, Anulada::class])
            ->map(fn ($clase) => $clase::$name)
            ->all();

        $caducables = Solicitud::query()
            ->whereNotIn('estado_actual', $terminales)
            ->where('fecha_ultima_accion', '<', $limite)
            ->get();

        $contador = 0;
        foreach ($caducables as $solicitud) {
            $solicitudes->transicionar($solicitud, 'CADUCADA', 'CORE', motivo: "Inactividad mayor a {$dias} días");

            $contador++;
        }

        $this->info("Solicitudes caducadas: {$contador}.");

        return self::SUCCESS;
    }
}
