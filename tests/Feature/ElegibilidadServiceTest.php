<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\CategoriaMigratoria;
use App\Models\DocumentoAdjunto;
use App\Models\DocumentoEmitido;
use App\Models\Expediente;
use App\Models\Oficina;
use App\Models\Persona;
use App\Models\Servicio;
use App\Models\Solicitud;
use App\Services\ElegibilidadService;
use Database\Seeders\CategoriasMigratoriasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ElegibilidadServiceTest extends TestCase
{
    use RefreshDatabase;

    private ElegibilidadService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new ElegibilidadService;
    }

    // ---- RN-01 ----
    public function test_rn01_prorroga_turista_dentro_del_maximo(): void
    {
        $this->expectNotToPerformAssertions();
        $this->servicio->validarProrrogaTurista(0, 60); // 30 + 60 = 90 <= 120
    }

    public function test_rn01_prorroga_turista_excede_120(): void
    {
        $this->expectException(ReglaNegocioException::class);
        $this->servicio->validarProrrogaTurista(60, 60); // 30 + 120 = 150 > 120
    }

    // ---- RN-03 ----
    public function test_rn03_renovacion_con_antelacion_suficiente(): void
    {
        $this->expectNotToPerformAssertions();
        $this->servicio->validarAntelacionRenovacion(Carbon::parse('2026-12-31'), Carbon::parse('2026-06-01'));
    }

    public function test_rn03_renovacion_tardia_falla(): void
    {
        $this->expectException(ReglaNegocioException::class);
        $this->servicio->validarAntelacionRenovacion(Carbon::parse('2026-06-20'), Carbon::parse('2026-06-01')); // 19 < 45
    }

    // ---- RN-10 ----
    public function test_rn10_pasaporte_con_vigencia_suficiente(): void
    {
        $persona = $this->persona(['pasaporte_vence' => '2027-06-01']);
        $this->expectNotToPerformAssertions();
        $this->servicio->validarVigenciaPasaporte($persona, Carbon::parse('2026-06-01'));
    }

    public function test_rn10_pasaporte_por_vencer_falla(): void
    {
        $persona = $this->persona(['pasaporte_vence' => '2026-08-01']); // < 6 meses
        $this->expectException(ReglaNegocioException::class);
        $this->servicio->validarVigenciaPasaporte($persona, Carbon::parse('2026-06-01'));
    }

    // ---- RN-11 ----
    public function test_rn11_formato_distinto_de_jpg_falla(): void
    {
        $this->expectException(ReglaNegocioException::class);
        $this->servicio->validarFormatoJpg('PDF');
    }

    // ---- RN-12 ----
    public function test_rn12_solvencia_insuficiente_falla(): void
    {
        $this->expectException(ReglaNegocioException::class);
        $this->servicio->validarSolvenciaRT9(true, '100000.00');
    }

    public function test_rn12_solvencia_suficiente_ok(): void
    {
        $this->expectNotToPerformAssertions();
        $this->servicio->validarSolvenciaRT9(true, '150000.00');
    }

    public function test_rn12_no_casado_no_exige_solvencia(): void
    {
        $this->expectNotToPerformAssertions();
        $this->servicio->validarSolvenciaRT9(false, '0.00');
    }

    // ---- RN-05 ----
    public function test_rn05_cambio_rt9_a_rp1_requiere_carnet(): void
    {
        $this->seed(CategoriasMigratoriasSeeder::class);
        $rt9 = CategoriaMigratoria::where('codigo', 'RT-9')->first();
        $persona = $this->persona(['categoria_migratoria_id' => $rt9->id]);

        // Sin carnet emitido -> falla.
        try {
            $this->servicio->validarCambioCategoria($persona, 'RP-1');
            $this->fail('Debió lanzar ReglaNegocioException por falta de carnet.');
        } catch (ReglaNegocioException $e) {
            $this->assertSame('RN-05', $e->regla);
        }

        // Con carnet RT-9 vigente -> ok.
        $this->emitirCarnet($persona, 'CARNET_RT9');
        $this->servicio->validarCambioCategoria($persona->refresh(), 'RP-1');
        $this->assertTrue(true);
    }

    public function test_rn05_no_rt9_no_puede_cambiar(): void
    {
        $this->seed(CategoriasMigratoriasSeeder::class);
        $rp1 = CategoriaMigratoria::where('codigo', 'RP-1')->first();
        $persona = $this->persona(['categoria_migratoria_id' => $rp1->id]);

        $this->expectException(ReglaNegocioException::class);
        $this->servicio->validarCambioCategoria($persona, 'RP-1');
    }

    // ---- RN-06 ----
    public function test_rn06_vigencia_carnet_rp1(): void
    {
        $persona = $this->persona();
        $this->assertSame(1, $this->servicio->vigenciaCarnetRP1($persona->id)); // primero: 1 año
        $this->emitirCarnet($persona, 'CARNET_RP1');
        $this->assertSame(4, $this->servicio->vigenciaCarnetRP1($persona->id)); // siguientes: 4 años
    }

    // ---- RN-08 ----
    public function test_rn08_renovacion_exige_poliza_validada(): void
    {
        $solicitud = $this->solicitudConAdjunto(false);
        try {
            $this->servicio->validarPolizaRenovacion($solicitud);
            $this->fail('Debió fallar sin póliza validada.');
        } catch (ReglaNegocioException $e) {
            $this->assertSame('RN-08', $e->regla);
        }

        $solicitud->adjuntos()->update(['validado' => true]);
        $this->servicio->validarPolizaRenovacion($solicitud->refresh());
        $this->assertTrue(true);
    }

    // ---- Helpers ----
    private function persona(array $attrs = []): Persona
    {
        return Persona::create(array_merge([
            'tipo_documento' => 'PASAPORTE',
            'numero_documento' => 'P'.fake()->unique()->numerify('######'),
            'nacionalidad' => 'USA',
            'nombres' => 'Test',
            'apellidos' => 'Persona',
            'fecha_nacimiento' => '1990-01-01',
            'estatus_migratorio' => 'EN_TRAMITE',
        ], $attrs));
    }

    private function solicitud(Persona $persona): Solicitud
    {
        $of = Oficina::firstOrCreate(['codigo' => 'OF-TST'], ['nombre' => 'Oficina Test', 'localidad' => 'SDQ']);
        $srv = Servicio::firstOrCreate(['codigo' => 'SRV-TST'], ['nombre' => 'Test', 'requiere_cita' => false, 'dias_sla' => 10, 'canal' => 'AMBOS', 'activo' => true]);
        $exp = Expediente::create(['persona_id' => $persona->id, 'numero_expediente' => 'DGM-2026-'.fake()->unique()->numerify('######'), 'fecha_apertura' => now(), 'oficina_id' => $of->id]);

        return Solicitud::create(['expediente_id' => $exp->id, 'servicio_id' => $srv->id, 'canal_origen' => 'WEB', 'oficina_id' => $of->id]);
    }

    private function emitirCarnet(Persona $persona, string $tipo): DocumentoEmitido
    {
        $solicitud = $this->solicitud($persona);

        return DocumentoEmitido::create([
            'solicitud_id' => $solicitud->id,
            'tipo' => $tipo,
            'numero_serie' => 'SER-'.fake()->unique()->numerify('######'),
            'fecha_emision' => now(),
            'estado' => 'VIGENTE',
        ]);
    }

    private function solicitudConAdjunto(bool $validado): Solicitud
    {
        $solicitud = $this->solicitud($this->persona());
        DocumentoAdjunto::create([
            'solicitud_id' => $solicitud->id,
            'tipo_documento' => 'POLIZA',
            'formato' => 'JPG',
            'ruta' => 'adjuntos/poliza.jpg',
            'validado' => $validado,
        ]);

        return $solicitud;
    }
}
