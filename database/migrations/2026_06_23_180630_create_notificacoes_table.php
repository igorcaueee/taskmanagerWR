<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('tipo', 50)->default('tarefa_atribuida');
            $table->string('mensagem');
            $table->unsignedBigInteger('tarefa_id')->nullable();
            $table->boolean('lida')->default(false);
            $table->timestamps();

            $table->index(['usuario_id', 'lida']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacoes');
    }
};
