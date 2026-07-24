<?php

namespace App\Services\SimplesNacional;

use App\Models\Cliente;
use App\Models\DefisDeclaracao;
use Illuminate\Support\Facades\Log;

/**
 * Serviços da DEFIS (Declaração de Informações Socioeconômicas e Fiscais,
 * anual, Simples Nacional) via Integra Contador — consulta e transmissão.
 *
 * Consulta (CONSDECLARACAO142/CONSDECREC144): idServico confirmado na
 * documentação oficial, testado contra a API real.
 *
 * Transmissão (TRANSDECLARACAO141): "metodoGateway" confirmado como
 * 'Declarar' no Catálogo de Serviços oficial (apicenter.estaleiro.serpro.
 * gov.br/documentacao/api-integra-contador/pt/catalogo_de_servicos/, "Em
 * produção" desde 25/09/2023), versaoSistema "1.0" confirmado na doc de
 * entrada. NUNCA testado contra a API real ainda.
 *
 * Suporta só UM estabelecimento por declaração (a matriz, cpfcnpj do
 * cliente) — mesma simplificação do PgdasdService::montarDeclaracao.
 * NÃO suporta (bloqueia com RuntimeException, decisão deliberada, mesmo
 * espírito do "exigibilidade_suspensa" do PGDASD): situação especial
 * (cisão/fusão/incorporação/extinção), "não optante", comerciais
 * exportadoras, doações a campanha eleitoral, e os campos de "informação
 * opcional" por estabelecimento (regra_informacao_opcional da doc) — quem
 * precisar de algum desses cenários deve lançar a DEFIS manualmente pelo
 * e-CAC. A tela força o usuário a confirmar explicitamente que nenhum desses
 * cenários se aplica antes de liberar salvar/transmitir.
 */
class DefisService
{
    const ID_SISTEMA = 'DEFIS';

    public function __construct(
        private IntegraContadorClient $client,
    ) {}

