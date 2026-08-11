<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoria dos eventos (cancelamento/substituição) enviados via
 * POST /nfse/{chaveAcesso}/eventos para uma NFS-e emitida pela plataforma.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nfse_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nfse_emissao_id')->constrained('nfse_emissoes')->cascadeOnDelete();
            $table->string('tipo_evento');
            $table->string('motivo')->nullable();
            $table->longText('xml_evento')->nullable();
            $table->longText('resposta')->nullable();
            $table->enum('status', ['enviado', 'aceito', 'rejeitado'])->default('enviado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nfse_eventos');
    }
};
