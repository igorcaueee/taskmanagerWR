<?php

namespace Database\Seeders;

use App\Models\NfseServicoNacional;
use Illuminate\Database\Seeder;

/**
 * Popula a Lista Nacional de Serviços (LC 116) a partir do CSV extraído do
 * Anexo I oficial do Sistema Nacional NFS-e (aba MUN.INCID_INFO.SERV.),
 * usada para selecionar o código de tributação nacional (cTribNac) na DPS.
 */
class NfseServicosNacionaisSeeder extends Seeder
{
    public function run(): void
    {
        $caminho = database_path('seeders/data/nfse_lista_servicos_nacional.csv');

        if (!file_exists($caminho)) {
            $this->command?->warn("Arquivo não encontrado: {$caminho}");
            return;
        }

        $handle = fopen($caminho, 'r');
        $cabecalho = fgetcsv($handle);

        while (($linha = fgetcsv($handle)) !== false) {
            $dados = array_combine($cabecalho, $linha);

            NfseServicoNacional::updateOrCreate(
                ['codigo_tributacao_nacional' => $dados['codigo_tributacao_nacional']],
                ['descricao' => $dados['descricao']]
            );
        }

        fclose($handle);
    }
}
