<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversa_participantes', function (Blueprint $table) {
            $table->foreign('ultima_mensagem_lida_id')->references('id')->on('mensagens')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversa_participantes', function (Blueprint $table) {
            $table->dropForeign(['ultima_mensagem_lida_id']);
        });
    }
};
