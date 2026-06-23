<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\CalculadoraPenalidadService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** RN-04: RD$1,000 por mes (o fracción) de residencia temporal vencida. */
class CalculadoraPenalidadServiceTest extends TestCase
{
    private CalculadoraPenalidadService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new CalculadoraPenalidadService;
    }

    public function test_sin_vencimiento_no_hay_penalidad(): void
    {
        $r = $this->servicio->calcular(Carbon::parse('2026-12-31'), Carbon::parse('2026-06-01'));

        $this->assertSame(0, $r['meses_vencidos']);
        $this->assertSame('0.00', $r['monto']);
    }

    public function test_tres_meses_completos_vencidos(): void
    {
        $r = $this->servicio->calcular(Carbon::parse('2026-01-01'), Carbon::parse('2026-04-01'));

        $this->assertSame(3, $r['meses_vencidos']);
        $this->assertSame('3000.00', $r['monto']);
    }

    public function test_fraccion_de_mes_cuenta_como_mes_completo(): void
    {
        // 2 meses y 10 días -> 3 meses.
        $r = $this->servicio->calcular(Carbon::parse('2026-01-01'), Carbon::parse('2026-03-11'));

        $this->assertSame(3, $r['meses_vencidos']);
        $this->assertSame('3000.00', $r['monto']);
    }
}
