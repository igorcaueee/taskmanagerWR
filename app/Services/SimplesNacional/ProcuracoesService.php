<?php

namespace App\Services\SimplesNacional;

use App\Models\Cliente;
use App\Models\IntegraContadorConfiguracao;

/**
 * Integra-Procurações — consulta se o cliente tem procuração eletrônica
 * ativa em nome do escritório (e para quais sistemas/serviços), sem precisar
 * abrir o e-CAC manualmente. Confirmado e validado contra a API real em
 * produção (2026-07-22): retorna as procurações ativas, cada uma com
 * "sistemas" (pode vir como lista de nomes ou a string literal "TODOS", caso
 * a procuração cubra todos os sistemas) e "dtexpiracao".
 *
 * "outorgante" = quem concede a procuração (o cliente); "outorgado" = quem
 * recebe (o escritório, sempre CNPJ = tipoOutorgado 2) — não confundir com a
 * direção contratante/contribuinte do envelope padrão da API.
 *
 * "metodoGateway" é 'Consultar' — confirmado no Catálogo de Serviços oficial
 * (apicenter.estaleiro.serpro.gov.br/documentacao/api-integra-contador/pt/
 * catalogo_de_servicos/, sequencial 045, tipo "Consultar"). O slug da doc
 * ("obter_procuracao") era só o nome de exibição, não o verbo real; 'Obter'
 * dava 404 em produção.
 */
class ProcuracoesService
{
    const ID_SISTEMA = 'PROCURACOES';

    public function __construct(
        private IntegraContadorClient $client,
    ) {}

    public function obterProcuracao(Cliente $cliente): array
    {
        $config = IntegraContadorConfiguracao::first();

        if (!$config) {
            throw new \RuntimeException('Configuração da API Integra Contador não encontrada.');
        }

        $cnpjEscritorio = preg_replace('/\D/', '', $config->cnpj_contratante);
        $cnpjCliente = preg_replace('/\D/', '', $cliente->cpfcnpj ?? '');

        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: 'OBTERPROCURACAO41',
            versaoSistema: '1',
            cliente: $cliente,
            dados: [
                'outorgante' => $cnpjCliente,
                'tipoOutorgante' => '2',
                'outorgado' => $cnpjEscritorio,
                'tipoOutorgado' => '2',
            ],
            idSistema: self::ID_SISTEMA,
        );
    }
}
