<?php

namespace App\Services\SimplesNacional;

use App\Models\Cliente;

/**
 * SITFIS — Relatório de Situação Fiscal (visão geral de pendências perante
 * RFB/PGFN, mais amplo que só parcelamento/DAS). Fluxo assíncrono em duas
 * etapas: solicitar um protocolo, depois tentar emitir o PDF com esse
 * protocolo (pode não estar pronto na primeira tentativa — tem que esperar
 * o "tempoEspera" indicado e tentar de novo).
 *
 * idServico confirmado na documentação oficial (apicenter.estaleiro.serpro.
 * gov.br/documentacao/api-integra-contador/pt/solucoes/integra-sitfis/sitfis/),
 * mas NUNCA testado contra a API real, e a documentação resumida tem
 * inconsistências entre si sobre o formato exato da resposta assíncrona
 * (menciona tanto status HTTP 202/204/503 com "tempoEspera" em header ETag,
 * quanto um "tempoEspera" dentro do próprio corpo JSON — nosso client só
 * expõe o corpo, não os headers). Implementado de forma defensiva: tenta ler
 * "tempoEspera" do corpo decodificado, com fallback de 4000ms se não achar.
 * REVALIDAR no primeiro uso real.
 *
 * "metodoGateway" das duas chamadas ('Apoiar' e 'Emitir') foi inferido dos
 * slugs da documentação (apoiar_relatorio / emitir_relatorio) — não
 * confirmado contra o Swagger/API Reference oficial. Se a API retornar 404,
 * o verbo certo precisa ser corrigido aqui.
 */
class SitfisService
{
    const ID_SISTEMA = 'SITFIS';

    public function __construct(
        private IntegraContadorClient $client,
    ) {}

    /**
     * Solicita um protocolo do relatório (SOLICITARPROTOCOLO91). Retorna o
     * envelope bruto — quem chama decodifica "dados" pra pegar
     * "protocoloRelatorio" e "tempoEspera".
     */
    public function solicitarProtocolo(Cliente $cliente): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Apoiar',
            idServico: 'SOLICITARPROTOCOLO91',
            versaoSistema: '2.0',
            cliente: $cliente,
            dados: [],
            idSistema: self::ID_SISTEMA,
        );
    }

    /**
     * Tenta emitir o relatório com o protocolo obtido (RELATORIOSITFIS92).
     * Pode não estar pronto ainda — quem chama decide se tenta de novo com
     * base no "pronto" do retorno de extrairResultado().
     */
    public function emitirRelatorio(Cliente $cliente, string $protocolo): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Emitir',
            idServico: 'RELATORIOSITFIS92',
            versaoSistema: '2.0',
            cliente: $cliente,
            dados: ['protocoloRelatorio' => $protocolo],
            idSistema: self::ID_SISTEMA,
        );
    }

    /**
     * Interpreta a resposta de emitirRelatorio(): tenta achar o PDF (pronto)
     * ou o tempo de espera (ainda processando). Formato exato não confirmado
     * — ver ressalva na classe.
     */
    public function extrairResultado(array $resposta): array
    {
        $statusHttp = $resposta['status'] ?? null;
        $dadosBruto = $resposta['dados'] ?? null;

        $dados = is_string($dadosBruto) ? (json_decode($dadosBruto, true) ?? $dadosBruto) : $dadosBruto;

        $pdf = is_array($dados) ? ($dados['pdf'] ?? null) : (is_string($dados) && strlen($dados) > 100 ? $dados : null);

        if ($statusHttp === 200 && $pdf) {
            return ['pronto' => true, 'pdf' => $pdf];
        }

        $tempoEspera = is_array($dados) ? ($dados['tempoEspera'] ?? null) : null;

        return ['pronto' => false, 'tempo_espera_ms' => $tempoEspera ?? 4000];
    }
}
