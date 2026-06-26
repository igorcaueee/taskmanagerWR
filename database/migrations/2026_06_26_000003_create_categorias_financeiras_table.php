<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_financeiras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('conta_azul_id')->nullable()->index();
            $table->string('nome');
            $table->string('tipo')->default('receita'); // receita | despesa
            $table->unsignedBigInteger('categoria_pai_id')->nullable();
            $table->timestamps();

            $table->unique(['cliente_id', 'conta_azul_id']);
            $table->foreign('categoria_pai_id')->references('id')->on('categorias_financeiras')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_financeiras');
    }
};
