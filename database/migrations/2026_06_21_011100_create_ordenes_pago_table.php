<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_pago', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('solicitud_id')->constrained('solicitudes')->cascadeOnDelete();
            $table->json('detalle'); // desglose de conceptos y montos
            $table->decimal('monto_total', 12, 2);
            $table->char('moneda', 3)->default('DOP');
            $table->enum('estado', ['PENDIENTE', 'PAGADA', 'ANULADA', 'VENCIDA'])->default('PENDIENTE');
            $table->timestamp('fecha_emision')->useCurrent();
            $table->timestamp('fecha_vencimiento')->nullable();
            $table->timestamps();

            $table->index(['solicitud_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordenes_pago');
    }
};
