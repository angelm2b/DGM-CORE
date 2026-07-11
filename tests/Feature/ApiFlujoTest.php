<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Ajuste;
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

        // El pago puede consultarse por su id (reimpresión de comprobante).
        $this->getJson("/core/v1/pagos/{$pago1['id']}")
            ->assertOk()
            ->assertJsonPath('data.numero_comprobante', $pago1['numero_comprobante']);

        // El listado de solicitudes filtra por persona y estado.
        $this->getJson('/core/v1/solicitudes?persona_id='.$persona['id'].'&estado=PAGADA')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $solicitud['id']);
        $this->getJson('/core/v1/solicitudes?persona_id='.$persona['id'].'&estado=RECHAZADA')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // El expediente se consulta por id y por persona.
        $this->getJson("/core/v1/expedientes/{$solicitud['expediente_id']}")
            ->assertOk()
            ->assertJsonPath('data.persona_id', $persona['id'])
            ->assertJsonCount(1, 'data.solicitudes');
        $this->getJson("/core/v1/personas/{$persona['id']}/expedientes")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // 6. Emitir documento y verificarlo.
        $doc = $this->postJson('/core/v1/documentos/emitir', [
            'solicitud_id' => $solicitud['id'],
            'tipo' => 'CARNET_RT9',
        ])->assertStatus(201)->json('data');

        $this->getJson("/core/v1/documentos/{$doc['numero_serie']}/verificar")
            ->assertOk()
            ->assertJsonPath('valido', true);

        // Los documentos emitidos se listan por persona.
        $this->getJson("/core/v1/personas/{$persona['id']}/documentos")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.numero_serie', $doc['numero_serie']);
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

    public function test_api_apagada_responde_503_a_todo(): void
    {
        // Con la API encendida (estado por defecto) el catálogo responde.
        $this->getJson('/core/v1/catalogos/servicios')->assertOk();

        Ajuste::alternarApi(); // apagar

        // Apagada: 503 sin cuerpo aun con token y permisos válidos.
        $this->getJson('/core/v1/catalogos/servicios')
            ->assertStatus(503)
            ->assertNoContent(503);
        $this->postJson('/core/v1/personas', [])->assertStatus(503);

        Ajuste::alternarApi(); // encender

        // Encendida de nuevo: el mismo token funciona sin cambios.
        $this->getJson('/core/v1/catalogos/servicios')->assertOk();
    }

    public function test_servicio_desactivado_rechaza_solicitudes_nuevas(): void
    {
        $persona = $this->postJson('/core/v1/personas', [
            'tipo_documento' => 'PASAPORTE',
            'numero_documento' => 'B7654321',
            'nacionalidad' => 'USA',
            'nombres' => 'John',
            'apellidos' => 'Roe',
            'fecha_nacimiento' => '1988-09-12',
            'pasaporte_vence' => '2031-05-05',
        ])->assertStatus(201)->json('data');

        $srv = Servicio::where('codigo', 'SRV-001')->first();
        $datos = [
            'persona_id' => $persona['id'],
            'servicio_id' => $srv->id,
            'oficina_id' => $this->oficinaId,
            'canal_origen' => 'WEB',
        ];

        // Desactivado: 422 problem+json estándar, igual que cualquier validación.
        $srv->update(['activo' => false]);
        $this->postJson('/core/v1/solicitudes', $datos)
            ->assertStatus(422)
            ->assertHeader('content-type', 'application/problem+json');

        // Reactivado: la misma petición vuelve a funcionar sin ningún cambio.
        $srv->update(['activo' => true]);
        $this->postJson('/core/v1/solicitudes', $datos)->assertStatus(201);
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

    public function test_validar_y_desvalidar_adjunto(): void
    {
        $persona = $this->postJson('/core/v1/personas', [
            'tipo_documento' => 'PASAPORTE', 'numero_documento' => 'F7777', 'nacionalidad' => 'ESP',
            'nombres' => 'Eva', 'apellidos' => 'Sol', 'fecha_nacimiento' => '1990-02-02',
        ])->json('data');
        $srv = Servicio::where('codigo', 'SRV-001')->first();
        $solicitud = $this->postJson('/core/v1/solicitudes', [
            'persona_id' => $persona['id'], 'servicio_id' => $srv->id, 'oficina_id' => $this->oficinaId, 'canal_origen' => 'WEB',
        ])->json('data');

        $adjunto = $this->postJson("/core/v1/solicitudes/{$solicitud['id']}/adjuntos", [
            'tipo_documento' => 'POLIZA', 'formato' => 'JPG', 'ruta' => 'adjuntos/poliza.jpg',
        ])->assertStatus(201)->json('data');

        $this->assertFalse($adjunto['validado']);

        $usuarioId = Usuario::where('email', 'analista@dgm.gob.do')->value('id');

        // Validar el adjunto (RN-08).
        $this->putJson("/core/v1/solicitudes/{$solicitud['id']}/adjuntos/{$adjunto['id']}/validar", [
            'validado' => true,
            'usuario_id' => $usuarioId,
        ])->assertOk()
            ->assertJsonPath('data.validado', true)
            ->assertJsonPath('data.validado_por', $usuarioId);

        // Retirar la validación limpia también quién validó.
        $this->putJson("/core/v1/solicitudes/{$solicitud['id']}/adjuntos/{$adjunto['id']}/validar", [
            'validado' => false,
        ])->assertOk()
            ->assertJsonPath('data.validado', false)
            ->assertJsonPath('data.validado_por', null);
    }

    public function test_adjunto_de_otra_solicitud_no_puede_validarse(): void
    {
        $srv = Servicio::where('codigo', 'SRV-001')->first();
        $solicitudes = [];
        foreach (['G8881', 'G8882'] as $doc) {
            $persona = $this->postJson('/core/v1/personas', [
                'tipo_documento' => 'PASAPORTE', 'numero_documento' => $doc, 'nacionalidad' => 'PER',
                'nombres' => 'P', 'apellidos' => 'Q', 'fecha_nacimiento' => '1990-01-01',
            ])->json('data');
            $solicitudes[] = $this->postJson('/core/v1/solicitudes', [
                'persona_id' => $persona['id'], 'servicio_id' => $srv->id, 'oficina_id' => $this->oficinaId, 'canal_origen' => 'WEB',
            ])->json('data');
        }

        $adjunto = $this->postJson("/core/v1/solicitudes/{$solicitudes[0]['id']}/adjuntos", [
            'tipo_documento' => 'POLIZA', 'formato' => 'JPG', 'ruta' => 'adjuntos/p.jpg',
        ])->json('data');

        // El binding es con alcance: el adjunto no pertenece a la otra solicitud.
        $this->putJson("/core/v1/solicitudes/{$solicitudes[1]['id']}/adjuntos/{$adjunto['id']}/validar", [
            'validado' => true,
        ])->assertStatus(404);
    }

    public function test_revocar_y_reponer_documento(): void
    {
        $persona = $this->postJson('/core/v1/personas', [
            'tipo_documento' => 'PASAPORTE', 'numero_documento' => 'H9999', 'nacionalidad' => 'BRA',
            'nombres' => 'Rui', 'apellidos' => 'Melo', 'fecha_nacimiento' => '1985-03-03',
        ])->json('data');
        $srv = Servicio::where('codigo', 'SRV-001')->first();
        $solicitud = $this->postJson('/core/v1/solicitudes', [
            'persona_id' => $persona['id'], 'servicio_id' => $srv->id, 'oficina_id' => $this->oficinaId, 'canal_origen' => 'WEB',
        ])->json('data');

        // Revocar: el documento deja de verificar como válido.
        $doc = $this->postJson('/core/v1/documentos/emitir', [
            'solicitud_id' => $solicitud['id'], 'tipo' => 'CARNET_RT9',
        ])->assertStatus(201)->json('data');

        $this->postJson("/core/v1/documentos/{$doc['id']}/revocar")
            ->assertOk()
            ->assertJsonPath('data.estado', 'REVOCADO');

        $this->getJson("/core/v1/documentos/{$doc['numero_serie']}/verificar")
            ->assertOk()
            ->assertJsonPath('valido', false);

        // Revocar dos veces es un incumplimiento de regla de negocio.
        $this->postJson("/core/v1/documentos/{$doc['id']}/revocar")
            ->assertStatus(422)
            ->assertHeader('content-type', 'application/problem+json');

        // Reponer: el original queda REPUESTO y se emite uno nuevo VIGENTE.
        $doc2 = $this->postJson('/core/v1/documentos/emitir', [
            'solicitud_id' => $solicitud['id'], 'tipo' => 'CARNET_RT9',
        ])->json('data');

        $nuevo = $this->postJson("/core/v1/documentos/{$doc2['id']}/reponer")
            ->assertStatus(201)
            ->json('data');

        $this->assertSame('VIGENTE', $nuevo['estado']);
        $this->assertNotSame($doc2['numero_serie'], $nuevo['numero_serie']);

        $this->getJson("/core/v1/documentos/{$doc2['numero_serie']}/verificar")
            ->assertOk()
            ->assertJsonPath('valido', false)
            ->assertJsonPath('documento.estado', 'REPUESTO');

        // Un documento REPUESTO tampoco puede reponerse de nuevo.
        $this->postJson("/core/v1/documentos/{$doc2['id']}/reponer")
            ->assertStatus(422);
    }

    public function test_eliminar_adjunto_no_validado(): void
    {
        $persona = $this->postJson('/core/v1/personas', [
            'tipo_documento' => 'PASAPORTE', 'numero_documento' => 'J1111', 'nacionalidad' => 'CHL',
            'nombres' => 'Ida', 'apellidos' => 'Rey', 'fecha_nacimiento' => '1991-01-01',
        ])->json('data');
        $srv = Servicio::where('codigo', 'SRV-001')->first();
        $solicitud = $this->postJson('/core/v1/solicitudes', [
            'persona_id' => $persona['id'], 'servicio_id' => $srv->id, 'oficina_id' => $this->oficinaId, 'canal_origen' => 'WEB',
        ])->json('data');

        $adjunto = $this->postJson("/core/v1/solicitudes/{$solicitud['id']}/adjuntos", [
            'tipo_documento' => 'FOTO_2X2', 'formato' => 'JPG', 'ruta' => 'adjuntos/foto.jpg',
        ])->json('data');

        // Un adjunto validado no puede eliminarse.
        $this->putJson("/core/v1/solicitudes/{$solicitud['id']}/adjuntos/{$adjunto['id']}/validar", ['validado' => true]);
        $this->deleteJson("/core/v1/solicitudes/{$solicitud['id']}/adjuntos/{$adjunto['id']}")
            ->assertStatus(422)
            ->assertHeader('content-type', 'application/problem+json');

        // Sin validación sí se elimina.
        $this->putJson("/core/v1/solicitudes/{$solicitud['id']}/adjuntos/{$adjunto['id']}/validar", ['validado' => false]);
        $this->deleteJson("/core/v1/solicitudes/{$solicitud['id']}/adjuntos/{$adjunto['id']}")->assertNoContent();

        $this->assertDatabaseMissing('documentos_adjuntos', ['id' => $adjunto['id']]);
    }

    public function test_calculo_penalidad_rn04(): void
    {
        // 2 meses completos + fracción desde el vencimiento => 3 meses x RD$1,000.
        $this->getJson('/core/v1/calculos/penalidad?fecha_vencimiento=2026-01-01&fecha_calculo=2026-03-15')
            ->assertOk()
            ->assertJsonPath('data.meses_vencidos', 3)
            ->assertJsonPath('data.monto', '3000.00');

        // Sin vencimiento cumplido no hay penalidad.
        $this->getJson('/core/v1/calculos/penalidad?fecha_vencimiento=2026-06-01&fecha_calculo=2026-03-15')
            ->assertOk()
            ->assertJsonPath('data.meses_vencidos', 0)
            ->assertJsonPath('data.monto', '0.00');
    }

    public function test_elegibilidad_de_renovacion(): void
    {
        $persona = $this->postJson('/core/v1/personas', [
            'tipo_documento' => 'PASAPORTE', 'numero_documento' => 'K2222', 'nacionalidad' => 'ARG',
            'nombres' => 'Leo', 'apellidos' => 'Vera', 'fecha_nacimiento' => '1983-04-04',
            'pasaporte_vence' => '2035-01-01',
        ])->json('data');

        // SRV-002: renovación de residencia temporal (aplican RN-03, RN-08 y RN-10).
        $srv = Servicio::where('codigo', 'SRV-002')->first();
        $solicitud = $this->postJson('/core/v1/solicitudes', [
            'persona_id' => $persona['id'], 'servicio_id' => $srv->id, 'oficina_id' => $this->oficinaId, 'canal_origen' => 'WEB',
        ])->json('data');

        // Sin carnet vigente ni póliza validada la solicitud no es elegible.
        $resultado = $this->getJson("/core/v1/solicitudes/{$solicitud['id']}/elegibilidad")
            ->assertOk()
            ->json('data');

        $this->assertFalse($resultado['elegible']);
        $this->assertFalse($resultado['requiere_certificacion_menor']);

        $porRegla = collect($resultado['reglas'])->keyBy('regla');
        $this->assertTrue($porRegla['RN-10']['cumple']);   // pasaporte vigente
        $this->assertFalse($porRegla['RN-03']['cumple']);  // sin carnet que renovar
        $this->assertFalse($porRegla['RN-08']['cumple']);  // sin póliza validada

        // Al adjuntar y validar la PÓLIZA, RN-08 pasa a cumplirse.
        $adjunto = $this->postJson("/core/v1/solicitudes/{$solicitud['id']}/adjuntos", [
            'tipo_documento' => 'POLIZA', 'formato' => 'JPG', 'ruta' => 'adjuntos/poliza.jpg',
        ])->json('data');
        $this->putJson("/core/v1/solicitudes/{$solicitud['id']}/adjuntos/{$adjunto['id']}/validar", ['validado' => true]);

        $porRegla = collect($this->getJson("/core/v1/solicitudes/{$solicitud['id']}/elegibilidad")->json('data.reglas'))->keyBy('regla');
        $this->assertTrue($porRegla['RN-08']['cumple']);
    }

    public function test_actualizar_y_eliminar_persona(): void
    {
        $persona = $this->postJson('/core/v1/personas', [
            'tipo_documento' => 'PASAPORTE', 'numero_documento' => 'D5555', 'nacionalidad' => 'MEX',
            'nombres' => 'Ana', 'apellidos' => 'Luna', 'fecha_nacimiento' => '1995-05-05',
        ])->assertStatus(201)->json('data');

        // Actualización parcial: solo cambian los campos enviados.
        $this->putJson("/core/v1/personas/{$persona['id']}", [
            'email' => 'ana.luna@example.com',
            'telefono' => '809-555-0101',
        ])->assertOk()
            ->assertJsonPath('data.email', 'ana.luna@example.com')
            ->assertJsonPath('data.nombres', 'Ana');

        // Eliminación de persona sin expedientes ni movimientos.
        $this->deleteJson("/core/v1/personas/{$persona['id']}")->assertNoContent();
        $this->getJson("/core/v1/personas/{$persona['id']}")->assertStatus(404);
    }

    public function test_eliminar_persona_con_expediente_es_rechazado(): void
    {
        $persona = $this->postJson('/core/v1/personas', [
            'tipo_documento' => 'PASAPORTE', 'numero_documento' => 'E6666', 'nacionalidad' => 'COL',
            'nombres' => 'Luis', 'apellidos' => 'Paz', 'fecha_nacimiento' => '1988-08-08',
        ])->json('data');

        // Crear una solicitud abre un expediente asociado a la persona.
        $srv = Servicio::where('codigo', 'SRV-001')->first();
        $this->postJson('/core/v1/solicitudes', [
            'persona_id' => $persona['id'], 'servicio_id' => $srv->id,
            'oficina_id' => $this->oficinaId, 'canal_origen' => 'WEB',
        ])->assertStatus(201);

        $this->deleteJson("/core/v1/personas/{$persona['id']}")
            ->assertStatus(422)
            ->assertHeader('content-type', 'application/problem+json');
    }

    public function test_catalogos_responden(): void
    {
        $this->getJson('/core/v1/catalogos/servicios')->assertOk()->assertJsonStructure(['data']);
        $this->getJson('/core/v1/catalogos/categorias')->assertOk();
        $this->getJson('/core/v1/catalogos/tarifas')->assertOk();
        $this->getJson('/core/v1/catalogos/puntos-control')->assertOk();
    }
}
