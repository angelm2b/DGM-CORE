<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('orden_pago_id')->constrained('ordenes_pago')->cascadeOnDelete();
            $table->decimal('monto', 12, 2);
            $table->enum('metodo', ['EFECTIVO', 'TARJETA', 'TRANSFERENCIA', 'PORTAL']);
            $table->string('referencia_externa')->nullable();
            $table->string('numero_comprobante', 30)->unique();
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
