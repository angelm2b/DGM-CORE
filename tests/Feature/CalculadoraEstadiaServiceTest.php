<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\CalculadoraEstadiaService;
use Database\Seeders\TablaEstadiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** RN-02: tasa de sobreestadía escalonada + recargo desde 10 años. */
class CalculadoraEstadiaServiceTest extends TestCase
{
    use RefreshDatabase;

    private CalculadoraEstadiaService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TablaEstadiaSeeder::class);
        $this->servicio = new CalculadoraEstadiaService;
    }

    public function test_sin_sobreestadia_es_cero(): void
    {
        $this->assertSame('0.00', $this->servicio->calcularPorDias(0)['monto']);
    }

    public function test_usa_el_rango_correcto_de_la_tabla(): void
    {
        $this->assertSame('1000.00', $this->servicio->calcularPorDias(5)['monto']);   // 1-10
        $this->assertSame('2500.00', $this->servicio->calcularPorDias(20)['monto']);  // 11-30
        $this->assertSame('4000.00', $this->servicio->calcularPorDias(60)['monto']);  // 31-90
        $this->assertSame('10000.00', $this->servicio->calcularPorDias(200)['monto']); // 181-365
    }

    public function test_rango_abierto_para_el_ultimo_tramo(): void
    {
        $r = $this->servicio->calcularPorDias(400); // > 366, < 10 años
        $this->assertSame('20000.00', $r['monto']);
        $this->assertSame('0.00', $r['recargo_anios']);
    }

    public function test_recargo_de_5000_por_anio_o_fraccion_desde_10_anios(): void
    {
        // 10 años (3650 días) -> sin recargo todavía.
        $this->assertSame('0.00', $this->servicio->calcularPorDias(3650)['recargo_anios']);

        // 10 años + 1 día -> 1 año-fracción de recargo = 5000.
        $r = $this->servicio->calcularPorDias(3651);
        $this->assertSame('5000.00', $r['recargo_anios']);
        $this->assertSame('25000.00', $r['monto']); // 20000 base + 5000

        // 12 años (3650 + 730) -> 2 años de recargo = 10000.
        $r2 = $this->servicio->calcularPorDias(3650 + 730);
        $this->assertSame('10000.00', $r2['recargo_anios']);
    }
}
