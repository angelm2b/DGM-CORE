<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tarifas escalonadas de sobreestadía. El recargo a partir de 10 años
        // (+RD$5,000 por año o fracción) se calcula en CalculadoraEstadiaService.
        Schema::create('tabla_estadia', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('dias_desde');
            $table->unsignedInteger('dias_hasta')->nullable(); // null = último rango abierto
            $table->decimal('monto', 12, 2);
            $table->timestamps();

            $table->index('dias_desde');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tabla_estadia');
    }
};
