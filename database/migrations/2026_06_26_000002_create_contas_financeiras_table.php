<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas_financeiras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('conta_azul_id')->nullable()->index();
            $table->string('nome');
            $table->string('tipo')->nullable();
            $table->decimal('saldo_atual', 15, 2)->default(0);
            $table->boolean('ativa')->default(true);
            $table->timestamp('atualizado_em')->nullable();
            $table->timestamps();

            $table->unique(['cliente_id', 'conta_azul_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_financeiras');
    }
};
