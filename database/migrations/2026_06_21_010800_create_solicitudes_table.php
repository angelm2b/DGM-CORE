<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('expediente_id')->constrained('expedientes')->cascadeOnDelete();
            $table->foreignId('servicio_id')->constrained('servicios')->restrictOnDelete();
            $table->enum('canal_origen', ['WEB', 'CAJA']);
            // Gestionado por la máquina de estados (spatie/laravel-model-states).
            $table->string('estado_actual', 40)->default('BORRADOR');
            $table->timestamp('fecha_creacion')->useCurrent();
            // Base de la regla de caducidad (RN-07).
            $table->timestamp('fecha_ultima_accion')->useCurrent();
            $table->timestamp('fecha_cita')->nullable();
            $table->foreignId('oficina_id')->constrained('oficinas')->restrictOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('estado_actual');
            $table->index('fecha_ultima_accion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};
