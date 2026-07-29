<?php

namespace App\Services\SimplesNacional;

use App\Models\Cliente;

/**
 * Integra-CaixaPostal / DTE — consulta se o CNPJ está enquadrado no
 * Domicílio Tributário Eletrônico (DTE) e/ou no DTE-SN (Simples Nacional).
 * Mais crítico que a caixa postal comum: intimações via DTE costumam ter
 * prazo legal contando da ciência (ou do prazo presumido de leitura).
 *
 * idServico confirmado na documentação oficial (apicenter.estaleiro.serpro.
 * gov.br/documentacao/api-integra-contador/pt/solucoes/integra-caixapostal/
 * dte/servicos/obter_indicador_dte/): idSistema "DTE", versaoSistema "1.0",
 * "dados" vazio (não recebe parâmetro nenhum, só o envelope padrão).
 * NUNCA testado contra a API real.
 */
class DteService
{
    const ID_SISTEMA = 'DTE';

    public function __construct(
        private IntegraContadorClient $client,
    ) {}

    /**
     * Consulta o indicador de enquadramento no DTE (CONSULTASITUACAODTE111).
     * indicadorEnquadramento: -2 CNPJ inválido, -1 não participante,
     * 0 participante do DTE, 1 participante do DTE-SN, 2 participante de
     * ambos. statusEnquadramento traz a descrição textual.
     */
    public function consultarSituacao(Cliente $cliente): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: 'CONSULTASITUACAODTE111',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: [],
            idSistema: self::ID_SISTEMA,
        );
    }
}
