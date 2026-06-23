<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Oficina;
use App\Models\PuntoControl;
use App\Models\Servicio;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiFlujoTest extends TestCase
{
    use RefreshDatabase;

    private int $oficinaId;

    private int $puntoControlId;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->seed(DatabaseSeeder::class);
        $integrador = Usuario::where('email', 'integrador@dgm.gob.do')->first();
        Sanctum::actingAs($integrador, ['*'], 'sanctum');

        // El auto-incremento de MySQL no se revierte entre pruebas (RefreshDatabase
        // usa transacciones), por lo que no se pueden asumir ids fijos.
        $this->oficinaId = Oficina::query()->value('id');
        $this->puntoControlId = PuntoControl::query()->value('id');
    }

    public function test_sin_token_devuelve_401_problem_json(): void
    {
        // Petición sin actuar como usuario autenticado.
        app('auth')->forgetGuards();
        $resp = $this->getJson('/core/v1/catalogos/servicios');
        $resp->assertStatus(401)
            ->assertHeader('content-type', 'application/problem+json')
            ->assertJsonStructure(['type', 'title', 'status', 'detail', 'correlationId']);
    }

    public function test_flujo_completo_persona_solicitud_pago_documento(): void
    {
        // 1. Crear persona.
        $persona = $this->postJson('/core/v1/personas', [
            'tipo_documento' => 'PASAPORTE',
            'numero_documento' => 'A1234567',
            'nacionalidad' => 'USA',
            'nombres' => 'Jane',
            'apellidos' => 'Doe',
            'fecha_nacimiento' => '1992-03-04',
            'pasaporte_vence' => '2030-01-01',
        ])->assertStatus(201)->json('data');

        // 2. Crear solicitud RT-9.
        $srv = Servicio::where('codigo', 'SRV-001')->first();
        $solicitud = $this->postJson('/core/v1/solicitudes', [
            'persona_id' => $persona['id'],
            'servicio_id' => $srv->id,
            'oficina_id' => $this->oficinaId,
            'canal_origen' => 'WEB',
        ])->assertStatus(201)->json('data');

        $this->assertSame('BORRADOR', $solicitud['estado_actual']);

        // 3. Transicionar hasta APROBADA_PAGO_PENDIENTE (genera la orden).
        foreach (['ENVIADA', 'EN_DEPURACION', 'APROBADA_PAGO_PENDIENTE'] as $estado) {
            $this->postJson("/core/v1/solicitudes/{$solicitud['id']}/transicion", ['estado_destino' => $estado])
                ->assertOk();
        }

        $detalle = $this->getJson("/core/v1/solicitudes/{$solicitud['id']}")->json('data');
        $this->assertSame('APROBADA_PAGO_PENDIENTE', $detalle['estado_actual']);
        $orden = $detalle['ordenes_pago'][0];
        $this->assertSame('9500.00', $orden['monto_total']); // 7000 depósito + 2500 carnet

        // 4. Pagar con Idempotency-Key.
        $headers = ['Idempotency-Key' => 'idem-001'];
        $pago1 = $this->withHeaders($headers)->postJson('/core/v1/pagos', [
            'orden_pago_id' => $orden['id'],
            'monto' => '9500.00',
            'metodo' => 'EFECTIVO',
        ])->assertStatus(201)->json('data');

        // Reintento idempotente: mismo comprobante, 200.
        $pago2 = $this->withHeaders($headers)->postJson('/core/v1/pagos', [
            'orden_pago_id' => $orden['id'],
            'monto' => '9500.00',
            'metodo' => 'EFECTIVO',
        ])->assertStatus(200)->json('data');

        $this->assertSame($pago1['numero_comprobante'], $pago2['numero_comprobante']);

        // 5. La orden quedó PAGADA y la solicitud avanzó a PAGADA.
        $this->getJson("/core/v1/ordenes-pago/{$orden['id']}")->assertOk()->assertJsonPath('data.estado', 'PAGADA');
        $this->getJson("/core/v1/solicitudes/{$solicitud['id']}")->assertJsonPath('data.estado_actual', 'PAGADA');

        // 6. Emitir documento y verificarlo.
        $doc = $this->postJson('/core/v1/documentos/emitir', [
            'solicitud_id' => $solicitud['id'],
            'tipo' => 'CARNET_RT9',
        ])->assertStatus(201)->json('data');

        $this->getJson("/core/v1/documentos/{$doc['numero_serie']}/verificar")
            ->assertOk()
            ->assertJsonPath('valido', true);
    }

    public function test_movimiento_salida_calcula_sobreestadia(): void
    {
        $persona = $this->postJson('/core/v1/personas', [
            'tipo_documento' => 'PASAPORTE', 'numero_documento' => 'B999', 'nacionalidad' => 'CAN',
            'nombres' => 'Tom', 'apellidos' => 'Roe', 'fecha_nacimiento' => '1980-01-01',
        ])->json('data');

        // Entrada con 30 días autorizados.
        $this->postJson('/core/v1/movimientos', [
            'persona_id' => $persona['id'], 'tipo' => 'E', 'punto_control_id' => $this->puntoControlId,
            'fecha_hora' => '2026-01-01T10:00:00Z', 'dias_autorizados' => 30,
        ])->assertStatus(201);

        // Salida 50 días después -> 20 días de sobreestadía.
        $salida = $this->postJson('/core/v1/movimientos', [
            'persona_id' => $persona['id'], 'tipo' => 'S', 'punto_control_id' => $this->puntoControlId,
            'fecha_hora' => '2026-02-20T10:00:00Z',
        ])->assertStatus(201)->json('data');

        $this->assertArrayHasKey('sobreestadia', $salida);
        $this->assertSame(20, $salida['sobreestadia']['dias']);

        // Endpoint de cálculo coincide.
        $this->getJson('/core/v1/calculos/tasa-estadia?persona_id='.$persona['id'].'&fecha_salida=2026-02-20')
            ->assertOk()
            ->assertJsonPath('data.dias_sobreestadia', 20);
    }

    public function test_adjunto_no_jpg_es_rechazado_rn11(): void
    {
        $persona = $this->postJson('/core/v1/personas', [
            'tipo_documento' => 'CEDULA', 'numero_documento' => 'C1', 'nacionalidad' => 'DOM',
            'nombres' => 'A', 'apellidos' => 'B', 'fecha_nacimiento' => '2000-01-01',
        ])->json('data');
        $srv = Servicio::where('codigo', 'SRV-001')->first();
        $solicitud = $this->postJson('/core/v1/solicitudes', [
            'persona_id' => $persona['id'], 'servicio_id' => $srv->id, 'oficina_id' => $this->oficinaId, 'canal_origen' => 'WEB',
        ])->json('data');

        $this->postJson("/core/v1/solicitudes/{$solicitud['id']}/adjuntos", [
            'tipo_documento' => 'POLIZA', 'formato' => 'PDF', 'ruta' => 'x/y.pdf',
        ])->assertStatus(422)->assertHeader('content-type', 'application/problem+json');
    }

    public function test_catalogos_responden(): void
    {
        $this->getJson('/core/v1/catalogos/servicios')->assertOk()->assertJsonStructure(['data']);
        $this->getJson('/core/v1/catalogos/categorias')->assertOk();
        $this->getJson('/core/v1/catalogos/tarifas')->assertOk();
        $this->getJson('/core/v1/catalogos/puntos-control')->assertOk();
    }
}
