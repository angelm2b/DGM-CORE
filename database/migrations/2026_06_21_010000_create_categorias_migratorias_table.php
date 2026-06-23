<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_migratorias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique(); // RT-3, RT-9, RP-1, RD-1, TURISTA...
            $table->string('nombre');
            $table->enum('grupo', ['RESIDENTE', 'NO_RESIDENTE']);
            $table->unsignedSmallInteger('vigencia_meses')->nullable();
            $table->boolean('permite_renovacion')->default(false);
            // Autorreferencia: codifica el único cambio de categoría válido en el país (RT-9 -> RP-1).
            $table->foreignId('permite_cambio_a_id')
                ->nullable()
                ->constrained('categorias_migratorias')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_migratorias');
    }
};
