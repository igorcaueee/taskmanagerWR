<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('precificacao_cenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('precificacao_produto_id')->constrained('precificacao_produtos')->cascadeOnDelete();
            $table->string('nome')->nullable();
            $table->string('uf_compra', 2);
            $table->string('uf_venda', 2);
            $table->decimal('valor_compra_total', 12, 2);
            $table->decimal('quantidade', 12, 3);
            $table->decimal('frete_compra', 12, 2)->default(0);
            $table->decimal('ipi_pct', 5, 2)->default(0);
            $table->decimal('markup_pct', 6, 2)->default(0);
            $table->decimal('comissao_pct', 5, 2)->default(0);
            $table->decimal('frete_venda_pct', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('precificacao_cenarios');
    }
};
