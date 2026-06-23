<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Oficina;
use Illuminate\Database\Seeder;

class OficinasSeeder extends Seeder
{
    public function run(): void
    {
        $oficinas = [
            ['codigo' => 'OF-SDQ-01', 'nombre' => 'Sede Central Santo Domingo', 'localidad' => 'Santo Domingo de Guzmán'],
            ['codigo' => 'OF-SDQ-02', 'nombre' => 'Oficina Megacentro', 'localidad' => 'Santo Domingo Este'],
            ['codigo' => 'OF-STI-01', 'nombre' => 'Oficina Santiago', 'localidad' => 'Santiago de los Caballeros'],
            ['codigo' => 'OF-PUJ-01', 'nombre' => 'Oficina Punta Cana', 'localidad' => 'Punta Cana, La Altagracia'],
            ['codigo' => 'OF-PSP-01', 'nombre' => 'Oficina Puerto Plata', 'localidad' => 'Puerto Plata'],
        ];

        foreach ($oficinas as $of) {
            Oficina::updateOrCreate(['codigo' => $of['codigo']], $of);
        }
    }
}
