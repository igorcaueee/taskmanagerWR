<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtos_financeiros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('nome');
            $table->string('codigo')->nullable();
            $table->string('categoria')->nullable();
            $table->decimal('preco_custo', 15, 2)->default(0);
            $table->decimal('preco_venda', 15, 2)->default(0);
            $table->decimal('estoque_atual', 15, 3)->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index(['cliente_id', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos_financeiros');
    }
};
