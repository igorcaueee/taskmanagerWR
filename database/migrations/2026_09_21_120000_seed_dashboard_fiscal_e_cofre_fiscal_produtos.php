<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Pré-cadastra os produtos usados para liberar "Dashboard Fiscal" e "Cofre Fiscal"
 * no portal do cliente (ver EnsurePortalDashboardFiscalAccess/EnsurePortalCofreAccess,
 * que checam Cliente::hasProduto() por este nome exato) — evita que o admin precise
 * digitar o nome manualmente na tela de cliente e arrisque um typo que quebra o gate.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['Dashboard Fiscal', 'Cofre Fiscal'] as $nome) {
            DB::table('produtos')->insertOrIgnore([
                'nome' => $nome,
                'descricao' => null,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('produtos')->whereIn('nome', ['Dashboard Fiscal', 'Cofre Fiscal'])->delete();
    }
};
