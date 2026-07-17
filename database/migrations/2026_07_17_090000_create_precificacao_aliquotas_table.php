<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('precificacao_aliquotas', function (Blueprint $table) {
            $table->id();
            $table->string('ncm', 20);
            $table->string('cest', 20)->nullable();
            $table->string('descricao')->nullable();
            $table->string('uf_referencia', 2)->nullable();
            $table->decimal('aliquota_icms_interna', 5, 2)->default(0);
            $table->boolean('aplica_st')->default(false);
            $table->decimal('aliquota_icms_st', 5, 2)->default(0);
            $table->enum('icms_venda_regra', ['tributado', 'st_ja_paga'])->default('tributado');
            $table->enum('regime_pis_cofins', ['monofasico', 'tributado'])->default('tributado');
            $table->decimal('aliquota_pis', 5, 2)->default(0);
            $table->decimal('aliquota_cofins', 5, 2)->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['ncm', 'cest', 'uf_referencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('precificacao_aliquotas');
    }
};
