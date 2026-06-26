<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->boolean('conta_azul_conectada')->default(false)->after('regime_tributario');
            $table->string('conta_azul_client_id')->nullable()->after('conta_azul_conectada');
            $table->text('conta_azul_access_token')->nullable()->after('conta_azul_client_id');
            $table->text('conta_azul_refresh_token')->nullable()->after('conta_azul_access_token');
            $table->datetime('conta_azul_token_expira_em')->nullable()->after('conta_azul_refresh_token');
            $table->datetime('conta_azul_ultima_sincronizacao')->nullable()->after('conta_azul_token_expira_em');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn([
                'conta_azul_conectada',
                'conta_azul_client_id',
                'conta_azul_access_token',
                'conta_azul_refresh_token',
                'conta_azul_token_expira_em',
                'conta_azul_ultima_sincronizacao',
            ]);
        });
    }
};
