<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Percentual de redução de base do ICMS-ST na UF de destino — só usado
     * quando a origem aplicou pRedBC no ICMS próprio. O pRedBC do XML é da
     * legislação de ORIGEM e nunca é copiado automaticamente para a base do
     * ST; este override registra a decisão explícita do percentual real do
     * destino ("0" = sem redução aplicável).
     */
    public function up(): void
    {
        Schema::create('st_overrides_base', function (Blueprint $table) {
            $table->id();
            $table->string('chave_acesso', 44);
            $table->string('nfe_item');
            $table->decimal('percentual_reducao_pct', 5, 2)->nullable();
            $table->text('observacao')->nullable();
            $table->string('decidido_por')->nullable();
            $table->dateTime('decidido_em')->nullable();
            $table->timestamps();

            $table->unique(['chave_acesso', 'nfe_item']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('st_overrides_base');
    }
};
