<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Expediente;
use App\Models\Oficina;
use App\Models\Persona;
use App\Models\Servicio;
use App\Models\Solicitud;
use App\Services\DocumentoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DocumentoServiceTest extends TestCase
{
    use RefreshDatabase;

    private DocumentoService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = app(DocumentoService::class);
    }

    public function test_emite_carnet_rt9_con_un_anio_de_vigencia(): void
    {
        $solicitud = $this->solicitud();
        $doc = $this->servicio->emitir($solicitud, 'CARNET_RT9', emision: Carbon::parse('2026-01-15'));

        $this->assertSame('VIGENTE', $doc->estado);
        $this->assertStringStartsWith('DOC-2026-', $doc->numero_serie);
        $this->assertSame('2027-01-15', $doc->fecha_vencimiento->toDateString());
    }

    public function test_rn06_carnet_rp1_primero_un_anio_luego_cuatro(): void
    {
        $persona = $this->persona();
        $sol1 = $this->solicitud($persona);
        $doc1 = $this->servicio->emitir($sol1, 'CARNET_RP1', emision: Carbon::parse('2026-01-15'));
        $this->assertSame('2027-01-15', $doc1->fecha_vencimiento->toDateString()); // 1 año

        $sol2 = $this->solicitud($persona);
        $doc2 = $this->servicio->emitir($sol2, 'CARNET_RP1', emision: Carbon::parse('2027-01-15'));
        $this->assertSame('2031-01-15', $doc2->fecha_vencimiento->toDateString()); // 4 años
    }

    public function test_reposicion_marca_repuesto_y_emite_nuevo(): void
    {
        $solicitud = $this->solicitud();
        $original = $this->servicio->emitir($solicitud, 'CARNET_RT9', emision: Carbon::parse('2026-01-15'));

        $nuevo = $this->servicio->reponer($original);

        $this->assertSame('REPUESTO', $original->refresh()->estado);
        $this->assertSame('VIGENTE', $nuevo->estado);
        $this->assertNotSame($original->numero_serie, $nuevo->numero_serie);
    }

    public function test_revocar_cambia_estado(): void
    {
        $doc = $this->servicio->emitir($this->solicitud(), 'CARNET_RT9');
        $this->servicio->revocar($doc, 'fraude');
        $this->assertSame('REVOCADO', $doc->refresh()->estado);
    }

    private function persona(): Persona
    {
        return Persona::create([
            'tipo_documento' => 'PASAPORTE', 'numero_documento' => 'P'.fake()->unique()->numerify('######'),
            'nacionalidad' => 'USA', 'nombres' => 'Test', 'apellidos' => 'User',
            'fecha_nacimiento' => '1990-01-01', 'estatus_migratorio' => 'EN_TRAMITE',
        ]);
    }

    private function solicitud(?Persona $persona = null): Solicitud
    {
        $persona ??= $this->persona();
        $of = Oficina::firstOrCreate(['codigo' => 'OF-TST'], ['nombre' => 'Test', 'localidad' => 'SDQ']);
        $srv = Servicio::firstOrCreate(['codigo' => 'SRV-TST'], ['nombre' => 'Test', 'requiere_cita' => false, 'dias_sla' => 10, 'canal' => 'AMBOS', 'activo' => true]);
        $exp = Expediente::create(['persona_id' => $persona->id, 'numero_expediente' => 'DGM-2026-'.fake()->unique()->numerify('######'), 'fecha_apertura' => now(), 'oficina_id' => $of->id]);

        return Solicitud::create(['expediente_id' => $exp->id, 'servicio_id' => $srv->id, 'canal_origen' => 'WEB', 'oficina_id' => $of->id]);
    }
}
