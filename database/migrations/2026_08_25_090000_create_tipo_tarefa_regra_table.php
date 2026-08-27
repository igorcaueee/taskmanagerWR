<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipo_tarefa_regra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_tarefa_id')->constrained('tipos_tarefa')->cascadeOnDelete();
            $table->string('regime_tributario')->nullable()->comment('null = aplica a qualquer regime');
            $table->json('cnae_prefixos')->nullable()->comment('lista de prefixos de CNAE que disparam a regra; vazio = qualquer atividade');
            $table->enum('frequencia', ['nenhuma', 'diaria', 'semanal', 'mensal', 'trimestral', 'semestral', 'anual'])->default('mensal');
            $table->unsignedTinyInteger('dia_vencimento')->nullable()->comment('dia do mês padrão de vencimento da obrigação');
            $table->foreignId('departamento_id')->nullable()->constrained('departamentos')->nullOnDelete();
            $table->foreignId('responsavel_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['tipo_tarefa_id', 'regime_tributario']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_tarefa_regra');
    }
};
