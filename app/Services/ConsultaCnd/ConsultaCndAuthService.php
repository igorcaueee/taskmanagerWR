<?php

namespace App\Services\ConsultaCnd;

use App\Models\ConsultaCndConfiguracao;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Autenticação na API "Consulta CND" (SERPRO) — OAuth2 client_credentials
 * via Basic Auth simples (consumer key/secret), SEM certificado/mTLS
 * (diferente da autenticação do Integra Contador). Confirmado na
 * documentação oficial (apicenter.estaleiro.serpro.gov.br/documentacao/
 * consulta-cnd/pt/global/quick_start/): POST para
 * https://gateway.apiserpro.serpro.gov.br/token com
 * Authorization: Basic base64(consumerKey:consumerSecret).
 *
 * A doc não informa o tempo de expiração do token — cacheamos por um
 * período curto e conservador (10 min) e reautenticamos em qualquer 401,
 * mesmo padrão de "retry uma vez" do IntegraContadorClient.
 */
class ConsultaCndAuthService
{
    const ENDPOINT_TOKEN = 'https://gateway.apiserpro.serpro.gov.br/token';

    const CACHE_KEY = 'consulta_cnd_token';

    public function obterToken(): string
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function () {
            return $this->autenticar();
        });
    }

    public function invalidarToken(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function autenticar(): string
    {
        $config = ConsultaCndConfiguracao::first();

        if (! $config) {
            throw new \RuntimeException('Configuração da API Consulta CND não encontrada. Cadastre o Consumer Key/Secret antes de consultar certidões.');
        }

        Log::debug('[ConsultaCnd] autenticar: solicitando token', ['ambiente' => $config->ambiente]);

        $resposta = Http::asForm()
            ->withBasicAuth($config->consumer_key, $config->consumer_secret)
            ->post(self::ENDPOINT_TOKEN, ['grant_type' => 'client_credentials']);

        if ($resposta->failed()) {
            Log::error('[ConsultaCnd] autenticar: falha na resposta', ['status' => $resposta->status()]);

            throw new \RuntimeException("Falha ao autenticar na API Consulta CND (HTTP {$resposta->status()}).");
        }

        $accessToken = $resposta->json('access_token');

        if (empty($accessToken)) {
            throw new \RuntimeException('Resposta de autenticação da API Consulta CND não trouxe access_token.');
        }

        Log::debug('[ConsultaCnd] autenticar: token obtido com sucesso');

        return $accessToken;
    }
}
