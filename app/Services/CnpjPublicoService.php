<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Consulta dados cadastrais públicos de CNPJ via BrasilAPI (agrega dados da
 * Receita Federal) — gratuita, sem autenticação. Usada só para sugerir o CNAE
 * principal; não tem relação com a API paga Integra Contador (SERPRO).
 */
class CnpjPublicoService
{
    const ENDPOINT = 'https://brasilapi.com.br/api/cnpj/v1/';

    /**
     * @return array{codigo: int, descricao: string}|null null se não encontrar ou a consulta falhar
     */
    public function buscarCnae(string $cnpj): ?array
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        try {
            $resposta = Http::timeout(10)->get(self::ENDPOINT . $cnpj);
        } catch (\Throwable $e) {
            Log::warning('[CnpjPublico] buscarCnae: falha de conexão', ['cnpj' => $cnpj, 'erro' => $e->getMessage()]);

            return null;
        }

        if ($resposta->failed()) {
            Log::info('[CnpjPublico] buscarCnae: resposta não OK', ['cnpj' => $cnpj, 'status' => $resposta->status()]);

            return null;
        }

        $dados = $resposta->json();

        if (empty($dados['cnae_fiscal'])) {
            return null;
        }

        return [
            'codigo' => $dados['cnae_fiscal'],
            'descricao' => $dados['cnae_fiscal_descricao'] ?? '',
        ];
    }

    /**
     * Dados cadastrais completos (razão social, endereço, código IBGE do
     * município) usados para pré-preencher o tomador na emissão de NFS-e.
     *
     * @return array{razao_social: ?string, nome_fantasia: ?string, cep: ?string, logradouro: ?string, numero: ?string, complemento: ?string, bairro: ?string, codigo_municipio_ibge: ?string, email: ?string}|null
     */
    public function buscarDadosCadastrais(string $cnpj): ?array
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        try {
            $resposta = Http::timeout(10)->get(self::ENDPOINT . $cnpj);
        } catch (\Throwable $e) {
            Log::warning('[CnpjPublico] buscarDadosCadastrais: falha de conexão', ['cnpj' => $cnpj, 'erro' => $e->getMessage()]);

            return null;
        }

        if ($resposta->failed()) {
            Log::info('[CnpjPublico] buscarDadosCadastrais: resposta não OK', ['cnpj' => $cnpj, 'status' => $resposta->status()]);

            return null;
        }

        $dados = $resposta->json();

        if (empty($dados['cnpj'])) {
            return null;
        }

        return [
            'razao_social' => $dados['razao_social'] ?? null,
            'nome_fantasia' => $dados['nome_fantasia'] ?? null,
            'cep' => $dados['cep'] ?? null,
            'logradouro' => $dados['logradouro'] ?? null,
            'numero' => $dados['numero'] ?? null,
            'complemento' => $dados['complemento'] ?? null,
            'bairro' => $dados['bairro'] ?? null,
            'codigo_municipio_ibge' => isset($dados['codigo_municipio_ibge']) ? (string) $dados['codigo_municipio_ibge'] : null,
            'email' => $dados['email'] ?? null,
        ];
    }
}
