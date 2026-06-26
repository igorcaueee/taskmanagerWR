<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centros_custo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('conta_azul_id')->nullable()->index();
            $table->string('codigo')->nullable();
            $table->string('nome');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['cliente_id', 'conta_azul_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centros_custo');
    }
};
