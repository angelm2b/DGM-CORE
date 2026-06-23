<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CategoriaMigratoria;
use Illuminate\Database\Seeder;

class CategoriasMigratoriasSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['codigo' => 'RT-3', 'nombre' => 'Residencia Temporal - Rentista', 'grupo' => 'RESIDENTE', 'vigencia_meses' => 12, 'permite_renovacion' => true],
            ['codigo' => 'RT-4', 'nombre' => 'Residencia Temporal - Inversionista', 'grupo' => 'RESIDENTE', 'vigencia_meses' => 12, 'permite_renovacion' => true],
            ['codigo' => 'RT-7', 'nombre' => 'Residencia Temporal - Trabajador', 'grupo' => 'RESIDENTE', 'vigencia_meses' => 12, 'permite_renovacion' => true],
            ['codigo' => 'RT-9', 'nombre' => 'Residencia Temporal - Familiar de dominicano', 'grupo' => 'RESIDENTE', 'vigencia_meses' => 12, 'permite_renovacion' => true],
            ['codigo' => 'RP-1', 'nombre' => 'Residencia Permanente', 'grupo' => 'RESIDENTE', 'vigencia_meses' => 48, 'permite_renovacion' => true],
            ['codigo' => 'RD-1', 'nombre' => 'Residencia Definitiva', 'grupo' => 'RESIDENTE', 'vigencia_meses' => 120, 'permite_renovacion' => true],
            ['codigo' => 'TURISTA', 'nombre' => 'Turista', 'grupo' => 'NO_RESIDENTE', 'vigencia_meses' => 1, 'permite_renovacion' => false],
            ['codigo' => 'NG-1', 'nombre' => 'No residente - Negocios', 'grupo' => 'NO_RESIDENTE', 'vigencia_meses' => 12, 'permite_renovacion' => true],
            ['codigo' => 'TT-1', 'nombre' => 'No residente - Tripulante de transporte', 'grupo' => 'NO_RESIDENTE', 'vigencia_meses' => 12, 'permite_renovacion' => true],
            ['codigo' => 'PADEI', 'nombre' => 'Plan de Normalización (PADEI)', 'grupo' => 'NO_RESIDENTE', 'vigencia_meses' => 12, 'permite_renovacion' => true],
        ];

        foreach ($categorias as $cat) {
            CategoriaMigratoria::updateOrCreate(['codigo' => $cat['codigo']], $cat);
        }

        // RN-05: el único cambio de categoría permitido dentro del país es RT-9 -> RP-1.
        $rt9 = CategoriaMigratoria::where('codigo', 'RT-9')->first();
        $rp1 = CategoriaMigratoria::where('codigo', 'RP-1')->first();
        if ($rt9 && $rp1) {
            $rt9->update(['permite_cambio_a_id' => $rp1->id]);
        }
    }
}
