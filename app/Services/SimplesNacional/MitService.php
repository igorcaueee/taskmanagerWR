<?php

namespace App\Services\SimplesNacional;

use App\Models\Cliente;
use App\Models\MitApuracaoRascunho;

/**
 * MIT (Módulo de Inclusão de Tributos) — módulo da DCTFWeb que substitui
 * declarações específicas para tributos administrados pela Receita Federal
 * (IRPJ, CSLL, IRRF, IPI, IOF, PIS/Cofins etc.), obrigatório para PJs sujeitas
 * à DCTFWeb mensal (IN RFB 2005/2021 e 2.237/2024).
 *
 * Cobre consulta (apurações já encerradas), o encerramento de uma apuração
 * SEM MOVIMENTO e, agora, COM MOVIMENTO (ambos ENCAPURACAO314). Suspensões,
 * eventos especiais e débitos postergados de período anterior continuam de
 * fora por decisão deliberada — não existe tabela oficial publicada de
 * códigos MotivoSuspensao, e são cenários raros (bloqueados na tela, exigem
 * lançamento manual pelo e-CAC). VariacoesMonetarias=1 (Caixa) é o default
 * inferido (é o padrão do próprio sistema da RFB, seção 3.3 do manual);
 * =2 (Competência) só é aceito quando o usuário confirma explicitamente.
 *
 * Verbos do gateway confirmados no Catálogo de Serviços oficial
 * (apicenter.estaleiro.serpro.gov.br/documentacao/api-integra-contador/pt/
 * catalogo_de_servicos/): CONSAPURACAO316 e LISTAAPURACOES317 = "Consultar",
 * ENCAPURACAO314 = "Declarar" (SITUACAOENC315 = "Apoiar" — não implementado
 * aqui, só faz sentido junto do fluxo "com movimento").
 *
 * listarApuracoes (LISTAAPURACOES317) validado contra a API real em produção
 * (2026-07-22): campo "Apuracoes" e os nomes periodoApuracao/situacao/
 * dataEncerramento/eventoEspecial/valorTotalApurado confirmados exatamente
 * como documentado. consultarApuracao (CONSAPURACAO316) — a doc não tinha
 * exemplo de JSON publicado (diferente dos outros módulos), estrutura
 * aninhada (dadosApuracaoMit/Debitos/ListaDebitos) ainda não confirmada
 * contra a API real — revalidar no primeiro "Ver detalhes" de verdade.
 *
 * encerrarApuracaoSemMovimento (ENCAPURACAO314, SemMovimento=true) — campos e
 * códigos de QualificacaoPj confirmados no "Manual do MIT – janeiro/2025"
 * (RFB, seção 3.1, tabela extraída visualmente do PDF oficial): 1 PJ em
 * geral, 2 Agência de Fomento/Banco/PJ do §1º art.22 Lei 8.212/1991,
 * 3 Cooperativa de Crédito, 4 Sociedade Corretora de Seguros, 5 Sociedade
 * Seguradora/Capitalização ou EAPC com fins lucrativos, 6 EFPC ou EAPC sem
 * fins lucrativos, 7 Sociedade Cooperativa, 8 Sociedade Cooperativa de
 * Produção Agropecuária/Consumo, 9 Autarquia ou Fundação Pública,
 * 10 Empresa Pública/Sociedade de Economia Mista (inc.III art.34 Lei
 * 10.833/2003), 11 Estado/DF/Município/Órgão Público Adm. Direta,
 * 12 Mais de uma qualificação durante o mês. NUNCA testado contra a API
 * real — a estrutura da resposta de sucesso não tem exemplo publicado.
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

    /**
     * Encerra uma apuração MIT SEM MOVIMENTO (ENCAPURACAO314,
     * SemMovimento=true) — não exige TributacaoLucro, RegimePisCofins,
     * Debitos, suspensões nem eventos especiais. TransmissaoImediata é
     * sempre true aqui (obrigatório quando SemMovimento=true).
     */
    public function encerrarApuracaoSemMovimento(Cliente $cliente, int $anoApuracao, int $mesApuracao, int $qualificacaoPj, string $cpfResponsavel): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Declarar',
            idServico: 'ENCAPURACAO314',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: [
                'AnoApuracao' => $anoApuracao,
                'MesApuracao' => $mesApuracao,
                'SemMovimento' => true,
                'QualificacaoPj' => $qualificacaoPj,
                'ResponsavelApuracao' => [
                    'CpfResponsavel' => preg_replace('/\D/', '', $cpfResponsavel),
                ],
                'TransmissaoImediata' => true,
            ],
            idSistema: self::ID_SISTEMA,
        );
    }

    /**
     * Encerra uma apuração MIT COM MOVIMENTO (ENCAPURACAO314,
     * SemMovimento=false) — monta o bloco "Debitos" agrupado por tributo a
     * partir dos débitos salvos no rascunho local.
     *
     * TributacaoLucro/RegimePisCofins só são enviados quando QualificacaoPj
     * != 11 (Estado/DF/Município/Órgão Público), regra confirmada no manual
     * do MIT (seção 3.1: "cada qualificação... está relacionada à forma de
     * tributação do lucro ou ao regime de apuração do PIS/PASEP e COFINS").
     * BalancoLucroReal só é enviado quando TributacaoLucro=1 (Real Anual),
     * único caso em que a seção 4.3 do manual prevê essa informação.
     *
     * Suspensões, eventos especiais e débitos postergados de período
     * anterior NÃO são suportados aqui (bloqueados na tela) — a tabela de
     * códigos MotivoSuspensao não foi encontrada em nenhuma doc oficial.
     *
     * PONTO DE RISCO NÃO CONFIRMADO CONTRA A API REAL: o formato exato de
     * "PaDebito" para códigos trimestrais/anuais (aqui inferido como AAAAMM
     * usando o último mês do trimestre/ano) — só o formato mensal (AAAAMM
     * direto) é garantido pela estrutura do payload. Revalidar no primeiro
     * envio real com débito TR/AN.
     */
    public function encerrarApuracaoComMovimento(Cliente $cliente, MitApuracaoRascunho $rascunho): array
    {
        $qualificacaoExigeTributacao = (int) $rascunho->qualificacao_pj !== 11;

        $dados = [
            'AnoApuracao' => $rascunho->ano_apuracao,
            'MesApuracao' => $rascunho->mes_apuracao,
            'SemMovimento' => false,
            'QualificacaoPj' => (int) $rascunho->qualificacao_pj,
            'VariacoesMonetarias' => (int) $rascunho->variacoes_monetarias,
            'ResponsavelApuracao' => [
                'CpfResponsavel' => preg_replace('/\D/', '', $rascunho->cpf_responsavel),
            ],
            'TransmissaoImediata' => true,
        ];

        if ($qualificacaoExigeTributacao && $rascunho->tributacao_lucro) {
            $dados['TributacaoLucro'] = (int) $rascunho->tributacao_lucro;

            if ((int) $rascunho->tributacao_lucro === 1) {
                $dados['BalancoLucroReal'] = [
                    'Irpj' => (bool) $rascunho->balanco_irpj,
                    'Csll' => (bool) $rascunho->balanco_csll,
                ];
            }
        }

        if ($qualificacaoExigeTributacao && $rascunho->regime_pis_cofins) {
            $dados['RegimePisCofins'] = (int) $rascunho->regime_pis_cofins;
        }

        $dados['Debitos'] = $this->montarDebitosPorGrupo($rascunho);

        return $this->client->chamarServico(
            metodoGateway: 'Declarar',
            idServico: 'ENCAPURACAO314',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: $dados,
            idSistema: self::ID_SISTEMA,
        );
    }

    /**
     * Agrupa os débitos do rascunho por tributo (Irpj, Csll etc.) no formato
     * exigido pelo payload — {Grupo: {ListaDebitos: [...]}}.
     */
    private function montarDebitosPorGrupo(MitApuracaoRascunho $rascunho): array
    {
        $grupos = [];

        foreach ($rascunho->debitos as $debito) {
            $item = [
                'CodigoDebito' => $debito->codigo_receita,
                'PaDebito' => $this->formatarPaDebito($debito),
                'ValorDebito' => (float) $debito->valor,
            ];

            if ($debito->cnpj_estabelecimento) {
                $item['CnpjEstabelecimento'] = $debito->cnpj_estabelecimento;
            }

            if ($debito->cnpj_incorporacao) {
                $item['CnpjIncorporacao'] = $debito->cnpj_incorporacao;
            }

            if ($debito->cnpj_scp) {
                $item['CnpjScp'] = $debito->cnpj_scp;
            }

            if ($debito->codigo_municipio_ouro) {
                $item['CodigoMunicipioOuro'] = $debito->codigo_municipio_ouro;
            }

            $grupos[$debito->grupo]['ListaDebitos'][] = $item;
        }

        return $grupos;
    }

    /**
     * Monta o período de referência do débito no formato AAAAMM.
     * Mensal usa o próprio mês; trimestral usa o último mês do trimestre
     * (03/06/09/12); anual usa dezembro (12) — as duas últimas são inferência,
     * ver aviso no docblock de encerrarApuracaoComMovimento.
     */
    private function formatarPaDebito($debito): string
    {
        $ano = $debito->ano_referencia;

        if ($debito->periodicidade === 'ME') {
            return sprintf('%04d%02d', $ano, $debito->mes_referencia);
        }

        if ($debito->periodicidade === 'TR') {
            return sprintf('%04d%02d', $ano, $debito->trimestre_referencia * 3);
        }

        return sprintf('%04d12', $ano);
    }
}
