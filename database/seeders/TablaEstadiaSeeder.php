<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TablaEstadia;
use Illuminate\Database\Seeder;

class TablaEstadiaSeeder extends Seeder
{
    public function run(): void
    {
        // Rangos escalonados de sobreestadía (días de exceso -> monto en DOP).
        // El recargo de +RD$5,000 por año o fracción desde 10 años se aplica
        // adicionalmente en CalculadoraEstadiaService.
        $rangos = [
            ['dias_desde' => 1, 'dias_hasta' => 10, 'monto' => '1000.00'],
            ['dias_desde' => 11, 'dias_hasta' => 30, 'monto' => '2500.00'],
            ['dias_desde' => 31, 'dias_hasta' => 90, 'monto' => '4000.00'],
            ['dias_desde' => 91, 'dias_hasta' => 180, 'monto' => '6000.00'],
            ['dias_desde' => 181, 'dias_hasta' => 365, 'monto' => '10000.00'],
            ['dias_desde' => 366, 'dias_hasta' => null, 'monto' => '20000.00'],
        ];

        foreach ($rangos as $r) {
            TablaEstadia::updateOrCreate(['dias_desde' => $r['dias_desde']], $r);
        }
    }
}
