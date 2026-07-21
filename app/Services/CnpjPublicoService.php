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
}
