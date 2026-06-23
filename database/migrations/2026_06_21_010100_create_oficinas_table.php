<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oficinas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique(); // OF-SDQ-01...
            $table->string('nombre');
            $table->string('localidad');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oficinas');
    }
};
