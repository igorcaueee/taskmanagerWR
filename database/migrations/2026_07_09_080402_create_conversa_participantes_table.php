<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversa_participantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversa_id')->constrained('conversas')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->unsignedBigInteger('ultima_mensagem_lida_id')->nullable();
            $table->timestamp('lida_em')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->timestamps();

            $table->unique(['conversa_id', 'usuario_id']);
            $table->index(['usuario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversa_participantes');
    }
};
