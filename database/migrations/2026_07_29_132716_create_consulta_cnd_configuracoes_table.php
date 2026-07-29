<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configuração única do escritório para a API "Consulta CND" (SERPRO) —
 * produto contratado à parte do Integra Contador, com autenticação OAuth2
 * simples (Basic Auth de consumer key/secret, sem certificado/mTLS).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consulta_cnd_configuracoes', function (Blueprint $table) {
            $table->id();
            $table->text('consumer_key');
            $table->text('consumer_secret');
            $table->enum('ambiente', ['trial', 'producao'])->default('trial');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consulta_cnd_configuracoes');
    }
};
