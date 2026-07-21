<?php

namespace App\Services\SimplesNacional;

use App\Models\Cliente;
use App\Models\IntegraContadorConfiguracao;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client HTTP genérico para a API Integra Contador (SERPRO): monta o envelope
 * padrão contratante/autorPedidoDados/contribuinte/pedidoDados e trata erros
 * comuns (token expirado, CNPJ sem procuração, bloqueios).
 *
 * A base da URL muda entre ambientes: trial usa "integra-contador-trial",
 * produção usa "integra-contador" (confirmado na documentação de cenários
 * trial do PGDASD). O nome exato de cada "método" de gateway (ex: /Consultar,
 * /Declarar, /Emitir) e a estrutura fina de "dados" por serviço (idServico)
 * ainda devem ser revalidados um a um contra a doc completa antes de uso em
 * produção — ver ressalva no plano de implementação.
 */
class IntegraContadorClient
{
    const BASE_URL_TRIAL = 'https://gateway.apiserpro.serpro.gov.br/integra-contador-trial/v1/';

    const BASE_URL_PRODUCAO = 'https://gateway.apiserpro.serpro.gov.br/integra-contador/v1/';

    const ID_SISTEMA = 'PGDASD';

    public function __construct(
        private IntegraContadorAuthService $auth,
    ) {}

    /**
     * @param  string  $metodoGateway  ex.: 'Consultar', 'Declarar', 'Emitir'
     * @param  array  $dados  payload específico do serviço (idServico)
     */
    public function chamarServico(string $metodoGateway, string $idServico, string $versaoSistema, Cliente $cliente, array $dados, bool $tentandoNovamente = false): array
    {
        $config = IntegraContadorConfiguracao::first();

        if (!$config) {
            throw new \RuntimeException('Configuração da API Integra Contador não encontrada.');
        }

        $baseUrl = $config->ambiente === 'producao' ? self::BASE_URL_PRODUCAO : self::BASE_URL_TRIAL;

        $tokens = $this->auth->obterTokens();

        $cnpjEscritorio = preg_replace('/\D/', '', $config->cnpj_contratante);
        $cnpjCliente = preg_replace('/\D/', '', $cliente->cpfcnpj ?? '');

        $payload = [
            'contratante' => ['numero' => $cnpjEscritorio, 'tipo' => 2],
            'autorPedidoDados' => ['numero' => $cnpjEscritorio, 'tipo' => 2],
            'contribuinte' => ['numero' => $cnpjCliente, 'tipo' => 2],
            'pedidoDados' => [
                'idSistema' => self::ID_SISTEMA,
                'idServico' => $idServico,
                'versaoSistema' => $versaoSistema,
                'dados' => json_encode($dados, JSON_UNESCAPED_UNICODE),
            ],
        ];

        Log::info('[IntegraContador] chamarServico: enviando', [
            'cliente_id' => $cliente->id,
            'idServico' => $idServico,
            'metodo' => $metodoGateway,
        ]);

        $resposta = Http::withToken($tokens['access_token'])
            ->withHeaders(['jwt_token' => $tokens['jwt_token']])
            ->acceptJson()
            ->post($baseUrl . $metodoGateway, $payload);

        if ($resposta->status() === 401 && !$tentandoNovamente) {
            Log::info('[IntegraContador] chamarServico: token expirado, reautenticando');
            $this->auth->invalidarTokens();

            return $this->chamarServico($metodoGateway, $idServico, $versaoSistema, $cliente, $dados, tentandoNovamente: true);
        }

        if ($resposta->failed()) {
            $corpo = $resposta->json() ?? [];

            Log::error('[IntegraContador] chamarServico: falha na resposta', [
                'cliente_id' => $cliente->id,
                'idServico' => $idServico,
                'status' => $resposta->status(),
                'corpo' => $corpo ?: $resposta->body(),
            ]);

            throw new \RuntimeException($this->mensagemErro($resposta->status(), $corpo, $idServico, $cliente));
        }

        return $resposta->json();
    }

    /**
     * Monta uma mensagem de erro descritiva a partir do que a API realmente
     * retornou — nunca assume o motivo (ex.: "sem procuração") sem o texto da
     * API confirmar, para não mascarar a causa real por trás de um HTTP genérico.
     */
    private function mensagemErro(int $status, array $corpo, string $idServico, Cliente $cliente): string
    {
        $mensagemApi = $corpo['mensagens'][0]['texto'] ?? $corpo['mensagem'] ?? $corpo['message'] ?? null;

        if ($mensagemApi && str_contains(strtolower($mensagemApi), 'procuração')) {
            return "CNPJ {$cliente->cpfcnpj} sem procuração eletrônica ativa para o escritório junto à Receita Federal: {$mensagemApi}";
        }

        if ($mensagemApi && str_contains(strtolower($mensagemApi), 'bloque')) {
            return "CNPJ {$cliente->cpfcnpj} bloqueado para este serviço: {$mensagemApi}";
        }

        $sufixo = $mensagemApi ? " — {$mensagemApi}" : ' (a API não retornou uma mensagem de texto; veja o corpo completo no log).';

        return "API Integra Contador retornou HTTP {$status} para o serviço {$idServico} (cliente {$cliente->id}){$sufixo}";
    }
}
