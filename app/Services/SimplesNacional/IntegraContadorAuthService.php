<?php

namespace App\Services\SimplesNacional;

use App\Models\IntegraContadorConfiguracao;
use App\Services\Concerns\LidaComCertificadoPfx;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Autenticação na API Integra Contador (SERPRO): mTLS com o certificado e-CNPJ
 * do escritório + Basic Auth (consumer key/secret). Retorna access_token (Bearer)
 * e jwt_token, ambos exigidos nas chamadas de serviço seguintes.
 *
 * https://apicenter.estaleiro.serpro.gov.br/documentacao/api-integra-contador/pt/quick_start/
 */
class IntegraContadorAuthService
{
    use LidaComCertificadoPfx;

    const ENDPOINT_AUTENTICACAO = 'https://autenticacao.sapi.serpro.gov.br/authenticate';

    const CACHE_KEY = 'integra_contador_tokens';

    public function obterTokens(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addSeconds(1800), function () {
            return $this->autenticar();
        });
    }

    public function invalidarTokens(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function autenticar(): array
    {
        $config = IntegraContadorConfiguracao::first();

        if (!$config) {
            throw new \RuntimeException('Configuração da API Integra Contador não encontrada. Cadastre o certificado e as credenciais do escritório antes de processar o Simples Nacional.');
        }

        $certPath = storage_path('app/' . $config->arquivo_certificado);

        [$pemCert, $pemKey, $tempFiles] = $this->extrairPem($certPath, $config->senha_certificado);

        try {
            Log::debug('[IntegraContador] autenticar: solicitando token', ['ambiente' => $config->ambiente]);

            $resposta = Http::withOptions([
                'cert' => $pemCert,
                'ssl_key' => $pemKey,
            ])
                ->withBasicAuth($config->consumer_key, $config->consumer_secret)
                ->withHeaders([
                    'role-type' => 'TERCEIROS',
                ])
                ->asForm()
                ->post(self::ENDPOINT_AUTENTICACAO, [
                    'grant_type' => 'client_credentials',
                ]);

            if ($resposta->failed()) {
                Log::error('[IntegraContador] autenticar: falha na resposta', [
                    'status' => $resposta->status(),
                ]);

                throw new \RuntimeException("Falha ao autenticar na API Integra Contador (HTTP {$resposta->status()}).");
            }

            $dados = $resposta->json();

            if (empty($dados['access_token']) || empty($dados['jwt_token'])) {
                throw new \RuntimeException('Resposta de autenticação da API Integra Contador não trouxe access_token/jwt_token.');
            }

            Log::debug('[IntegraContador] autenticar: token obtido com sucesso');

            return [
                'access_token' => $dados['access_token'],
                'jwt_token' => $dados['jwt_token'],
            ];
        } finally {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }
    }
}
