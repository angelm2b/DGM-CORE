<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\DocumentoEmitido;
use App\Models\Persona;
use App\Models\Solicitud;
use App\Support\Dinero;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Reglas de elegibilidad del negocio. Cada método lanza ReglaNegocioException
 * (regla incumplida) o retorna el resultado correspondiente.
 */
class ElegibilidadService
{
    /**
     * RN-01: la estadía total de un turista (base 30 días + prórrogas) no puede
     * superar los 120 días.
     */
    public function validarProrrogaTurista(int $diasProrrogadosPrevios, int $diasSolicitados): void
    {
        $base = (int) config('dgm.reglas.turista_dias_base', 30);
        $max = (int) config('dgm.reglas.turista_dias_max_prorroga', 120);

        $totalStay = $base + $diasProrrogadosPrevios + $diasSolicitados;

        if ($diasSolicitados <= 0) {
            throw new ReglaNegocioException('Los días solicitados de prórroga deben ser mayores a cero.', 'RN-01');
        }

        if ($totalStay > $max) {
            throw new ReglaNegocioException(
                "La estadía total ({$totalStay} días) excede el máximo de {$max} días permitido para turista.",
                'RN-01',
            );
        }
    }

    /**
     * RN-03: una renovación debe solicitarse con al menos 45 días de antelación
     * al vencimiento.
     */
    public function validarAntelacionRenovacion(CarbonInterface $fechaVencimiento, ?CarbonInterface $fechaSolicitud = null): void
    {
        $antelacion = (int) config('dgm.reglas.renovacion_antelacion_dias', 45);
        $solicitud = $fechaSolicitud ? Carbon::parse($fechaSolicitud) : Carbon::now();
        $vencimiento = Carbon::parse($fechaVencimiento);

        $diasDisponibles = $solicitud->startOfDay()->diffInDays($vencimiento->startOfDay(), false);

        if ($diasDisponibles < $antelacion) {
            throw new ReglaNegocioException(
                "La renovación debe solicitarse al menos {$antelacion} días antes del vencimiento (faltan {$diasDisponibles}).",
                'RN-03',
            );
        }
    }

    /**
     * RN-05: solo una persona RT-9 puede cambiar a RP-1 dentro del país, y debe
     * tener su primer carnet RT-9 emitido (y vigente/repuesto) como evidencia de
     * las renovaciones anuales cumplidas.
     */
    public function validarCambioCategoria(Persona $persona, string $codigoDestino): void
    {
        $origen = $persona->categoriaMigratoria;

        if (! $origen || $origen->codigo !== 'RT-9') {
            throw new ReglaNegocioException('Solo una persona en categoría RT-9 puede cambiar de categoría dentro del país.', 'RN-05');
        }

        if ($codigoDestino !== 'RP-1' || $origen->permiteCambioA?->codigo !== 'RP-1') {
            throw new ReglaNegocioException('El único cambio de categoría permitido es RT-9 a RP-1.', 'RN-05');
        }

        $tieneCarnetRt9 = DocumentoEmitido::query()
            ->whereHas('solicitud.expediente', fn ($q) => $q->where('persona_id', $persona->id))
            ->where('tipo', 'CARNET_RT9')
            ->whereIn('estado', ['VIGENTE', 'REPUESTO'])
            ->exists();

        if (! $tieneCarnetRt9) {
            throw new ReglaNegocioException('Se requiere el primer carnet RT-9 emitido y las renovaciones anuales cumplidas.', 'RN-05');
        }
    }

    /**
     * RN-06: vigencia del carnet RP-1: el primero es por 1 año; los siguientes
     * por 4 años. Devuelve los años de vigencia que corresponden a la próxima
     * emisión para la persona.
     */
    public function vigenciaCarnetRP1(string $personaId): int
    {
        $yaTieneRp1 = DocumentoEmitido::query()
            ->whereHas('solicitud.expediente', fn ($q) => $q->where('persona_id', $personaId))
            ->where('tipo', 'CARNET_RP1')
            ->exists();

        return $yaTieneRp1 ? 4 : 1;
    }

    /**
     * RN-08: una renovación exige un adjunto POLIZA validado.
     */
    public function validarPolizaRenovacion(Solicitud $solicitud): void
    {
        $tienePoliza = $solicitud->adjuntos()
            ->where('tipo_documento', 'POLIZA')
            ->where('validado', true)
            ->exists();

        if (! $tienePoliza) {
            throw new ReglaNegocioException('La renovación requiere una POLIZA adjunta y validada.', 'RN-08');
        }
    }

    /**
     * RN-09: las personas menores de 18 años requieren el flujo de certificación
     * de salida de menores.
     */
    public function requiereCertificacionMenor(Persona $persona, ?CarbonInterface $aFecha = null): bool
    {
        return $persona->esMenorDeEdad();
    }

    /**
     * RN-10: el pasaporte debe tener vigencia de al menos 6 meses respecto a la
     * fecha de solicitud.
     */
    public function validarVigenciaPasaporte(Persona $persona, ?CarbonInterface $fechaSolicitud = null): void
    {
        $meses = (int) config('dgm.reglas.pasaporte_vigencia_min_meses', 6);
        $solicitud = $fechaSolicitud ? Carbon::parse($fechaSolicitud) : Carbon::now();

        if ($persona->pasaporte_vence === null) {
            throw new ReglaNegocioException('No se registró la fecha de vencimiento del pasaporte.', 'RN-10');
        }

        $minimo = $solicitud->copy()->addMonths($meses);

        if ($persona->pasaporte_vence->lessThan($minimo)) {
            throw new ReglaNegocioException(
                "El pasaporte debe tener vigencia de al menos {$meses} meses a la fecha de solicitud.",
                'RN-10',
            );
        }
    }

    /**
     * RN-11: los adjuntos solo se aceptan en formato JPG.
     */
    public function validarFormatoJpg(string $formato): void
    {
        if (strtoupper($formato) !== 'JPG') {
            throw new ReglaNegocioException('Los adjuntos solo se aceptan en formato JPG.', 'RN-11');
        }
    }

    /**
     * RN-12: una solicitud RT-9 de persona casada con dominicano(a) exige
     * solvencia económica de al menos RD$150,000.
     */
    public function validarSolvenciaRT9(bool $casadoConDominicano, string $solvenciaDeclarada): void
    {
        if (! $casadoConDominicano) {
            return;
        }

        $minima = Dinero::normalizar((string) config('dgm.reglas.solvencia_minima', '150000.00'));

        if (! Dinero::esMayorIgual(Dinero::normalizar($solvenciaDeclarada), $minima)) {
            throw new ReglaNegocioException(
                "Para RT-9 casado(a) con dominicano(a) se exige solvencia mínima de RD\${$minima}.",
                'RN-12',
            );
        }
    }
}
