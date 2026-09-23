<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Novo status para itens cujo CEST é reconhecido, mas cuja regra em
     * st_regras_cest está marcada como "revogada" (ex.: Autopeças no RS,
     * Decreto nº 57.848/2024) -- resposta definitiva de "não é caso de ST",
     * diferente de qualquer pendente_* (que pede validação manual).
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE st_calculos MODIFY status ENUM("
            . "'calculado', 'pendente_cest', 'pendente_aliquota', 'pendente_reducao_base', "
            . "'pendente_fem', 'uf_nao_suportada', 'st_ja_destacada_no_xml', 'nao_sujeito_st'"
            . ')');
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE st_calculos MODIFY status ENUM("
            . "'calculado', 'pendente_cest', 'pendente_aliquota', 'pendente_reducao_base', "
            . "'pendente_fem', 'uf_nao_suportada', 'st_ja_destacada_no_xml'"
            . ')');
    }
};
