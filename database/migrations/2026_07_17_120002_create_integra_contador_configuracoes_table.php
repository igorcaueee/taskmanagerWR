<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configuração única do escritório para a API Integra Contador (SERPRO) — usa a
 * mesma procuração eletrônica única para toda a carteira de clientes (não há
 * certificado por cliente aqui, diferente do certificado de NFS-e).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integra_contador_configuracoes', function (Blueprint $table) {
            $table->id();
            $table->string('cnpj_contratante');
            $table->string('arquivo_certificado'); // path relativo ao storage/app, fora do público
            $table->text('senha_certificado');      // criptografado via cast
            $table->text('consumer_key');            // criptografado via cast
            $table->text('consumer_secret');         // criptografado via cast
            $table->enum('ambiente', ['trial', 'producao'])->default('trial');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integra_contador_configuracoes');
    }
};
