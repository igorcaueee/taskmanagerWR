<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persiste cada NF-e/NFC-e/CT-e já recebido via distribuição DFe (nacional ou
 * SEFAZ-RS). A distribuição é um feed incremental por NSU, não uma busca
 * histórica por data — sem esta tabela, documentos de períodos já
 * "consumidos" pelo NSU nunca mais voltam pela API.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_fiscais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('chave_acesso', 44)->unique();
            $table->enum('tipo', ['nfe', 'nfce', 'cte']);
            $table->enum('origem', ['nacional', 'rs']);
            $table->string('nsu')->nullable();
            $table->string('numero')->nullable();
            $table->date('data_emissao')->nullable();
            $table->string('emitente_nome')->nullable();
            $table->string('emitente_doc')->nullable();
            $table->decimal('valor', 15, 2)->nullable();
            $table->string('situacao')->nullable();
            $table->longText('xml_content')->nullable();
            $table->timestamps();

            $table->index(['cliente_id', 'tipo', 'data_emissao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_fiscais');
    }
};