    /**
     * Lista as declarações DEFIS já transmitidas pelo cliente dentro do
     * período decadencial (CONSDECLARACAO142) — resposta vem como lista
     * direta de {anoCalendario, idDefis, tipo, dataHora}, sem envelope
     * "periodos" (diferente do CONSDECLARACAO13 do PGDASD).
     */
    public function consultarDeclaracoes(Cliente $cliente): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: 'CONSDECLARACAO142',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: [],
            idSistema: self::ID_SISTEMA,
        );
    }

    /**
     * Busca o recibo e a declaração completa (PDFs) de uma DEFIS específica
     * já identificada na consulta acima (CONSDECREC144). Campos confirmados
     * na doc: "reciboPdf" e "declaracaoPdf" (sem "nomeArquivo" ao lado,
     * diferente do CONSDECREC15 do PGDASD).
     */
    public function consultarDeclaracaoRecibo(Cliente $cliente, string $idDefis): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: 'CONSDECREC144',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: ['idDefis' => $idDefis],
            idSistema: self::ID_SISTEMA,
        );
    }

    /**
     * Transmite a DEFIS do cliente (TRANSDECLARACAO141) a partir de um
     * rascunho já salvo (DefisDeclaracao + socios). Verifica antes se já não
     * existe uma DEFIS transmitida pra esse ano no histórico real da SERPRO
     * (mesmo espírito de PgdasdService::declaracaoJaExisteNaReceita).
     */
    public function transmitirDefisDoCliente(Cliente $cliente, DefisDeclaracao $declaracao): DefisDeclaracao
    {
        if ($declaracao->socios->isEmpty()) {
            throw new \RuntimeException("Cliente {$cliente->nome}: cadastre ao menos um sócio antes de transmitir a DEFIS.");
        }

        if ($this->declaracaoJaExisteNoHistorico($cliente, $declaracao->ano_calendario)) {
            throw new \RuntimeException("Cliente {$cliente->nome} já tem uma DEFIS transmitida para o ano {$declaracao->ano_calendario} (confirmado via CONSDECLARACAO142).");
        }

        $dados = $this->montarDeclaracao($declaracao, $cliente);

        try {
            $resposta = $this->client->chamarServico(
                metodoGateway: 'Declarar',
                idServico: 'TRANSDECLARACAO141',
                versaoSistema: '1.0',
                cliente: $cliente,
                dados: $dados,
                idSistema: self::ID_SISTEMA,
            );

            $corpo = json_decode($resposta['dados'] ?? '{}', true) ?? [];

            $declaracao->update([
                'status' => 'transmitida',
                'id_defis' => $corpo['idDefis'] ?? null,
                'mensagem_erro' => null,
                'transmitido_em' => now(),
            ]);
        } catch (\Throwable $e) {
            $declaracao->update(['status' => 'erro', 'mensagem_erro' => $e->getMessage()]);

            throw $e;
        }

        return $declaracao->fresh('socios');
    }

    /**
     * Consulta o histórico real (CONSDECLARACAO142) pra ver se já existe
     * DEFIS transmitida nesse ano — gera uma chamada paga extra por
     * transmissão, mas evita mandar uma declaração duplicada.
     */
    private function declaracaoJaExisteNoHistorico(Cliente $cliente, int $anoCalendario): bool
    {
        try {
            $resposta = $this->consultarDeclaracoes($cliente);
        } catch (\Throwable $e) {
            Log::warning('[DEFIS] declaracaoJaExisteNoHistorico: falha ao consultar histórico, prosseguindo sem essa checagem', [
                'cliente_id' => $cliente->id,
                'ano' => $anoCalendario,
                'erro' => $e->getMessage(),
            ]);

            return false;
        }

        $dados = json_decode($resposta['dados'] ?? '[]', true) ?? [];
        $declaracoes = array_is_list($dados) ? $dados : ($dados['declaracoes'] ?? []);

        foreach ($declaracoes as $d) {
            if ((int) ($d['anoCalendario'] ?? 0) === $anoCalendario) {
                return true;
            }
        }

        return false;
    }

    /**
     * Monta o payload "dados" da TRANSDECLARACAO141 a partir do rascunho.
     * "inatividade" só entra se ano_calendario < 2025 (regra da própria
     * DEFIS) — lança RuntimeException se faltar nesse caso.
     */
    private function montarDeclaracao(DefisDeclaracao $d, Cliente $cliente): array
    {
        $dados = [
            'ano' => (int) $d->ano_calendario,
            'empresa' => [
                'ganhoCapital' => (float) $d->ganho_capital,
                'qtdEmpregadoInicial' => (int) $d->qtd_empregado_inicial,
                'qtdEmpregadoFinal' => (int) $d->qtd_empregado_final,
                'receitaExportacaoDireta' => (float) $d->receita_exportacao_direta,
                'ganhoRendaVariavel' => (float) $d->ganho_renda_variavel,
                'socios' => $d->socios->map(fn ($s) => [
                    'cpf' => $s->cpf,
                    'rendimentosIsentos' => (float) $s->rendimentos_isentos,
                    'rendimentosTributaveis' => (float) $s->rendimentos_tributaveis,
                    'participacaoCapitalSocial' => (float) $s->participacao_capital_social,
                    'irRetidoFonte' => (float) $s->ir_retido_fonte,
                ])->values()->all(),
                'estabelecimentos' => [[
                    'cnpjCompleto' => preg_replace('/\D/', '', $cliente->cpfcnpj ?? ''),
                    'estoqueInicial' => (float) $d->estoque_inicial,
                    'estoqueFinal' => (float) $d->estoque_final,
                    'saldoCaixaInicial' => (float) $d->saldo_caixa_inicial,
                    'saldoCaixaFinal' => (float) $d->saldo_caixa_final,
                    'aquisicoesMercadoInterno' => (float) $d->aquisicoes_mercado_interno,
                    'importacoes' => (float) $d->importacoes,
                    'totalEntradasPorTransferencia' => (float) $d->total_entradas_por_transferencia,
                    'totalSaidasPorTransferencia' => (float) $d->total_saidas_por_transferencia,
                    'totalDevolucoesVendas' => (float) $d->total_devolucoes_vendas,
                    'totalEntradas' => (float) $d->total_entradas,
                    'totalDevolucoesCompras' => (float) $d->total_devolucoes_compras,
                    'totalDespesas' => (float) $d->total_despesas,
                ]],
            ],
        ];

        if ($d->lucro_contabil !== null) {
            $dados['empresa']['lucroContabil'] = (float) $d->lucro_contabil;
        }

        if ($d->participacao_cotas_tesouraria !== null) {
            $dados['empresa']['participacaoCotasTesouraria'] = (float) $d->participacao_cotas_tesouraria;
        }

        $estabelecimento = &$dados['empresa']['estabelecimentos'][0];

        if ($d->iss_retidos_fonte !== null) {
            $estabelecimento['issRetidosFonte'] = (float) $d->iss_retidos_fonte;
        }

        if ($d->prestacoes_servico_comunicacao !== null) {
            $estabelecimento['prestacoesServicoComunicacao'] = (float) $d->prestacoes_servico_comunicacao;
        }

        if ($d->prestacoes_servico_transporte !== null) {
            $estabelecimento['prestacoesServicoTransporte'] = (float) $d->prestacoes_servico_transporte;
        }

        if ($d->ano_calendario < 2025) {
            if ($d->inatividade === null) {
                throw new \RuntimeException("Cliente {$cliente->nome}: campo \"inatividade\" é obrigatório para anos anteriores a 2025 — preencha antes de transmitir.");
            }

            $dados['inatividade'] = (int) $d->inatividade;
        }

        return $dados;
    }
}
