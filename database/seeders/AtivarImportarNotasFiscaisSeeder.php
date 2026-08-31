<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Seeder;

/**
 * Ativa a importação de notas fiscais (importar_notas_fiscais) para os
 * clientes cujo CNPJ conste na lista em data/cnpjs_importar_notas_fiscais.txt.
 */
class AtivarImportarNotasFiscaisSeeder extends Seeder
{
    public function run(): void
    {
        $caminho = database_path('seeders/data/cnpjs_importar_notas_fiscais.txt');

        if (!file_exists($caminho)) {
            $this->command?->warn("Arquivo não encontrado: {$caminho}");
            return;
        }

        $cnpjs = collect(file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
            ->map(fn ($linha) => preg_replace('/\D/', '', $linha))
            ->filter()
            ->unique()
            ->values();

        $encontrados = Cliente::query()
            ->whereRaw("REPLACE(REPLACE(REPLACE(cpfcnpj, '.', ''), '-', ''), '/', '') IN (" . implode(',', array_fill(0, $cnpjs->count(), '?')) . ')', $cnpjs->all())
            ->update(['importar_notas_fiscais' => true]);

        $naoEncontrados = $cnpjs->count() - $encontrados;

        $this->command?->info("CNPJs na lista: {$cnpjs->count()} | Clientes atualizados: {$encontrados} | Sem correspondência: {$naoEncontrados}");
    }
}
