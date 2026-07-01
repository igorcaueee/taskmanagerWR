<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redes_permitidas', function (Blueprint $table) {
            $table->id();
            $table->string('cidr');
            $table->string('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->foreignId('criado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redes_permitidas');
    }
};
