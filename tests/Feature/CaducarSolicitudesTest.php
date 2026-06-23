<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Expediente;
use App\Models\Oficina;
use App\Models\Persona;
use App\Models\Servicio;
use App\Models\Solicitud;
use App\Services\SolicitudService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaducarSolicitudesTest extends TestCase
{
    use RefreshDatabase;

    public function test_caduca_solicitudes_activas_inactivas_y_respeta_terminales(): void
    {
        $vieja = $this->solicitud()->forceFill(['fecha_ultima_accion' => now()->subDays(120)]);
        $vieja->save();

        $reciente = $this->solicitud()->forceFill(['fecha_ultima_accion' => now()->subDays(10)]);
        $reciente->save();

        // Una entregada (terminal) y antigua: no debe caducar.
        $entregada = $this->solicitud();
        foreach (['ENVIADA', 'EN_DEPURACION', 'APROBADA_PAGO_PENDIENTE', 'PAGADA', 'EN_PROCESO', 'APROBADA', 'DOCUMENTO_EMITIDO', 'ENTREGADO'] as $e) {
            app(SolicitudService::class)->transicionar($entregada->refresh(), $e);
        }
        $entregada->forceFill(['fecha_ultima_accion' => now()->subDays(200)])->save();

        $this->artisan('solicitudes:caducar')->assertSuccessful();

        $this->assertSame('CADUCADA', $vieja->refresh()->estado_actual->getValue());
        $this->assertSame('BORRADOR', $reciente->refresh()->estado_actual->getValue());
        $this->assertSame('ENTREGADO', $entregada->refresh()->estado_actual->getValue());
    }

    private function solicitud(): Solicitud
    {
        $persona = Persona::create([
            'tipo_documento' => 'PASAPORTE', 'numero_documento' => 'P'.fake()->unique()->numerify('######'),
            'nacionalidad' => 'USA', 'nombres' => 'T', 'apellidos' => 'U',
            'fecha_nacimiento' => '1990-01-01', 'estatus_migratorio' => 'EN_TRAMITE',
        ]);
        $of = Oficina::firstOrCreate(['codigo' => 'OF-TST'], ['nombre' => 'Test', 'localidad' => 'SDQ']);
        $srv = Servicio::firstOrCreate(['codigo' => 'SRV-TST'], ['nombre' => 'Test', 'requiere_cita' => false, 'dias_sla' => 10, 'canal' => 'AMBOS', 'activo' => true]);
        $exp = Expediente::create(['persona_id' => $persona->id, 'numero_expediente' => 'DGM-2026-'.fake()->unique()->numerify('######'), 'fecha_apertura' => now(), 'oficina_id' => $of->id]);

        return Solicitud::create(['expediente_id' => $exp->id, 'servicio_id' => $srv->id, 'canal_origen' => 'WEB', 'oficina_id' => $of->id]);
    }
}
