<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique(); // SRV-001...SRV-012
            $table->string('nombre');
            $table->foreignId('categoria_migratoria_id')
                ->nullable()
                ->constrained('categorias_migratorias')
                ->nullOnDelete();
            $table->boolean('requiere_cita')->default(false);
            $table->unsignedSmallInteger('dias_sla')->default(0);
            $table->enum('canal', ['WEB', 'CAJA', 'AMBOS'])->default('AMBOS');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};
