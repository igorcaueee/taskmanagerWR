<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Decisão manual do contador de que um item genuinamente não é sujeito
     * a ICMS-ST (produto fora de qualquer segmento de ST, não porque uma
     * regra foi revogada por decreto -- esse caso já é automático via
     * st_regras_cest.situacao). Igual aos demais overrides: nunca aplicado
     * sem fundamento e sem registro de quem/quando decidiu.
     */
    public function up(): void
    {
        Schema::table('st_overrides_cest', function (Blueprint $table) {
            $table->boolean('nao_sujeito_st')->default(false)->after('cest_atribuido');
        });
    }

    public function down(): void
    {
        Schema::table('st_overrides_cest', function (Blueprint $table) {
            $table->dropColumn('nao_sujeito_st');
        });
    }
};
