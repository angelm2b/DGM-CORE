<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('tipo_documento', ['PASAPORTE', 'CEDULA']);
            $table->string('numero_documento', 50);
            $table->char('nacionalidad', 3); // ISO-3166 alfa-3
            $table->string('nombres');
            $table->string('apellidos');
            $table->date('fecha_nacimiento');
            $table->enum('sexo', ['M', 'F', 'X'])->nullable();
            $table->string('email')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->date('pasaporte_vence')->nullable();
            $table->foreignId('categoria_migratoria_id')
                ->nullable()
                ->constrained('categorias_migratorias')
                ->nullOnDelete();
            $table->enum('estatus_migratorio', ['REGULAR', 'IRREGULAR', 'EN_TRAMITE'])
                ->default('EN_TRAMITE');
            $table->timestamps();

            $table->unique(['tipo_documento', 'numero_documento', 'nacionalidad'], 'uq_persona_documento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
