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
     * Cadastro consolidado do CNPJ para pré-preencher o formulário de cliente
     * (nome, atividade, endereço, situação cadastral e regime sugerido).
     *
     * @return array<string, mixed>|null
     */
    public function buscarCadastro(string $cnpj): ?array
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        try {
            $resposta = Http::timeout(10)->get(self::ENDPOINT . $cnpj);
        } catch (\Throwable $e) {
            Log::warning('[CnpjPublico] buscarCadastro: falha de conexão', ['cnpj' => $cnpj, 'erro' => $e->getMessage()]);

            return null;
        }

        if ($resposta->failed()) {
            return null;
        }

        $dados = $resposta->json();

        if (empty($dados['cnpj'])) {
            return null;
        }

        $secundarios = collect($dados['cnaes_secundarios'] ?? [])
            ->pluck('codigo')
            ->filter()
            ->map(fn ($c) => (string) $c)
            ->values()
            ->all();

        $regimeSugerido = match (true) {
            (bool) ($dados['opcao_pelo_mei'] ?? false) => 'MEI',
            (bool) ($dados['opcao_pelo_simples'] ?? false) => 'Simples Nacional',
            default => null,
        };

        return [
            'razao_social' => $dados['razao_social'] ?? null,
            'nome_fantasia' => $dados['nome_fantasia'] ?? null,
            'situacao' => $dados['descricao_situacao_cadastral'] ?? null,
            'data_abertura' => $dados['data_inicio_atividade'] ?? null,
            'cidade' => $dados['municipio'] ?? null,
            'estado' => $dados['uf'] ?? null,
            'cnae_principal' => ! empty($dados['cnae_fiscal']) ? (string) $dados['cnae_fiscal'] : null,
            'cnae_descricao' => $dados['cnae_fiscal_descricao'] ?? null,
            'cnae_secundarios' => $secundarios,
            'regime_sugerido' => $regimeSugerido,
        ];
    }

    /**
     * CNAE principal (código + descrição) e a lista de códigos dos CNAEs secundários.
     *
     * @return array{principal: ?string, principal_descricao: ?string, secundarios: array<int, string>}|null
     */
    public function buscarCnaes(string $cnpj): ?array
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        try {
            $resposta = Http::timeout(10)->get(self::ENDPOINT . $cnpj);
        } catch (\Throwable $e) {
            Log::warning('[CnpjPublico] buscarCnaes: falha de conexão', ['cnpj' => $cnpj, 'erro' => $e->getMessage()]);

            return null;
        }

        if ($resposta->failed()) {
            Log::info('[CnpjPublico] buscarCnaes: resposta não OK', ['cnpj' => $cnpj, 'status' => $resposta->status()]);

            return null;
        }

        $dados = $resposta->json();

        if (empty($dados['cnae_fiscal'])) {
            return null;
        }

        $secundarios = collect($dados['cnaes_secundarios'] ?? [])
            ->pluck('codigo')
            ->filter()
            ->map(fn ($c) => (string) $c)
            ->values()
            ->all();

        return [
            'principal' => (string) $dados['cnae_fiscal'],
            'principal_descricao' => $dados['cnae_fiscal_descricao'] ?? null,
            'secundarios' => $secundarios,
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
