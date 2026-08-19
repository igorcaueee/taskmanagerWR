<?php

namespace App\Services\SimplesNacional;

use App\Models\Cliente;

/**
 * Serviços do Integra-Parcelamento (SERPRO) para os 4 programas de
 * parcelamento do Simples Nacional (não-MEI): PARCSN (ordinário), PARCSN-ESP
 * (especial/transação excepcional), PERTSN (transação excepcional) e RELPSN
 * (relançamento). Mesma estrutura de 5 serviços cada, parametrizada por
 * "programa" — mesmo padrão do MEI, ver ParcelamentoMeiService.
 *
 * idServico de cada operação confirmado no Catálogo de Serviços oficial da
 * SERPRO (apicenter.estaleiro.serpro.gov.br/documentacao/api-integra-
 * contador/pt/catalogo_de_servicos/): PARCSN usa a base 161, PARCSN-ESP a
 * base 171, PERTSN a base 181, RELPSN a base 191 — cada base cobre
 * base+0=GERARDAS (Emitir), base+1=PARCELASPARAGERAR (Consultar),
 * base+2=PEDIDOSPARC (Consultar), base+3=OBTERPARC (Consultar),
 * base+4=DETPAGTOPARC (Consultar). PARCSN-ESP/PERTSN/RELPSN NUNCA testados
 * contra a API real (nem trial nem produção) — revalidar assim que houver o
 * primeiro cliente com parcelamento ativo nesses programas para consultar.
 */
class ParcelamentoService
{
    private const BASE_POR_PROGRAMA = [
        'PARCSN' => 161,
        'PARCSN-ESP' => 171,
        'PERTSN' => 181,
        'RELPSN' => 191,
    ];

    public function __construct(
        private IntegraContadorClient $client,
    ) {}

    public static function programasValidos(): array
    {
        return array_keys(self::BASE_POR_PROGRAMA);
    }

    private function idServico(string $programa, string $prefixo, int $offset): string
    {
        if (! isset(self::BASE_POR_PROGRAMA[$programa])) {
            throw new \InvalidArgumentException("Programa de parcelamento inválido: {$programa}");
        }

        return $prefixo.(self::BASE_POR_PROGRAMA[$programa] + $offset);
    }

    /**
     * Lista o histórico de pedidos de parcelamento do cliente nesse programa
     * (um por parcelamento já solicitado), com a situação de cada um (ex.:
     * "Em parcelamento", "Encerrado Pedido do Contribuinte").
     */
    public function consultarPedidos(Cliente $cliente, string $programa): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: $this->idServico($programa, 'PEDIDOSPARC', 2),
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: [],
            idSistema: $programa,
        );
    }

    /**
     * Detalha um parcelamento específico: consolidação original (valor total,
     * quantidade de parcelas, parcela básica) e o demonstrativo de pagamentos
     * mês a mês já realizados.
     */
    public function consultarParcelamento(Cliente $cliente, string $programa, string $numeroParcelamento): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: $this->idServico($programa, 'OBTERPARC', 3),
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: ['numeroParcelamento' => (int) $numeroParcelamento],
            idSistema: $programa,
        );
    }

    /**
     * Detalhe de pagamento de uma parcela específica já paga (tributo a
     * tributo, banco/agência, data de pagamento).
     */
    public function consultarDetalhePagamento(Cliente $cliente, string $programa, string $numeroParcelamento, string $anoMesParcela): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: $this->idServico($programa, 'DETPAGTOPARC', 4),
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: [
                'numeroParcelamento' => (int) $numeroParcelamento,
                'anoMesParcela' => (int) $anoMesParcela,
            ],
            idSistema: $programa,
        );
    }

    /**
     * Lista as parcelas do parcelamento ATIVO do cliente nesse programa ainda
     * disponíveis para gerar o DAS — na prática, é a fila de parcelas em
     * aberto (o "gargalo" que o usuário quer enxergar).
     */
    public function consultarParcelasParaImpressao(Cliente $cliente, string $programa): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: $this->idServico($programa, 'PARCELASPARAGERAR', 1),
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: [],
            idSistema: $programa,
        );
    }

    /**
     * Emite o DAS de uma parcela (AAAAMM) do parcelamento ativo nesse
     * programa — resposta traz o PDF em "docArrecadacaoPdfB64" (nome de
     * campo diferente do "pdf"/"detalhamentoDas" do PGDASD).
     */
    public function emitirDas(Cliente $cliente, string $programa, string $parcela): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Emitir',
            idServico: $this->idServico($programa, 'GERARDAS', 0),
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: ['parcelaParaEmitir' => (int) $parcela],
            idSistema: $programa,
        );
    }
}
