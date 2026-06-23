<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarifas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->enum('concepto', [
                'DEPOSITO_EXPEDIENTE',
                'CARNET',
                'PENALIDAD_MES',
                'TASA_ESTADIA',
                'REENTRADA',
            ]);
            $table->decimal('monto', 12, 2);
            $table->char('moneda', 3)->default('DOP');
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->string('resolucion')->nullable();
            $table->timestamps();

            $table->index(['servicio_id', 'concepto', 'vigente_desde']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifas');
    }
};
