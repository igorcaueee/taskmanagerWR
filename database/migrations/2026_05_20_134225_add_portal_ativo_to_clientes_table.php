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
        // Em produção, migration 1 criou 'portal_senha' com nome errado.
        // Aqui corrigimos para 'senha_portal' e garantimos as demais colunas.
        Schema::table('clientes', function (Blueprint $table) {
            if (Schema::hasColumn('clientes', 'portal_senha') && ! Schema::hasColumn('clientes', 'senha_portal')) {
                $table->renameColumn('portal_senha', 'senha_portal');
            } elseif (! Schema::hasColumn('clientes', 'senha_portal')) {
                $table->string('senha_portal')->nullable();
            }

            if (! Schema::hasColumn('clientes', 'portal_ativo')) {
                $table->boolean('portal_ativo')->default(false);
            }

            if (! Schema::hasColumn('clientes', 'portal_ultimo_acesso')) {
                $table->timestamp('portal_ultimo_acesso')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            if (Schema::hasColumn('clientes', 'senha_portal')) {
                $table->renameColumn('senha_portal', 'portal_senha');
            }
            if (Schema::hasColumn('clientes', 'portal_ativo')) {
                $table->dropColumn('portal_ativo');
            }
            if (Schema::hasColumn('clientes', 'portal_ultimo_acesso')) {
                $table->dropColumn('portal_ultimo_acesso');
            }
        });
    }
};
