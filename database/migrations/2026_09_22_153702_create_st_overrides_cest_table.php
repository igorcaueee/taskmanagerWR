<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Atribuição/correção manual de CEST por item de NF-e — usado quando o
     * emitente não informou CEST, ou informou um CEST identificado como
     * incorreto. Nunca é inferido automaticamente pelo motor a partir do
     * NCM; sempre uma decisão explícita, registrada com quem/quando/porquê.
     */
    public function up(): void
    {
        Schema::create('st_overrides_cest', function (Blueprint $table) {
            $table->id();
            $table->string('chave_acesso', 44);
            $table->string('nfe_item');
            $table->string('ncm', 8)->nullable();
            $table->string('produto')->nullable();
            $table->string('cest_sugerido', 9)->nullable();
            // null = ainda só sugestão, não confirmado.
            $table->string('cest_atribuido', 9)->nullable();
            $table->text('fundamento')->nullable();
            $table->text('observacao')->nullable();
            $table->string('decidido_por')->nullable();
            $table->dateTime('decidido_em')->nullable();
            $table->timestamps();

            $table->unique(['chave_acesso', 'nfe_item']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('st_overrides_cest');
    }
};
