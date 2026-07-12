<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Agrega PRORROGA (SRV-006) y CERTIFICACION (SRV-009) a los conceptos
    // tarifables para que todos los servicios puedan emitir órdenes de pago.
    public function up(): void
    {
        DB::statement("ALTER TABLE tarifas MODIFY concepto ENUM('DEPOSITO_EXPEDIENTE', 'CARNET', 'PENALIDAD_MES', 'TASA_ESTADIA', 'REENTRADA', 'PRORROGA', 'CERTIFICACION') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE tarifas MODIFY concepto ENUM('DEPOSITO_EXPEDIENTE', 'CARNET', 'PENALIDAD_MES', 'TASA_ESTADIA', 'REENTRADA') NOT NULL");
    }
};
