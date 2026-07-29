<?php

namespace App\Services\SimplesNacional;

use App\Models\Cliente;

/**
 * Integra-DCTFWeb — DCTFWeb "normal" (fora do MIT): débitos previdenciários
 * e outras retenções gerados automaticamente a partir do eSocial/EFD-Reinf,
 * prazo mensal até dia 15 do mês seguinte. Cobre só consulta/emissão de guia
 * de uma declaração já existente — NÃO cobre TRANSDECLARACAO310 (transmitir),
 * decisão deliberada: esse serviço exige assinar digitalmente o XML da
 * declaração com certificado ICP-Brasil (padrão XMLDSig, confirmado na
 * documentação oficial: "xmlAssinadoBase64" precisa ser o XML de
 * CONSXMLDECLARACAO38 assinado, byte a byte idêntico ao original) — nenhum
 * outro módulo daqui faz assinatura de XML (PGDASD/DEFIS/MIT só mandam
 * JSON), então isso ficaria de fora até termos infraestrutura de certificado
 * A1 configurada. Quem precisar transmitir de verdade ainda faz pelo e-CAC.
 *
 * idServico/campos confirmados na documentação oficial (apicenter.estaleiro.
 * serpro.gov.br/documentacao/api-integra-contador/pt/solucoes/integra-
 * dctfweb/dctfweb/servicos/), NUNCA testado contra a API real.
 */
class DctfWebService
{
    const ID_SISTEMA = 'DCTFWEB';

    /**
     * Categorias confirmadas na doc oficial (serviço "Gerar Documento de
     * Arrecadação"): 40 geral mensal, 41 geral 13º salário, 44 aferição
     * (obra), 45 espetáculo desportivo, 46 reclamatória trabalhista,
     * 50 pessoa física mensal, 51 pessoa física 13º salário.
     */
    const CATEGORIAS = [
        40 => 'GERAL_MENSAL',
        41 => 'GERAL_13o_SALARIO',
        44 => 'AFERICAO',
        45 => 'ESPETACULO_DESPORTIVO',
        46 => 'RECLAMATORIA_TRABALHISTA',
        50 => 'PF_MENSAL',
        51 => 'PF_13o_SALARIO',
    ];

    public function __construct(
        private IntegraContadorClient $client,
    ) {}

    /**
     * Monta o campo "dados" comum às 5 operações — categoria/ano são sempre
     * obrigatórios, mesPA é obrigatório exceto nas categorias de 13º salário
     * (41/51), os demais são condicionais por categoria (diaPA só na 45,
     * cnoAfericao só na 44, numProcReclamatoria só na 46).
     */
    private function montarDados(int $categoria, string $anoPA, ?string $mesPA, ?string $diaPA, ?string $cnoAfericao, ?string $numProcReclamatoria, ?string $numeroReciboEntrega): array
    {
        $dados = ['categoria' => $categoria, 'anoPA' => $anoPA];

        if ($mesPA) {
            $dados['mesPA'] = $mesPA;
        }

        if ($diaPA) {
            $dados['diaPA'] = $diaPA;
        }

        if ($cnoAfericao) {
            $dados['cnoAfericao'] = (int) $cnoAfericao;
        }

        if ($numProcReclamatoria) {
            $dados['numProcReclamatoria'] = $numProcReclamatoria;
        }

        if ($numeroReciboEntrega) {
            $dados['numeroReciboEntrega'] = (int) $numeroReciboEntrega;
        }

        return $dados;
    }

    /**
     * Gera o DARF (guia de arrecadação) de uma declaração já entregue
     * (GERARGUIA31) — resposta traz "PDFByteArrayBase64".
     */
    public function gerarGuia(Cliente $cliente, int $categoria, string $anoPA, ?string $mesPA = null, ?string $diaPA = null, ?string $cnoAfericao = null, ?string $numProcReclamatoria = null, ?string $numeroReciboEntrega = null): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Emitir',
            idServico: 'GERARGUIA31',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: $this->montarDados($categoria, $anoPA, $mesPA, $diaPA, $cnoAfericao, $numProcReclamatoria, $numeroReciboEntrega),
            idSistema: self::ID_SISTEMA,
        );
    }

    /**
     * Gera o DARF de uma declaração ainda EM ANDAMENTO — ou seja, calculada
     * a partir do eSocial/EFD-Reinf mas ainda não entregue (GERARGUIAANDAMENTO313).
     */
    public function gerarGuiaAndamento(Cliente $cliente, int $categoria, string $anoPA, ?string $mesPA = null, ?string $diaPA = null, ?string $cnoAfericao = null, ?string $numProcReclamatoria = null): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Emitir',
            idServico: 'GERARGUIAANDAMENTO313',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: $this->montarDados($categoria, $anoPA, $mesPA, $diaPA, $cnoAfericao, $numProcReclamatoria, null),
            idSistema: self::ID_SISTEMA,
        );
    }

    /**
     * Consulta o recibo de transmissão de uma declaração já entregue
     * (CONSRECIBO32) — resposta traz "PDFByteArrayBase64".
     */
    public function consultarRecibo(Cliente $cliente, int $categoria, string $anoPA, ?string $mesPA = null, ?string $diaPA = null, ?string $cnoAfericao = null, ?string $numProcReclamatoria = null, ?string $numeroReciboEntrega = null): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: 'CONSRECIBO32',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: $this->montarDados($categoria, $anoPA, $mesPA, $diaPA, $cnoAfericao, $numProcReclamatoria, $numeroReciboEntrega),
            idSistema: self::ID_SISTEMA,
        );
    }

    /**
     * Consulta o relatório completo da declaração (CONSDECCOMPLETA33) —
     * resposta traz "PDFByteArrayBase64".
     */
    public function consultarDeclaracaoCompleta(Cliente $cliente, int $categoria, string $anoPA, ?string $mesPA = null, ?string $diaPA = null, ?string $cnoAfericao = null, ?string $numProcReclamatoria = null, ?string $numeroReciboEntrega = null): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: 'CONSDECCOMPLETA33',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: $this->montarDados($categoria, $anoPA, $mesPA, $diaPA, $cnoAfericao, $numProcReclamatoria, $numeroReciboEntrega),
            idSistema: self::ID_SISTEMA,
        );
    }

    /**
     * Consulta o XML da declaração (CONSXMLDECLARACAO38) — resposta traz
     * "XMLStringBase64". Serve pra ler o conteúdo da declaração; NÃO
     * usamos esse XML pra transmitir (ver docblock da classe).
     */
    public function consultarXmlDeclaracao(Cliente $cliente, int $categoria, string $anoPA, ?string $mesPA = null, ?string $diaPA = null, ?string $cnoAfericao = null, ?string $numProcReclamatoria = null, ?string $numeroReciboEntrega = null): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: 'CONSXMLDECLARACAO38',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: $this->montarDados($categoria, $anoPA, $mesPA, $diaPA, $cnoAfericao, $numProcReclamatoria, $numeroReciboEntrega),
            idSistema: self::ID_SISTEMA,
        );
    }
}
