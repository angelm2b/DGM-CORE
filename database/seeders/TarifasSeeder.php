<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Servicio;
use App\Models\Tarifa;
use Illuminate\Database\Seeder;

class TarifasSeeder extends Seeder
{
    public function run(): void
    {
        $srv = Servicio::pluck('id', 'codigo');
        $desde = '2024-01-01';

        // [codigo_servicio, concepto, monto, resolucion]
        $tarifas = [
            // SRV-001 Solicitud RT-9: depósito RD$7,000 + carnet.
            ['SRV-001', 'DEPOSITO_EXPEDIENTE', '7000.00', 'RES-DGM-2024-001'],
            ['SRV-001', 'CARNET', '2500.00', 'RES-DGM-2024-001'],
            // SRV-002 Renovación residencia temporal: RD$7,000.
            ['SRV-002', 'DEPOSITO_EXPEDIENTE', '7000.00', 'RES-DGM-2024-002'],
            // SRV-003 Solicitud RP-1: RD$7,000 + penalidad RD$1,000 por mes vencido.
            ['SRV-003', 'DEPOSITO_EXPEDIENTE', '7000.00', 'RES-DGM-2024-003'],
            ['SRV-003', 'PENALIDAD_MES', '1000.00', 'RES-DGM-2024-003'],
            // SRV-004 Renovación RP-1: carnet (1 año el primero, luego 4 años).
            ['SRV-004', 'CARNET', '4000.00', 'RES-DGM-2024-004'],
            // SRV-005 Residencia Definitiva RD-1: 10 años.
            ['SRV-005', 'DEPOSITO_EXPEDIENTE', '15000.00', 'RES-DGM-2024-005'],
            ['SRV-005', 'CARNET', '6000.00', 'RES-DGM-2024-005'],
            // SRV-008 Permiso de reentrada: 1ra prórroga RD$11,200.
            ['SRV-008', 'REENTRADA', '11200.00', 'RES-DGM-2024-008'],
            // SRV-006 Prórroga de estadía de turista: tarifa plana equivalente
            // al rango 31-90 días de la tabla de estadía 2024.
            ['SRV-006', 'PRORROGA', '4000.00', 'RES-DGM-2024-006'],
            // SRV-009 Certificación de salida de menores.
            ['SRV-009', 'CERTIFICACION', '1000.00', 'RES-DGM-2024-009'],
            // SRV-011 Reposición de carnet.
            ['SRV-011', 'CARNET', '2500.00', 'RES-DGM-2024-011'],
            // SRV-012 NG-1 / PADEI / TT-1: 1 año renovable.
            ['SRV-012', 'DEPOSITO_EXPEDIENTE', '5000.00', 'RES-DGM-2024-012'],
            ['SRV-012', 'CARNET', '2500.00', 'RES-DGM-2024-012'],
            // SRV-007 Tasa de estadía: se calcula con tabla_estadia (TablaEstadiaSeeder)
            // vía CalculadoraEstadiaService al emitir la orden de pago.
            // SRV-010 E-Ticket: gratuito por diseño, no lleva tarifa.
        ];

        foreach ($tarifas as [$codigo, $concepto, $monto, $resolucion]) {
            if (! isset($srv[$codigo])) {
                continue;
            }
            Tarifa::updateOrCreate(
                [
                    'servicio_id' => $srv[$codigo],
                    'concepto' => $concepto,
                    'vigente_desde' => $desde,
                ],
                [
                    'monto' => $monto,
                    'moneda' => 'DOP',
                    'vigente_hasta' => null,
                    'resolucion' => $resolucion,
                ],
            );
        }
    }
}
