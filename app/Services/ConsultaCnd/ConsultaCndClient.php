<?php

namespace App\Services\ConsultaCnd;

use App\Models\ConsultaCndConfiguracao;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client HTTP da API "Consulta CND" (SERPRO) — payload bem mais simples que
 * o Integra Contador (sem envelope contratante/pedidoDados, é um POST direto
 * em /v1/certidao). Confirmado na doc oficial (apicenter.estaleiro.serpro.
 * gov.br/documentacao/consulta-cnd/pt/global/servicos_api_macro/):
 * base trial "consulta-cnd-trial", produção "consulta-cnd" (mesmo padrão de
 * nome usado pelo Integra Contador).
 *
 * IMPORTANTE: a API usa o próprio HTTP status como parte do protocolo de
 * negócio (200=achou/emitiu certidão, 201=em processamento, 202=tentar de
 * novo, 400/404=parâmetro inválido ou dado não cadastrado, todos com corpo
 * JSON parseável) — por isso NÃO tratamos 4xx/404 como falha de conexão
 * aqui, só deixamos o ConsultaCndService interpretar o campo "Status" do
 * corpo. Só erros de conexão de fato (timeout, DNS etc.) ou 401 (token
 * expirado) são tratados neste nível.
 */
class ConsultaCndClient
{
    const BASE_URL_TRIAL = 'https://gateway.apiserpro.serpro.gov.br/consulta-cnd-trial/v1/certidao';

    const BASE_URL_PRODUCAO = 'https://gateway.apiserpro.serpro.gov.br/consulta-cnd/v1/certidao';

    public function __construct(
        private ConsultaCndAuthService $auth,
    ) {}

    /**
     * @return array{status_http: int, corpo: array}
     */
    public function consultarCertidao(array $dados, bool $carimboTempo = false, bool $tentandoNovamente = false): array
    {
        $config = ConsultaCndConfiguracao::first();

        if (! $config) {
            throw new \RuntimeException('Configuração da API Consulta CND não encontrada.');
        }

        $baseUrl = $config->ambiente === 'producao' ? self::BASE_URL_PRODUCAO : self::BASE_URL_TRIAL;
        $token = $this->auth->obterToken();

        $headers = ['Accept' => 'application/json'];

        if ($carimboTempo) {
            $headers['x-signature'] = '1';
        }

        Log::info('[ConsultaCnd] consultarCertidao: enviando', [
            'tipo_contribuinte' => $dados['TipoContribuinte'] ?? null,
        ]);

        $resposta = Http::withToken($token)
            ->withHeaders($headers)
            ->post($baseUrl, $dados);

        if ($resposta->status() === 401 && ! $tentandoNovamente) {
            Log::info('[ConsultaCnd] consultarCertidao: token expirado, reautenticando');
            $this->auth->invalidarToken();

            return $this->consultarCertidao($dados, $carimboTempo, tentandoNovamente: true);
        }

        $corpo = $resposta->json();

        if ($corpo === null) {
            Log::error('[ConsultaCnd] consultarCertidao: resposta sem JSON válido', [
                'status' => $resposta->status(),
                'corpo' => $resposta->body(),
            ]);

            throw new \RuntimeException("API Consulta CND retornou HTTP {$resposta->status()} sem corpo JSON válido.");
        }

        return ['status_http' => $resposta->status(), 'corpo' => $corpo];
    }
}
