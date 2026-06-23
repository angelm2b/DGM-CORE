<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('solicitud_id')->constrained('solicitudes')->cascadeOnDelete();
            $table->enum('tipo_documento', [
                'PASAPORTE_FOTO',
                'FOTO_2X2',
                'CERT_NO_ANTECEDENTES',
                'POLIZA',
                'CERT_MEDICO',
                'TICKET_RETORNO',
                'SOLVENCIA',
            ]);
            $table->string('formato', 10)->default('JPG'); // RN-11: solo JPG
            $table->string('ruta');
            $table->boolean('validado')->default(false);
            $table->foreignId('validado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();

            $table->index(['solicitud_id', 'tipo_documento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_adjuntos');
    }
};
