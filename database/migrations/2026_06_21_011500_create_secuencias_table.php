<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Numeración correlativa sin colisiones bajo concurrencia (MySQL no tiene SEQUENCE).
        // Se consulta con SELECT ... FOR UPDATE dentro de una transacción.
        Schema::create('secuencias', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 40); // EXPEDIENTE, COMPROBANTE, SERIE_DOC...
            $table->unsignedSmallInteger('anio');
            $table->unsignedBigInteger('ultimo_valor')->default(0);
            $table->timestamps();

            $table->unique(['clave', 'anio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secuencias');
    }
};
