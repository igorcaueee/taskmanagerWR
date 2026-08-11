<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tributação do ISSQN sobre o serviço (DPS/infDPS/valores/trib/tribMun/tribISSQN):
 * 1-Operação tributável, 2-Imunidade, 3-Exportação de serviço, 4-Não incidência.
 * Antes ficava fixo em "1" no NfseDpsBuilderService — vira configurável por emissão.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nfse_emissoes', function (Blueprint $table) {
            $table->unsignedTinyInteger('trib_issqn')->default(1)->after('iss_retido');
        });
    }

    public function down(): void
    {
        Schema::table('nfse_emissoes', function (Blueprint $table) {
            $table->dropColumn('trib_issqn');
        });
    }
};
