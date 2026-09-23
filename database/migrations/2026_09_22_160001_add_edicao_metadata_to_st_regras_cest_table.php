<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registra quem/quando editou uma regra pela tela de "Regras
     * cadastradas" (Fase 2) -- as regras seedadas pelo StRegrasCestSeeder
     * ficam com esses campos nulos até a primeira edição manual.
     */
    public function up(): void
    {
        Schema::table('st_regras_cest', function (Blueprint $table) {
            $table->string('editado_por')->nullable()->after('fonte_legal');
            $table->dateTime('editado_em')->nullable()->after('editado_por');
        });
    }

    public function down(): void
    {
        Schema::table('st_regras_cest', function (Blueprint $table) {
            $table->dropColumn(['editado_por', 'editado_em']);
        });
    }
};
