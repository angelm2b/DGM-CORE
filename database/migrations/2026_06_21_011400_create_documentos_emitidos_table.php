<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_emitidos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('solicitud_id')->constrained('solicitudes')->cascadeOnDelete();
            $table->enum('tipo', [
                'CARNET_RT9',
                'CARNET_RP1',
                'PRORROGA',
                'CERT_SALIDA_MENOR',
                'PERMISO_REENTRADA',
                'CARNET_RD1',
                'CERTIFICACION',
            ]);
            $table->string('numero_serie', 40)->unique();
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento')->nullable();
            $table->enum('estado', ['VIGENTE', 'VENCIDO', 'REVOCADO', 'REPUESTO'])->default('VIGENTE');
            $table->timestamps();

            $table->index(['solicitud_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_emitidos');
    }
};
