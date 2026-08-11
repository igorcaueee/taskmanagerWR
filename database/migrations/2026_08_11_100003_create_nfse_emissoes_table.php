<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro central de cada NFS-e emitida pela plataforma via Sistema Nacional
 * NFS-e (DPS enviada em NfseEmissaoService::emitir). O tomador é avulso por
 * emissão (sem cadastro próprio) e o bloco IBS/CBS da reforma tributária fica
 * fora do escopo — DPS só com ISSQN clássico.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nfse_emissoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->enum('ambiente', ['homologacao', 'producao']);
            $table->string('serie');
            $table->unsignedInteger('numero');
            $table->enum('status', ['rascunho', 'enviada', 'autorizada', 'rejeitada', 'cancelada', 'substituida'])
                ->default('rascunho');

            $table->enum('tomador_tipo_doc', ['CPF', 'CNPJ']);
            $table->string('tomador_cpf_cnpj', 14);
            $table->string('tomador_nome');
            $table->string('tomador_email')->nullable();
            $table->string('tomador_cep', 8)->nullable();
            $table->string('tomador_logradouro')->nullable();
            $table->string('tomador_numero')->nullable();
            $table->string('tomador_complemento')->nullable();
            $table->string('tomador_bairro')->nullable();
            $table->string('tomador_codigo_municipio_ibge', 7)->nullable();

            $table->string('codigo_tributacao_nacional', 6);
            $table->string('descricao_servico', 1000);
            $table->string('codigo_municipio_prestacao', 7);

            $table->decimal('valor_servico', 15, 2);
            $table->decimal('aliquota', 5, 2)->nullable();
            $table->decimal('valor_iss', 15, 2)->nullable();
            $table->boolean('iss_retido')->default(false);
            $table->decimal('desconto_incondicional', 15, 2)->nullable();

            $table->date('dcompet');

            $table->string('chave_acesso', 50)->nullable();
            $table->string('numero_nfse', 13)->nullable();
            $table->longText('xml_dps')->nullable();
            $table->longText('xml_nfse')->nullable();
            $table->text('erro_mensagem')->nullable();

            $table->string('chave_nfse_substituida', 50)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nfse_emissoes');
    }
};
