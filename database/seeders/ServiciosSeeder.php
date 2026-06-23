<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CategoriaMigratoria;
use App\Models\Servicio;
use Illuminate\Database\Seeder;

class ServiciosSeeder extends Seeder
{
    public function run(): void
    {
        $cat = CategoriaMigratoria::pluck('id', 'codigo');

        $servicios = [
            ['codigo' => 'SRV-001', 'nombre' => 'Solicitud de Residencia Temporal RT-9', 'categoria' => 'RT-9', 'requiere_cita' => true, 'dias_sla' => 90, 'canal' => 'AMBOS'],
            ['codigo' => 'SRV-002', 'nombre' => 'Renovación de Residencia Temporal', 'categoria' => null, 'requiere_cita' => true, 'dias_sla' => 90, 'canal' => 'AMBOS'],
            ['codigo' => 'SRV-003', 'nombre' => 'Solicitud de Residencia Permanente RP-1', 'categoria' => 'RP-1', 'requiere_cita' => true, 'dias_sla' => 90, 'canal' => 'AMBOS'],
            ['codigo' => 'SRV-004', 'nombre' => 'Renovación de Residencia Permanente RP-1', 'categoria' => 'RP-1', 'requiere_cita' => true, 'dias_sla' => 90, 'canal' => 'AMBOS'],
            ['codigo' => 'SRV-005', 'nombre' => 'Residencia Definitiva RD-1', 'categoria' => 'RD-1', 'requiere_cita' => true, 'dias_sla' => 120, 'canal' => 'AMBOS'],
            ['codigo' => 'SRV-006', 'nombre' => 'Prórroga de estadía de turista', 'categoria' => 'TURISTA', 'requiere_cita' => false, 'dias_sla' => 3, 'canal' => 'AMBOS'],
            ['codigo' => 'SRV-007', 'nombre' => 'Tasa de estadía (sobreestadía)', 'categoria' => null, 'requiere_cita' => false, 'dias_sla' => 0, 'canal' => 'CAJA'],
            ['codigo' => 'SRV-008', 'nombre' => 'Permiso de reentrada', 'categoria' => null, 'requiere_cita' => false, 'dias_sla' => 5, 'canal' => 'AMBOS'],
            ['codigo' => 'SRV-009', 'nombre' => 'Certificación de salida de menores', 'categoria' => null, 'requiere_cita' => true, 'dias_sla' => 5, 'canal' => 'AMBOS'],
            ['codigo' => 'SRV-010', 'nombre' => 'E-Ticket', 'categoria' => null, 'requiere_cita' => false, 'dias_sla' => 0, 'canal' => 'WEB'],
            ['codigo' => 'SRV-011', 'nombre' => 'Reposición de carnet (pérdida/robo/deterioro)', 'categoria' => null, 'requiere_cita' => true, 'dias_sla' => 15, 'canal' => 'AMBOS'],
            ['codigo' => 'SRV-012', 'nombre' => 'No residente NG-1 / PADEI / TT-1', 'categoria' => 'NG-1', 'requiere_cita' => true, 'dias_sla' => 30, 'canal' => 'AMBOS'],
        ];

        foreach ($servicios as $s) {
            Servicio::updateOrCreate(
                ['codigo' => $s['codigo']],
                [
                    'nombre' => $s['nombre'],
                    'categoria_migratoria_id' => $s['categoria'] ? ($cat[$s['categoria']] ?? null) : null,
                    'requiere_cita' => $s['requiere_cita'],
                    'dias_sla' => $s['dias_sla'],
                    'canal' => $s['canal'],
                    'activo' => true,
                ],
            );
        }
    }
}
