<?php

namespace App\Services\SimplesNacional;

use App\Models\Cliente;

/**
 * Caixa Postal do contribuinte (SERPRO) — mensagens da Receita Federal
 * (e-CAC), via Integra Contador. Substitui a checagem manual da caixa postal
 * no e-CAC para cada cliente.
 *
 * idServico confirmado na documentação oficial (apicenter.estaleiro.serpro.
 * gov.br/documentacao/api-integra-contador/pt/solucoes/integra-caixapostal/
 * caixapostal/) e validado contra a API real em produção (2026-07-22):
 * `statusLeitura=0` confirmado como "todas as mensagens" (retorna lidas e
 * não lidas juntas, distinguíveis por "dataLeitura" vazio/preenchido).
 */
class CaixaPostalService
{
    const ID_SISTEMA = 'CAIXAPOSTAL';

    public function __construct(
        private IntegraContadorClient $client,
    ) {}

    /**
     * Lista mensagens do contribuinte, paginada (MSGCONTRIBUINTE61).
     * Primeira página: indicadorPagina=0, sem ponteiroPagina. Páginas
     * seguintes: indicadorPagina=1 + ponteiroPagina = "ponteiroProximaPagina"
     * da resposta anterior.
     */
    public function obterListaMensagens(Cliente $cliente, ?string $ponteiroPagina = null, int $statusLeitura = 0): array
    {
        $dados = [
            'statusLeitura' => $statusLeitura,
            'indicadorPagina' => $ponteiroPagina ? 1 : 0,
        ];

        if ($ponteiroPagina) {
            $dados['ponteiroPagina'] = $ponteiroPagina;
        }

        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: 'MSGCONTRIBUINTE61',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: $dados,
            idSistema: self::ID_SISTEMA,
        );
    }

    /**
     * Detalhes completos de uma mensagem (MSGDETALHAMENTO62) — traz o corpo
     * em HTML ("corpoModelo").
     */
    public function obterDetalhesMensagem(Cliente $cliente, string $isn): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: 'MSGDETALHAMENTO62',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: ['isn' => $isn],
            idSistema: self::ID_SISTEMA,
        );
    }

    /**
     * Indicador rápido (0=nenhuma, 1=uma, 2=mais de uma) de mensagens novas
     * (INNOVAMSG63) — barato, útil pra checar vários clientes em lote antes
     * de decidir quais vale a pena abrir a lista completa.
     */
    public function obterIndicadorNovasMensagens(Cliente $cliente): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: 'INNOVAMSG63',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: [],
            idSistema: self::ID_SISTEMA,
        );
    }
}
