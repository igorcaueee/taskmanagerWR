<?php

namespace App\Services\SimplesNacional;

use App\Models\Cliente;

/**
 * Serviços do Integra-Parcelamento (SERPRO) para os 4 programas de
 * parcelamento do MEI: PARCMEI (ordinário), PARCMEI-ESP (especial/transação
 * excepcional), PERTMEI (transação excepcional) e RELPMEI (relançamento).
 * Mesma estrutura de 5 serviços do PARCSN (ver ParcelamentoService), só que
 * parametrizada por "programa" porque os 4 têm os mesmos serviços, diferindo
 * apenas no idServico/idSistema.
 *
 * idServico de cada operação confirmado no Catálogo de Serviços oficial da
 * SERPRO (apicenter.estaleiro.serpro.gov.br/documentacao/api-integra-
 * contador/pt/catalogo_de_servicos/): PARCMEI usa a base 201, PARCMEI-ESP a
 * base 211, PERTMEI a base 221, RELPMEI a base 231 — cada base cobre
 * base+0=GERARDAS (Emitir), base+1=PARCELASPARAGERAR (Consultar),
 * base+2=PEDIDOSPARC (Consultar), base+3=OBTERPARC (Consultar),
 * base+4=DETPAGTOPARC (Consultar). NUNCA testado contra a API real (nem
 * trial nem produção) — revalidar assim que houver o primeiro cliente MEI
 * com parcelamento ativo para consultar.
 */
class ParcelamentoMeiService
{
    private const BASE_POR_PROGRAMA = [
        'PARCMEI' => 201,
        'PARCMEI-ESP' => 211,
        'PERTMEI' => 221,
        'RELPMEI' => 231,
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
            throw new \InvalidArgumentException("Programa de parcelamento MEI inválido: {$programa}");
        }

        return $prefixo.(self::BASE_POR_PROGRAMA[$programa] + $offset);
    }

    /**
     * Lista o histórico de pedidos de parcelamento do cliente nesse programa.
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
     * Detalha um parcelamento específico: consolidação original e
     * demonstrativo de pagamentos mês a mês já realizados.
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
     * Detalhe de pagamento de uma parcela específica já paga.
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
     * Lista as parcelas do parcelamento ativo do cliente ainda pendentes de
     * emissão/pagamento.
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
     * Emite o DAS de uma parcela (AAAAMM) do parcelamento ativo.
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
