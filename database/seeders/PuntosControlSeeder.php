<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PuntoControl;
use Illuminate\Database\Seeder;

class PuntosControlSeeder extends Seeder
{
    public function run(): void
    {
        $puntos = [
            ['codigo' => 'AILA', 'nombre' => 'Aeropuerto Internacional Las Américas (AILA)', 'tipo' => 'AEREO'],
            ['codigo' => 'PUJ', 'nombre' => 'Aeropuerto Internacional Punta Cana', 'tipo' => 'AEREO'],
            ['codigo' => 'STI', 'nombre' => 'Aeropuerto Internacional del Cibao (Santiago)', 'tipo' => 'AEREO'],
            ['codigo' => 'POP', 'nombre' => 'Aeropuerto Internacional Gregorio Luperón (Puerto Plata)', 'tipo' => 'AEREO'],
            ['codigo' => 'PTO-SDQ', 'nombre' => 'Puerto de Santo Domingo', 'tipo' => 'MARITIMO'],
            ['codigo' => 'PTO-SANSOUCI', 'nombre' => 'Terminal Sans Souci', 'tipo' => 'MARITIMO'],
            ['codigo' => 'FRO-DAJABON', 'nombre' => 'Paso fronterizo Dajabón', 'tipo' => 'TERRESTRE'],
            ['codigo' => 'FRO-JIMANI', 'nombre' => 'Paso fronterizo Jimaní', 'tipo' => 'TERRESTRE'],
            ['codigo' => 'FRO-ELIAS', 'nombre' => 'Paso fronterizo Elías Piña', 'tipo' => 'TERRESTRE'],
        ];

        foreach ($puntos as $p) {
            PuntoControl::updateOrCreate(['codigo' => $p['codigo']], $p);
        }
    }
}
