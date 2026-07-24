<?php

namespace App\Services\SimplesNacional;

use App\Models\Cliente;

/**
 * Serviços do Integra-Parcelamento (SERPRO) para o PARCSN (parcelamento
 * ordinário do Simples Nacional) — permite ver quais parcelamentos o cliente
 * tem, o histórico de pagamento de cada um e quais parcelas ainda estão
 * pendentes de emissão/pagamento (o "gargalo").
 *
 * idServico de cada operação confirmado na documentação oficial
 * (apicenter.estaleiro.serpro.gov.br/documentacao/api-integra-contador/pt/
 * solucoes/integra-parcelamento/parcsn/servicos/), mas NUNCA testado contra a
 * API real (nem trial nem produção) — revalidar assim que houver o primeiro
 * cliente com parcelamento ativo para consultar.
 *
 * Só cobre PARCSN (parcelamento ordinário do Simples Nacional). Os outros 7
 * módulos do Integra-Parcelamento (PARCSN-ESP, PERTSN, RELPSN, PARCMEI e
 * variantes) têm os mesmos 5 serviços, mas com idServico diferente — não
 * implementados aqui até surgir um cliente que precise.
 */
class ParcelamentoService
{
    const ID_SISTEMA = 'PARCSN';

    public function __construct(
        private IntegraContadorClient $client,
    ) {}

    /**
     * Lista o histórico de pedidos de parcelamento do cliente (um por
     * parcelamento já solicitado), com a situação de cada um (ex.: "Em
     * parcelamento", "Encerrado Pedido do Contribuinte").
     */
    public function consultarPedidos(Cliente $cliente): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: 'PEDIDOSPARC163',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: [],
            idSistema: self::ID_SISTEMA,
        );
    }

    /**
     * Detalha um parcelamento específico: consolidação original (valor total,
     * quantidade de parcelas, parcela básica) e o demonstrativo de pagamentos
     * mês a mês já realizados — é comparando esse demonstrativo com as
     * parcelas esperadas que dá para enxergar atraso/gargalo manualmente.
     */
    public function consultarParcelamento(Cliente $cliente, string $numeroParcelamento): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: 'OBTERPARC164',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: ['numeroParcelamento' => (int) $numeroParcelamento],
            idSistema: self::ID_SISTEMA,
        );
    }

    /**
     * Detalhe de pagamento de uma parcela específica já paga (tributo a
     * tributo, banco/agência, data de pagamento).
     */
    public function consultarDetalhePagamento(Cliente $cliente, string $numeroParcelamento, string $anoMesParcela): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: 'DETPAGTOPARC165',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: [
                'numeroParcelamento' => (int) $numeroParcelamento,
                'anoMesParcela' => (int) $anoMesParcela,
            ],
            idSistema: self::ID_SISTEMA,
        );
    }

    /**
     * Lista as parcelas do parcelamento ATIVO do cliente ainda disponíveis
     * para gerar o DAS — na prática, é a fila de parcelas em aberto (o
     * "gargalo" que o usuário quer enxergar): cada item tem o mês/ano
     * (AAAAMM) e o valor devido daquela parcela.
     */
    public function consultarParcelasParaImpressao(Cliente $cliente): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: 'PARCELASPARAGERAR162',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: [],
            idSistema: self::ID_SISTEMA,
        );
    }

    /**
     * Emite o DAS de uma parcela (AAAAMM) do parcelamento ativo — resposta
     * traz o PDF em "docArrecadacaoPdfB64" (nome de campo diferente do
     * "pdf"/"detalhamentoDas" do PGDASD).
     */
    public function emitirDas(Cliente $cliente, string $parcela): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Emitir',
            idServico: 'GERARDAS161',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: ['parcelaParaEmitir' => (int) $parcela],
            idSistema: self::ID_SISTEMA,
        );
    }
}
