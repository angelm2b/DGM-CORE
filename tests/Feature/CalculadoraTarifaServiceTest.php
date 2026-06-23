<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Servicio;
use App\Models\Tarifa;
use App\Services\CalculadoraTarifaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CalculadoraTarifaServiceTest extends TestCase
{
    use RefreshDatabase;

    private CalculadoraTarifaService $servicio;

    private Servicio $srv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new CalculadoraTarifaService;
        $this->srv = Servicio::create([
            'codigo' => 'SRV-TST', 'nombre' => 'Prueba', 'requiere_cita' => false,
            'dias_sla' => 10, 'canal' => 'AMBOS', 'activo' => true,
        ]);
    }

    public function test_devuelve_la_tarifa_vigente_a_la_fecha(): void
    {
        Tarifa::create(['servicio_id' => $this->srv->id, 'concepto' => 'DEPOSITO_EXPEDIENTE', 'monto' => '5000.00', 'vigente_desde' => '2023-01-01', 'vigente_hasta' => '2023-12-31']);
        Tarifa::create(['servicio_id' => $this->srv->id, 'concepto' => 'DEPOSITO_EXPEDIENTE', 'monto' => '7000.00', 'vigente_desde' => '2024-01-01', 'vigente_hasta' => null]);

        $this->assertSame('5000.00', $this->servicio->montoVigente($this->srv->id, 'DEPOSITO_EXPEDIENTE', Carbon::parse('2023-06-01')));
        $this->assertSame('7000.00', $this->servicio->montoVigente($this->srv->id, 'DEPOSITO_EXPEDIENTE', Carbon::parse('2025-06-01')));
    }

    public function test_sin_tarifa_aplicable_devuelve_cero(): void
    {
        $this->assertSame('0.00', $this->servicio->montoVigente($this->srv->id, 'CARNET', Carbon::parse('2025-01-01')));
    }
}
