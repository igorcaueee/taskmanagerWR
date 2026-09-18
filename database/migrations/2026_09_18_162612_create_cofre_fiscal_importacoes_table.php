<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cofre_fiscal_importacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('arquivo_path');
            $table->string('arquivo_nome_original');
            $table->string('status')->default('pendente'); // pendente|processando|concluido|falhou
            $table->unsignedInteger('total_xmls')->default(0);
            $table->unsignedInteger('processados')->default(0);
            $table->unsignedInteger('importados')->default(0);
            $table->unsignedInteger('atualizados')->default(0);
            $table->unsignedInteger('ignorados_invalidos')->default(0);
            $table->unsignedInteger('ignorados_outro_cliente')->default(0);
            $table->unsignedInteger('ignorados_cnpj_divergente')->default(0);
            $table->text('erro')->nullable();
            $table->timestamp('iniciado_em')->nullable();
            $table->timestamp('finalizado_em')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cofre_fiscal_importacoes');
    }
};
