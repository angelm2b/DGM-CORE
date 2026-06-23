<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_migratorios', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->enum('tipo', ['E', 'S']); // Entrada / Salida
            $table->foreignId('punto_control_id')->constrained('puntos_control')->restrictOnDelete();
            $table->timestamp('fecha_hora');
            $table->string('medio')->nullable(); // nº de vuelo / buque
            $table->string('eticket_codigo')->nullable();
            $table->unsignedSmallInteger('dias_autorizados')->nullable();
            $table->foreignId('oficial_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();

            $table->index(['persona_id', 'fecha_hora']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_migratorios');
    }
};
