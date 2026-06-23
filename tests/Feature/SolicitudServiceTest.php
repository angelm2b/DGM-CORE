<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\TransicionInvalidaException;
use App\Models\Expediente;
use App\Models\Oficina;
use App\Models\Persona;
use App\Models\Servicio;
use App\Models\Solicitud;
use App\Services\SolicitudService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolicitudServiceTest extends TestCase
{
    use RefreshDatabase;

    private SolicitudService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = app(SolicitudService::class);
    }

    public function test_transicion_legal_registra_historial(): void
    {
        $solicitud = $this->solicitud();
        $this->servicio->registrarHistorialInicial($solicitud);

        $this->servicio->transicionar($solicitud, 'ENVIADA', 'INTEGRACION', motivo: 'envío');

        $solicitud->refresh();
        $this->assertSame('ENVIADA', $solicitud->estado_actual->getValue());
        $this->assertSame(2, $solicitud->estados()->count()); // inicial + ENVIADA
        $this->assertDatabaseHas('solicitud_estados', [
            'solicitud_id' => $solicitud->id,
            'estado_anterior' => 'BORRADOR',
            'estado_nuevo' => 'ENVIADA',
            'sistema_origen' => 'INTEGRACION',
        ]);
    }

    public function test_actualiza_fecha_ultima_accion(): void
    {
        $solicitud = $this->solicitud();
        $solicitud->forceFill(['fecha_ultima_accion' => now()->subDays(30)])->save();

        $this->servicio->transicionar($solicitud, 'ENVIADA');

        $this->assertTrue($solicitud->refresh()->fecha_ultima_accion->greaterThan(now()->subMinute()));
    }

    public function test_transicion_ilegal_lanza_excepcion_y_no_persiste(): void
    {
        $solicitud = $this->solicitud();

        try {
            $this->servicio->transicionar($solicitud, 'ENTREGADO');
            $this->fail('Debió lanzar TransicionInvalidaException.');
        } catch (TransicionInvalidaException $e) {
            // ok
        }

        $this->assertSame('BORRADOR', $solicitud->refresh()->estado_actual->getValue());
    }

    public function test_flujo_feliz_completo(): void
    {
        $solicitud = $this->solicitud();
        $ruta = ['ENVIADA', 'EN_DEPURACION', 'APROBADA_PAGO_PENDIENTE', 'PAGADA', 'EN_PROCESO', 'APROBADA', 'DOCUMENTO_EMITIDO', 'ENTREGADO'];

        foreach ($ruta as $estado) {
            $this->servicio->transicionar($solicitud->refresh(), $estado);
        }

        $this->assertSame('ENTREGADO', $solicitud->refresh()->estado_actual->getValue());
        $this->assertTrue($solicitud->estado_actual->esTerminal());
    }

    private function solicitud(): Solicitud
    {
        $persona = Persona::create([
            'tipo_documento' => 'PASAPORTE', 'numero_documento' => 'P'.fake()->unique()->numerify('######'),
            'nacionalidad' => 'USA', 'nombres' => 'Test', 'apellidos' => 'User',
            'fecha_nacimiento' => '1990-01-01', 'estatus_migratorio' => 'EN_TRAMITE',
        ]);
        $of = Oficina::firstOrCreate(['codigo' => 'OF-TST'], ['nombre' => 'Test', 'localidad' => 'SDQ']);
        $srv = Servicio::firstOrCreate(['codigo' => 'SRV-TST'], ['nombre' => 'Test', 'requiere_cita' => false, 'dias_sla' => 10, 'canal' => 'AMBOS', 'activo' => true]);
        $exp = Expediente::create(['persona_id' => $persona->id, 'numero_expediente' => 'DGM-2026-'.fake()->unique()->numerify('######'), 'fecha_apertura' => now(), 'oficina_id' => $of->id]);

        return Solicitud::create(['expediente_id' => $exp->id, 'servicio_id' => $srv->id, 'canal_origen' => 'WEB', 'oficina_id' => $of->id]);
    }
}
