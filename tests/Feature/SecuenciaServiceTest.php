<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\SecuenciaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecuenciaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_genera_correlativos_consecutivos(): void
    {
        $s = new SecuenciaService;
        $this->assertSame(1, $s->siguiente('PRUEBA', 2026));
        $this->assertSame(2, $s->siguiente('PRUEBA', 2026));
        $this->assertSame(3, $s->siguiente('PRUEBA', 2026));
        // Clave/año distinto reinicia.
        $this->assertSame(1, $s->siguiente('PRUEBA', 2027));
        $this->assertSame(1, $s->siguiente('OTRA', 2026));
    }

    public function test_formatos_de_numeracion(): void
    {
        $s = new SecuenciaService;
        $this->assertSame('DGM-2026-000001', $s->numeroExpediente(2026));
        $this->assertSame('CMP-2026-000001', $s->numeroComprobante(2026));
        $this->assertSame('DOC-2026-000001', $s->numeroSerie(2026));
        $this->assertSame('DGM-2026-000002', $s->numeroExpediente(2026));
    }

    public function test_no_se_repiten_bajo_muchas_llamadas(): void
    {
        $s = new SecuenciaService;
        $vistos = [];
        for ($i = 0; $i < 50; $i++) {
            $vistos[] = $s->siguiente('MASIVO', 2026);
        }
        $this->assertSame(range(1, 50), $vistos);
        $this->assertSame(50, count(array_unique($vistos)));
    }
}
