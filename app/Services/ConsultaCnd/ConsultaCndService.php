<?php

namespace App\Services\ConsultaCnd;

/**
 * Consulta CND (SERPRO) — certidão negativa de débito federal (PGFN/RFB)
 * de pessoa jurídica, pessoa física ou imóvel rural, emitida com validade
 * de 180 dias. Produto contratado à parte do Integra Contador (contrato e
 * credenciais próprios).
 *
 * TipoContribuinte/CodigoIdentificacao confirmados na doc oficial
 * (apicenter.estaleiro.serpro.gov.br/documentacao/consulta-cnd/pt/global/
 * servicos_api_macro/): 1/9001 = PJ, 2/9002 = PF, 3/9003 = imóvel rural.
 *
 * Status de retorno confirmados (apicenter.estaleiro.serpro.gov.br/
 * documentacao/consulta-cnd/pt/tipos_retornados/): 1-4 = concluído (com ou
 * sem certidão emitida), 5/6 = tentar de novo em instantes (sem "Chave"),
 * 7 = em processamento (com "Chave" pra reconsulta), 8-15 = erro de
 * parâmetro/contribuinte não cadastrado, 99 = erro no servidor.
 *
 * NUNCA testado contra a API real.
 */
class ConsultaCndService
{
    const TIPO_PJ = 1;

    const TIPO_PF = 2;

    const TIPO_IMOVEL_RURAL = 3;

    private const CODIGO_IDENTIFICACAO = [
        self::TIPO_PJ => '9001',
        self::TIPO_PF => '9002',
        self::TIPO_IMOVEL_RURAL => '9003',
    ];

    public function __construct(
        private ConsultaCndClient $client,
    ) {}

    /**
     * Consulta/emite a certidão. $chave só deve ser informada numa
     * reconsulta após um retorno anterior com Status=7 ("em processamento").
     */
    public function consultar(int $tipoContribuinte, string $numeroInscricao, bool $gerarPdf = true, bool $carimboTempo = false, ?string $chave = null): array
    {
        if (! isset(self::CODIGO_IDENTIFICACAO[$tipoContribuinte])) {
            throw new \InvalidArgumentException("Tipo de contribuinte inválido: {$tipoContribuinte}");
        }

        $dados = [
            'TipoContribuinte' => $tipoContribuinte,
            'ContribuinteConsulta' => preg_replace('/\D/', '', $numeroInscricao),
            'CodigoIdentificacao' => self::CODIGO_IDENTIFICACAO[$tipoContribuinte],
            'GerarCertidaoPdf' => $gerarPdf,
        ];

        if ($chave) {
            $dados['Chave'] = $chave;
        }

        $resposta = $this->client->consultarCertidao($dados, $carimboTempo);

        return $resposta['corpo'];
    }
}
