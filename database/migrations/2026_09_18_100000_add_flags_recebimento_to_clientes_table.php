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
        Schema::table('clientes', function (Blueprint $table) {
            $table->boolean('pode_enviar_documentos')->default(false)->after('acesso_extrato');
            $table->boolean('recebe_arquivos_portal')->default(true)->after('pode_enviar_documentos');
            $table->boolean('recebe_arquivos_email')->default(false)->after('recebe_arquivos_portal');
            $table->boolean('recebe_arquivos_whatsapp')->default(false)->after('recebe_arquivos_email');
            $table->boolean('notificar_email_novo_arquivo')->default(false)->after('recebe_arquivos_whatsapp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn([
                'pode_enviar_documentos',
                'recebe_arquivos_portal',
                'recebe_arquivos_email',
                'recebe_arquivos_whatsapp',
                'notificar_email_novo_arquivo',
            ]);
        });
    }
};
