<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('cnae_principal')->nullable()->after('atividade')->comment('código do CNAE fiscal principal (cache da BrasilAPI)');
            $table->json('cnae_secundarios')->nullable()->after('cnae_principal')->comment('códigos dos CNAEs secundários (cache da BrasilAPI)');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['cnae_principal', 'cnae_secundarios']);
        });
    }
};
