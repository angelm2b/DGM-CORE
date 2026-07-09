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
     * Evalúa las reglas de elegibilidad aplicables a una solicitud según su
     * servicio, sin lanzar excepciones: devuelve el resultado de cada regla.
     * RN-01 y RN-12 requieren datos que no viven en la solicitud (días
     * solicitados, estado civil/solvencia), por lo que no se evalúan aquí.
     *
     * @return array{elegible:bool, requiere_certificacion_menor:bool, reglas:list<array{regla:string, descripcion:string, cumple:bool, detalle:?string}>}
     */
    public function evaluarSolicitud(Solicitud $solicitud): array
    {
        $solicitud->loadMissing(['servicio', 'expediente.persona.categoriaMigratoria']);

        $persona = $solicitud->expediente->persona;
        $codigoServicio = $solicitud->servicio?->codigo;
        $fechaSolicitud = $solicitud->fecha_creacion;

        $reglas = [
            $this->evaluarRegla('RN-10', 'Pasaporte con vigencia mínima respecto a la fecha de solicitud',
                fn () => $this->validarVigenciaPasaporte($persona, $fechaSolicitud)),
        ];

        if (in_array($codigoServicio, (array) config('dgm.elegibilidad.servicios_renovacion', []), true)) {
            $reglas[] = $this->evaluarRegla('RN-03', 'Renovación solicitada con la antelación mínima al vencimiento',
                fn () => $this->validarAntelacionRenovacion($this->vencimientoCarnetVigente($persona), $fechaSolicitud));
            $reglas[] = $this->evaluarRegla('RN-08', 'Renovación con PÓLIZA adjunta y validada',
                fn () => $this->validarPolizaRenovacion($solicitud));
        }

        if ($codigoServicio === config('dgm.elegibilidad.servicio_cambio_categoria')) {
            $reglas[] = $this->evaluarRegla('RN-05', 'Cambio de categoría permitido (solo RT-9 a RP-1, con carnet RT-9)',
                fn () => $this->validarCambioCategoria($persona, 'RP-1'));
        }

        return [
            'elegible' => ! in_array(false, array_column($reglas, 'cumple'), true),
            // RN-09: informativo, indica que aplica el flujo de salida de menores.
            'requiere_certificacion_menor' => $this->requiereCertificacionMenor($persona),
            'reglas' => $reglas,
        ];
    }

    /**
     * Ejecuta una validación y traduce su resultado a cumple/detalle.
     *
     * @return array{regla:string, descripcion:string, cumple:bool, detalle:?string}
     */
    private function evaluarRegla(string $regla, string $descripcion, callable $validacion): array
    {
        try {
            $validacion();

            return ['regla' => $regla, 'descripcion' => $descripcion, 'cumple' => true, 'detalle' => null];
        } catch (ReglaNegocioException $e) {
            return ['regla' => $regla, 'descripcion' => $descripcion, 'cumple' => false, 'detalle' => $e->getMessage()];
        }
    }

    /**
     * Vencimiento del carnet vigente (o repuesto) más reciente de la persona,
     * base del cómputo de antelación de una renovación (RN-03).
     */
    private function vencimientoCarnetVigente(Persona $persona): CarbonInterface
    {
        $vencimiento = DocumentoEmitido::query()
            ->whereHas('solicitud.expediente', fn ($q) => $q->where('persona_id', $persona->id))
            ->where('tipo', 'like', 'CARNET_%')
            ->whereIn('estado', ['VIGENTE', 'REPUESTO'])
            ->whereNotNull('fecha_vencimiento')
            ->orderByDesc('fecha_vencimiento')
            ->value('fecha_vencimiento');

        if (! $vencimiento) {
            throw new ReglaNegocioException('La persona no tiene un carnet vigente del cual derivar el vencimiento a renovar.', 'RN-03');
        }

        return Carbon::parse($vencimiento);
    }

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
