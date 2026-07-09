<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Servicio;
use App\Models\Tarifa;
use App\Models\Usuario;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PanelAdminTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->admin = Usuario::where('email', 'admin@dgm.gob.do')->first();
        $this->admin->update(['password' => Hash::make('clave-segura-admin')]);
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_login_correcto_entra_al_panel(): void
    {
        $this->post('/admin/login', [
            'email' => 'admin@dgm.gob.do',
            'password' => 'clave-segura-admin',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($this->admin, 'web');
        $this->get('/admin')->assertOk()->assertSee('Panel de administración');
    }

    public function test_login_con_clave_incorrecta_falla(): void
    {
        $this->post('/admin/login', [
            'email' => 'admin@dgm.gob.do',
            'password' => 'clave-equivocada',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_rol_no_admin_no_puede_entrar(): void
    {
        $analista = Usuario::where('email', 'analista@dgm.gob.do')->first();
        $analista->update(['password' => Hash::make('clave-segura-analista')]);

        $this->post('/admin/login', [
            'email' => 'analista@dgm.gob.do',
            'password' => 'clave-segura-analista',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_cuenta_inactiva_no_puede_entrar(): void
    {
        $this->admin->update(['activo' => false]);

        $this->post('/admin/login', [
            'email' => 'admin@dgm.gob.do',
            'password' => 'clave-segura-admin',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('web');
    }

    public function test_paginas_del_panel_cargan(): void
    {
        $this->actingAs($this->admin, 'web');

        $this->get('/admin/usuarios')->assertOk()->assertSee('Usuarios');
        $this->get('/admin/servicios')->assertOk()->assertSee('Servicios');
        $this->get('/admin/tarifas')->assertOk()->assertSee('Tarifas');
        $this->get('/admin/solicitudes')->assertOk()->assertSee('Solicitudes');
        $this->get('/admin/auditoria')->assertOk()->assertSee('auditoría');
    }

    public function test_admin_puede_crear_usuario(): void
    {
        $rolAuditor = Rol::where('codigo', 'AUDITOR')->first();

        $this->actingAs($this->admin, 'web')
            ->post('/admin/usuarios', [
                'nombre' => 'Nuevo Auditor',
                'email' => 'nuevo.auditor@dgm.gob.do',
                'password' => 'clave-larga-segura',
                'rol_id' => $rolAuditor->id,
            ])->assertRedirect('/admin/usuarios');

        $this->assertDatabaseHas('usuarios', [
            'email' => 'nuevo.auditor@dgm.gob.do',
            'rol_id' => $rolAuditor->id,
            'activo' => true,
        ]);
    }

    public function test_admin_no_puede_desactivarse_a_si_mismo(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post("/admin/usuarios/{$this->admin->id}/alternar-activo")
            ->assertSessionHasErrors('usuario');

        $this->assertTrue($this->admin->fresh()->activo);
    }

    public function test_admin_puede_alternar_servicio(): void
    {
        $servicio = Servicio::first();

        $this->actingAs($this->admin, 'web')
            ->post("/admin/servicios/{$servicio->id}/alternar-activo")
            ->assertRedirect();

        $this->assertNotSame($servicio->activo, $servicio->fresh()->activo);
    }

    public function test_admin_puede_registrar_tarifa(): void
    {
        $servicio = Servicio::first();

        $this->actingAs($this->admin, 'web')
            ->post('/admin/tarifas', [
                'servicio_id' => $servicio->id,
                'concepto' => 'DEPOSITO_EXPEDIENTE',
                'monto' => '1500.00',
                'moneda' => 'DOP',
                'vigente_desde' => '2026-07-01',
                'vigente_hasta' => '2027-06-30',
                'resolucion' => 'RES-2026-001',
            ])->assertRedirect('/admin/tarifas');

        $this->assertDatabaseHas('tarifas', [
            'servicio_id' => $servicio->id,
            'concepto' => 'DEPOSITO_EXPEDIENTE',
            'resolucion' => 'RES-2026-001',
        ]);
    }

    public function test_admin_puede_editar_tarifa(): void
    {
        $tarifa = Tarifa::first();

        $this->actingAs($this->admin, 'web')
            ->get("/admin/tarifas/{$tarifa->id}/editar")
            ->assertOk()
            ->assertSee('Editar tarifa');

        $this->actingAs($this->admin, 'web')
            ->put("/admin/tarifas/{$tarifa->id}", [
                'servicio_id' => $tarifa->servicio_id,
                'concepto' => $tarifa->concepto,
                'monto' => '9999.00',
                'moneda' => $tarifa->moneda,
                'vigente_desde' => $tarifa->vigente_desde->format('Y-m-d'),
                'vigente_hasta' => $tarifa->vigente_hasta?->format('Y-m-d'),
                'resolucion' => 'RES-CORREGIDA',
            ])->assertRedirect('/admin/tarifas');

        $tarifa->refresh();
        $this->assertSame('9999.00', (string) $tarifa->monto);
        $this->assertSame('RES-CORREGIDA', $tarifa->resolucion);
    }
}
