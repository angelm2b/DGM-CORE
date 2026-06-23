<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Datos maestros del CORE. Todos los seeders son idempotentes.
     */
    public function run(): void
    {
        $this->call([
            CategoriasMigratoriasSeeder::class,
            OficinasSeeder::class,
            PuntosControlSeeder::class,
            ServiciosSeeder::class,
            TarifasSeeder::class,
            TablaEstadiaSeeder::class,
            UsuariosRolesSeeder::class,
        ]);
    }
}
