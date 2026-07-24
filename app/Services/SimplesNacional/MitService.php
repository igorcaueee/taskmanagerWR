<?php

namespace App\Services\SimplesNacional;

use App\Models\Cliente;

/**
 * MIT (Módulo de Inclusão de Tributos) — módulo da DCTFWeb que substitui
 * declarações específicas para tributos administrados pela Receita Federal
 * (IRPJ, CSLL, IRRF, IPI, IOF, PIS/Cofins etc.), obrigatório para PJs sujeitas
 * à DCTFWeb mensal (IN RFB 2005/2021 e 2.237/2024).
 *
 * Cobre só o lado de CONSULTA (apurações já encerradas, seja pelo e-CAC ou
 * por outro sistema) — por decisão deliberada, igual à DEFIS: encerrar uma
 * apuração nova (ENCAPURACAO314) é uma declaração fiscal REAL e irreversível,
 * com um payload enorme (débitos por tributo, eventos especiais, suspensões,
 * responsável legal) ainda não modelado no sistema. Fica para uma etapa
 * separada.
 *
 * Verbos do gateway confirmados no Catálogo de Serviços oficial
 * (apicenter.estaleiro.serpro.gov.br/documentacao/api-integra-contador/pt/
 * catalogo_de_servicos/): CONSAPURACAO316 e LISTAAPURACOES317 = "Consultar"
 * (ENCAPURACAO314 = "Declarar", SITUACAOENC315 = "Apoiar" — não
 * implementados aqui).
 *
 * listarApuracoes (LISTAAPURACOES317) validado contra a API real em produção
 * (2026-07-22): campo "Apuracoes" e os nomes periodoApuracao/situacao/
 * dataEncerramento/eventoEspecial/valorTotalApurado confirmados exatamente
 * como documentado. consultarApuracao (CONSAPURACAO316) — a doc não tinha
 * exemplo de JSON publicado (diferente dos outros módulos), estrutura
 * aninhada (dadosApuracaoMit/Debitos/ListaDebitos) ainda não confirmada
 * contra a API real — revalidar no primeiro "Ver detalhes" de verdade.
 */
class MitService
{
    const ID_SISTEMA = 'MIT';

    public function __construct(
        private IntegraContadorClient $client,
    ) {}

    /**
     * Lista as apurações MIT do cliente por ano (e opcionalmente mês/situação)
     * — LISTAAPURACOES317.
     */
    public function listarApuracoes(Cliente $cliente, int $anoApuracao, ?int $mesApuracao = null, ?int $situacaoApuracao = null): array
    {
        $dados = ['anoApuracao' => $anoApuracao];

        if ($mesApuracao) {
            $dados['mesApuracao'] = $mesApuracao;
        }

        if ($situacaoApuracao) {
            $dados['situacaoApuracao'] = $situacaoApuracao;
        }

        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: 'LISTAAPURACOES317',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: $dados,
            idSistema: self::ID_SISTEMA,
        );
    }

    /**
     * Detalha uma apuração específica (débitos por tributo, suspensões etc.)
     * — CONSAPURACAO316.
     */
    public function consultarApuracao(Cliente $cliente, int $idApuracao): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: 'CONSAPURACAO316',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: ['idApuracao' => $idApuracao],
            idSistema: self::ID_SISTEMA,
        );
    }
}
