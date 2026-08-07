<?php

namespace App\Services\SimplesNacional;

use App\Models\Cliente;
use App\Models\SimplesDasProcessamento;
use App\Models\SimplesReceitaAtividade;
use App\Models\SimplesReceitaMensal;
use App\Support\PgdasdAtividades;
use Illuminate\Support\Facades\Log;

/**
 * Serviços de alto nível do PGDASD (apuração/DAS do Simples Nacional) via
 * Integra Contador.
 *
 * Validado contra a API real de produção (2026-07-20):
 * - CONSDECLARACAO13 (consultar por ano) — confirmado, retorna o histórico de
 *   declarações/status do DAS por período (sem valores de receita/RBT12).
 * - CONSDECREC15 (consultar recibo de uma declaração) — confirmado, retorna
 *   dois PDFs em base64 (recibo + declaração completa). O RBT12/receitas
 *   anteriores/Fator r aparecem no TEXTO do PDF "declaração", não como campo
 *   JSON — a Receita Federal calcula e guarda esses valores internamente a
 *   partir do histórico de transmissões, não é algo que a gente pré-calcula.
 *   Por decisão do usuário, não fazemos parsing desse PDF por código; quem
 *   precisar do valor abre o PDF manualmente.
 * - CONSEXTRATO16 (extrato do DAS) — confirmado em produção (2026-07-21):
 *   {numeroDas, extrato: {nomeArquivo, pdf}} — um único PDF com nomeArquivo,
 *   sem dados estruturados adicionais (diferente do GERARDAS12).
 * - GERARDAS12 (emitir DAS) — confirmado em produção (2026-07-21): a resposta
 *   vem como LISTA, campo correto é "detalhamentoDas" (não "detalhamento"):
 *   [{pdf, cnpjCompleto, detalhamentoDas: {periodoApuracao, numeroDocumento,
 *   dataVencimento (AAAAMMDD), dataLimiteAcolhimento, valores{principal,multa,
 *   juros,total}, composicao[]{codigo,denominacao,valores}}}]. Suporta
 *   "dataConsolidacao" opcional (AAAAMMDD, só datas futuras).
 *
 * AINDA NÃO VALIDADO — requer cautela redobrada antes de usar:
 * - TRANSDECLARACAO11 (transmitir) nunca foi chamado de verdade. Diferente
 *   das consultas/emissões acima, cria uma declaração fiscal REAL perante a
 *   Receita Federal — não há como testar sem risco de gerar uma declaração
 *   real indevida.
 * - A estrutura do payload "declaracao" implementada em montarDeclaracao()
 *   segue um modelo de terceiro (pacote Dart não-oficial
 *   serpro_integra_contador_api), NÃO a documentação oficial paga — é a
 *   melhor referência encontrada publicamente, mas precisa ser confirmada
 *   com o suporte SERPRO/doc completa antes do primeiro uso real.
 * - `idAtividade` é um código do catálogo "Dados de domínio" do PGDASD,
 *   DIFERENTE do CNAE — CONFIRMADO na doc oficial (apicenter.estaleiro.serpro.
 *   gov.br/documentacao/api-integra-contador/pt/solucoes/integra-sn/pgdasd/
 *   dados_de_dominio/, tabela "Atividades", 43 valores, ver App\Support\
 *   PgdasdAtividades). Não é código de classificação livre — é a atividade
 *   que o contribuinte já tem cadastrada no Simples Nacional (pode ter mais
 *   de uma ao mesmo tempo, daí SimplesReceitaAtividade ser por período+
 *   atividade, não um campo único no cliente). Ex.: id 9 = "Escritórios de
 *   serviços contábeis autorizados pela legislação municipal a pagar o ISS em
 *   valor fixo em guia do Município" (exceto para o exterior). A mesma página
 *   confirma tipoDeclaracao (1=Original, 2=Retificadora), os códigos de
 *   tributo (1001=IRPJ, 1002=CSLL, 1004=COFINS, 1005=PIS, 1006=INSS/CPP,
 *   1007=ICMS, 1008=IPI, 1010=ISS) e a tabela "Qualificação Tributária"
 *   (1=Imunidade, 3=Lançamento de Ofício, 8=Substituição Tributária,
 *   9=Tributação Monofásica, 10=Antecipação com Encerramento, 11=Retenção de
 *   ISS) usados em isencoes/reducoes — mas o MAPEAMENTO "qual tributo se
 *   aplica a qual atividade" (App\Support\PgdasdAtividades::catalogo()) é
 *   inferido das regras gerais de Anexo do Simples Nacional, não uma tabela
 *   literal da SERPRO — vale conferir com um contador antes de confiar 100%.
 */
class PgdasdService
{
    public function __construct(
        private IntegraContadorClient $client,
    ) {}

    public function consultarDeclaracao(Cliente $cliente, string $anoCalendario): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: 'CONSDECLARACAO13',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: ['anoCalendario' => $anoCalendario],
        );
    }

    /**
     * Consulta o recibo/conteúdo completo de uma declaração já transmitida —
     * validado em produção como a fonte provável dos valores de apuração
     * (receita bruta, RBT12 etc.) já que CONSDECLARACAO13 só traz o histórico
     * de transmissões/status do DAS, sem esses valores.
     */
    public function consultarRecibo(Cliente $cliente, string $numeroDeclaracao): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: 'CONSDECREC15',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: ['numeroDeclaracao' => $numeroDeclaracao],
        );
    }

    /**
     * Consulta o extrato de um DAS específico (situação de pagamento etc.) —
     * payload de requisição confirmado publicamente (campo "numeroDas"), mas a
     * estrutura da resposta ainda não foi validada contra a API real — pode vir
     * como PDF em base64 (mesmo padrão do CONSDECREC15) ou como JSON estruturado.
     */
    public function consultarExtratoDas(Cliente $cliente, string $numeroDas): array
    {
        return $this->client->chamarServico(
            metodoGateway: 'Consultar',
            idServico: 'CONSEXTRATO16',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: ['numeroDas' => $numeroDas],
        );
    }

    /**
     * Monta o payload de apuração a partir da receita lançada manualmente e dos
     * dados fiscais cadastrados do cliente, e transmite via TRANSDECLARACAO11.
     * Ponto de entrada único usado tanto pelo Job em lote quanto pelo botão
     * manual de transmissão na tela.
     *
     * @param  bool  $confirmarReceitaZerada  precisa ser true explicitamente para
     *         transmitir uma declaração com receita zero (declaração "sem movimento"
     *         é legítima na Receita Federal, mas queremos uma confirmação
     *         deliberada em vez de deixar passar um esquecimento de preenchimento).
     * @param  bool  $confirmarRetificadora  precisa ser true explicitamente para
     *         reenviar um período que já tem declaração ORIGINAL na Receita —
     *         nesse caso a transmissão vai como retificadora (tipoDeclaracao=2,
     *         substitui a declaração anterior), não como Original de novo.
     */
    public function transmitirDeclaracaoDoCliente(Cliente $cliente, string $periodoApuracao, bool $confirmarReceitaZerada = false, bool $confirmarRetificadora = false): SimplesDasProcessamento
    {
        if (empty($cliente->cpfcnpj)) {
            throw new \RuntimeException("Cliente {$cliente->nome} não tem CNPJ cadastrado — obrigatório para transmitir.");
        }

        $receita = SimplesReceitaMensal::where('cliente_id', $cliente->id)
            ->where('periodo_apuracao', $periodoApuracao)
            ->first();

        if (!$receita) {
            throw new \RuntimeException("Cliente {$cliente->nome} não tem receita bruta lançada para o período {$periodoApuracao}. Cadastre antes de transmitir.");
        }

        if ($receita->regime_apuracao === 'caixa' && $receita->receita_bruta_caixa === null) {
            throw new \RuntimeException("Cliente {$cliente->nome}: regime de apuração é \"caixa\", mas a receita bruta (caixa) não foi preenchida — ambos os valores (competência e caixa) são exigidos pela Receita Federal, não só um deles.");
        }

        $valorTributavel = $receita->regime_apuracao === 'caixa'
            ? (float) $receita->receita_bruta_caixa
            : (float) $receita->receita_bruta_competencia;

        if ($valorTributavel <= 0 && !$confirmarReceitaZerada) {
            throw new \RuntimeException("Cliente {$cliente->nome}: receita bruta do período está zerada. Se for mesmo uma declaração sem movimento, confirme explicitamente antes de transmitir.");
        }

        $atividades = SimplesReceitaAtividade::with('tributos')
            ->where('cliente_id', $cliente->id)
            ->where('periodo_apuracao', $periodoApuracao)
            ->get();

        // Uma declaração "sem movimento" de verdade (receita zerada,
        // confirmada acima) não tem nenhuma atividade — diferente de uma
        // receita > 0 sem atividade lançada, que aí sim é um esquecimento.
        if ($atividades->isEmpty() && !$confirmarReceitaZerada) {
            throw new \RuntimeException("Cliente {$cliente->nome} não tem nenhuma atividade com receita lançada para o período {$periodoApuracao} — cadastre em \"Receitas por Atividade\" antes de transmitir.");
        }

        $somaAtividades = round((float) $atividades->sum('valor'), 2);

        if (abs($somaAtividades - round($valorTributavel, 2)) > 0.01) {
            throw new \RuntimeException("Cliente {$cliente->nome}: a soma das receitas por atividade (R$ " . number_format($somaAtividades, 2, ',', '.') . ") não bate com a receita bruta do período em regime \"{$receita->regime_apuracao}\" (R$ " . number_format($valorTributavel, 2, ',', '.') . "). Ajuste os valores por atividade antes de transmitir.");
        }

        $jaTransmitidaNaReceita = $this->declaracaoJaExisteNaReceita($cliente, $periodoApuracao);

        if ($jaTransmitidaNaReceita && !$confirmarRetificadora) {
            throw new \RuntimeException("Cliente {$cliente->nome} já tem uma declaração ORIGINAL transmitida para o período {$periodoApuracao} (confirmado via CONSDECLARACAO13). Se você corrigiu os dados e quer substituir essa declaração, confirme explicitamente que esta transmissão é uma RETIFICADORA antes de continuar.");
        }

        $tipoDeclaracao = $jaTransmitidaNaReceita ? 2 : 1;

        $exigeFolhaSalario = $atividades->contains(fn (SimplesReceitaAtividade $a) => in_array($a->id_atividade, PgdasdAtividades::ATIVIDADES_FATOR_R, true));

        if ($exigeFolhaSalario && $receita->folha_salario === null) {
            throw new \RuntimeException("Cliente {$cliente->nome}: há atividade sujeita ao fator \"r\" (Anexo V) lançada para {$periodoApuracao}, que exige informar o valor da folha de salário do mês anterior — preencha antes de transmitir.");
        }

        $dadosApuracao = [
            'cnpjCompleto' => preg_replace('/\D/', '', $cliente->cpfcnpj ?? ''),
            'declaracao' => $this->montarDeclaracao($cliente, $receita, $atividades, $periodoApuracao, $exigeFolhaSalario, $tipoDeclaracao),
            'indicadorTransmissao' => true,
            'indicadorComparacao' => false,
        ];

        return $this->transmitirDeclaracao($cliente, $periodoApuracao, $dadosApuracao, $tipoDeclaracao);
    }

    /**
     * Verifica se já existe declaração ORIGINAL transmitida para este período,
     * consultando o histórico real (CONSDECLARACAO13) antes de qualquer
     * transmissão — evita mandar "Original" para um período que a Receita já
     * tem registrado (o que provavelmente seria rejeitado ou geraria
     * inconsistência). Gera uma chamada paga extra por transmissão, mas é o
     * preço de não arriscar uma declaração fiscal incorreta.
     */
    private function declaracaoJaExisteNaReceita(Cliente $cliente, string $periodoApuracao): bool
    {
        $anoCalendario = substr($periodoApuracao, 0, 4);

        try {
            $resposta = $this->consultarDeclaracao($cliente, $anoCalendario);
        } catch (\Throwable $e) {
            Log::warning('[PGDASD] declaracaoJaExisteNaReceita: falha ao consultar histórico, prosseguindo sem essa checagem', [
                'cliente_id' => $cliente->id,
                'periodo' => $periodoApuracao,
                'erro' => $e->getMessage(),
            ]);

            return false;
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];

        foreach ($dados['periodos'] ?? [] as $periodo) {
            if ((string) ($periodo['periodoApuracao'] ?? '') !== $periodoApuracao) {
                continue;
            }

            foreach ($periodo['operacoes'] ?? [] as $operacao) {
                if (!empty($operacao['indiceDeclaracao']['numeroDeclaracao'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Monta o objeto "declaracao" do payload do TRANSDECLARACAO11 — estrutura
     * baseada em modelo de terceiro, ver ressalva no docblock da classe.
     * Suporta múltiplas atividades por estabelecimento (réplica da etapa
     * "Atividades"/"Receitas" do e-CAC), cada uma com seu próprio tratamento
     * de isenção/redução por tributo.
     *
     * receitaPaCompetenciaInterno é sempre enviado (usado pela Receita para
     * acumular RBT12/RBA independente do regime); receitaPaCaixaInterno só é
     * enviado quando o regime de apuração é "caixa" — os dois valores são
     * conceitos diferentes (competência = receita auferida, caixa = recebida)
     * e não podem ser um substituto do outro, mesmo os dois compondo o mesmo período.
     *
     * "CnpjCompleto" dentro de "estabelecimentos" foi capitalizado (C
     * maiúsculo) numa primeira tentativa de corrigir o erro MSG_ISN_036
     * ("Required property 'CnpjCompleto' not found") em produção
     * (2026-08-03) — mas o MESMO erro, byte a byte igual, persistiu mesmo
     * com o campo já capitalizado no payload enviado. Isso indica que o
     * erro NÃO era sobre a capitalização do campo aninhado, e sim sobre um
     * campo "cnpjCompleto" (nível raiz do "dados", fora de "declaracao")
     * que nunca era enviado — hipótese baseada na doc oficial (apicenter.
     * estaleiro.serpro.gov.br/documentacao/api-integra-contador/pt/
     * solucoes/integra-sn/pgdasd/servicos/entregar_declaracao_mensal_entrada/),
     * que lista "cnpjCompleto" como campo do nível raiz de "dados", irmão
     * de "declaracao"/"indicadorTransmissao"/"indicadorComparacao" — por
     * isso agora é enviado também em transmitirDeclaracaoDoCliente().
     * CONFIRMADO em produção (2026-08-03): depois desse ajuste, o erro
     * avançou de "'CnpjCompleto' not found" para "'Pa' not found" — ou
     * seja, o campo de nível raiz que identifica o período NÃO é
     * "periodoApuracao" (usado por CONSDECLARACAO13/GERARDAS12), é "pa"
     * (confirma a doc oficial). Corrigido em transmitirDeclaracao(); os
     * outros idServico do PGDASD continuam usando "periodoApuracao"
     * normalmente, essa troca vale só para o TRANSDECLARACAO11.
     *
     * "folhasSalario" só é enviado quando alguma atividade lançada é sujeita
     * ao fator "r" (PgdasdAtividades::ATIVIDADES_FATOR_R). Formato (array de
     * {pa, valor}) é inferência a partir do nome/estrutura de
     * "receitasBrutasAnteriores" (mesmo padrão histórico por período).
     *
     * CONFIRMADO em produção (2026-08-03): o período exigido é o MÊS
     * ANTERIOR ao que está sendo declarado, não o próprio período da
     * apuração — ao declarar 07/2026 com folhasSalario=[{pa:202607,...}], a
     * API rejeitou pedindo especificamente "06/2026". Corrigido para enviar
     * sempre period_apuracao - 1 mês. Ainda não confirmado se isso é fixo
     * (sempre só o mês anterior) ou se, uma vez preenchido, a próxima
     * transmissão pode pedir outro mês mais antigo ainda (histórico
     * acumulado nunca antes informado pra esse CNPJ) — revalidar se
     * aparecer um novo erro parecido.
     *
     * @param  \Illuminate\Support\Collection<int, SimplesReceitaAtividade>  $atividades
     */
    private function montarDeclaracao(Cliente $cliente, SimplesReceitaMensal $receita, $atividades, string $periodoApuracao, bool $exigeFolhaSalario, int $tipoDeclaracao = 1): array
    {
        // A API valida que receitaPaCompetenciaInterno/Externo (e o par
        // "Caixa") sejam exatamente a soma das atividades classificadas como
        // mercado interno/externo (PgdasdAtividades::ehParaExterior) —
        // confirmado em produção (2026-08-04) com a BVD, que tem a atividade
        // 30 ("Prestação de serviços para o exterior") junto com atividades
        // domésticas: jogar o total inteiro em "Interno" é rejeitado.
        $atividadesExterno = $atividades->filter(
            fn (SimplesReceitaAtividade $atividade) => PgdasdAtividades::ehParaExterior((int) $atividade->id_atividade)
        );
        $atividadesInterno = $atividades->reject(
            fn (SimplesReceitaAtividade $atividade) => PgdasdAtividades::ehParaExterior((int) $atividade->id_atividade)
        );

        $declaracao = [
            'tipoDeclaracao' => $tipoDeclaracao, // 1 = Original, 2 = Retificadora
            'receitaPaCompetenciaInterno' => (float) $atividadesInterno->sum('valor'),
            'receitaPaCompetenciaExterno' => (float) $atividadesExterno->sum('valor'),
        ];

        if ($receita->regime_apuracao === 'caixa') {
            $declaracao['receitaPaCaixaInterno'] = (float) $atividadesInterno->sum('valor');
            $declaracao['receitaPaCaixaExterno'] = (float) $atividadesExterno->sum('valor');
        }

        if ($exigeFolhaSalario) {
            $periodoAnterior = \Carbon\Carbon::createFromFormat('Ym', $periodoApuracao)->subMonthNoOverflow()->format('Ym');

            $declaracao['folhasSalario'] = [
                ['pa' => (int) $periodoAnterior, 'valor' => (float) $receita->folha_salario],
            ];
        }

        $estabelecimento = ['CnpjCompleto' => preg_replace('/\D/', '', $cliente->cpfcnpj ?? '')];

        // Documentação oficial da SERPRO (entregar_declaracao_mensal_entrada):
        // "Se não houve atividade para o estabelecimento, não enviar esta
        // lista" — ou seja, pra uma declaração sem movimento (sem nenhuma
        // atividade lançada) a chave "atividades" tem que ficar OMITIDA, não
        // enviada como array vazio. Confirmado em produção (2026-08-04,
        // MACHADINHO): mandar "atividades": [] foi rejeitado com "O valor da
        // atividade deve ser maior que zero" (a API tentou validar um item
        // que não existia).
        if ($atividades->isNotEmpty()) {
            // Agrupa por id_atividade antes de montar o payload: o relatório do
            // Domínio (e a tela manual, ver das.blade.php) permitem lançar a
            // MESMA atividade em mais de uma linha no mesmo período — cada uma
            // com sua própria receita e tratamento tributário por tributo (ex.:
            // "revenda com substituição tributária" quebrada em "Tabela 1 -
            // Substituição somente do ICMS" e "Tabela 4 - Substituição do PIS/
            // PASEP, COFINS e do ICMS"). Enviar isso como DOIS objetos de
            // "atividades" com o mesmo idAtividade fez a API rejeitar o
            // TRANSDECLARACAO11 com "A soma dos valores das atividades está
            // diferente do valor total de receita do Pa" (confirmado em
            // produção 2026-08-07) — a API parece deduplicar/só considerar uma
            // ocorrência por idAtividade na soma. O array "receitasAtividade"
            // dentro de cada atividade já existe justamente pra isso: várias
            // receitas/tratamentos sob o mesmo idAtividade, com "valorAtividade"
            // sendo a soma de todas elas.
            $estabelecimento['atividades'] = $atividades
                ->groupBy('id_atividade')
                ->map(fn ($linhasDaAtividade) => $this->montarAtividade($linhasDaAtividade))
                ->values()
                ->all();
        }

        $declaracao['estabelecimentos'] = [$estabelecimento];

        return $declaracao;
    }

    /**
     * Monta uma entrada de "atividades" a partir de SimplesReceitaAtividade,
     * convertendo os tributos com tipo_ajuste != "normal" em "reducoes"/
     * "qualificacoesTributarias".
     *
     * CORRIGIDO em 2026-08-04: imunidade/lançamento de ofício/substituição
     * tributária/tributação monofásica/antecipação com encerramento/retenção
     * de ISS iam antes pro array "isencoes" (com "identificador" = código da
     * tabela "Qualificação Tributária" 1/3/8/9/10/11) — mas "isencoes"
     * NÃO é esse array. Confirmado em produção com a WEIAND: idAtividade 5 +
     * ICMS com identificador=8 (Substituição Tributária) em "isencoes" foi
     * rejeitado com MSG_ISN_008 "Campo 'isencao/identificacao' inválido", e
     * a mesma atividade SEM nenhuma qualificação foi rejeitada com MSG_E0044
     * exigindo justamente essa informação — ou seja, o dado é obrigatório,
     * só não pertencia a "isencoes". Consultando a documentação oficial da
     * SERPRO (apicenter.estaleiro.serpro.gov.br/.../pgdasd/servicos/
     * entregar_declaracao_mensal_entrada/ e .../dados_de_dominio/), o
     * exemplo de payload mostra "receitasAtividade" com 4 arrays distintos:
     * "isencoes" ({codTributo,valor,identificador}, identificador da tabela
     * "Identificador de isenção/redução" 1=Normal/2=Cesta básica — mesma
     * tabela usada em "reducoes"), "reducoes" (idem + percentualReducao), e
     * "qualificacoesTributarias" ({codigoTributo,id}, id da tabela
     * "Qualificação Tributária" 1/3/8/9/10/11) — é NESSE terceiro array que
     * imunidade/lançamento de ofício/substituição/monofásica/antecipação/
     * retenção de ISS entram. Ainda não testado com sucesso contra a API
     * real (só sabemos que o formato antigo estava errado) — revalidar na
     * próxima transmissão real com uma dessas qualificações.
     *
     * "exigibilidade_suspensa" não tem mapeamento confirmado no payload —
     * bloqueia a transmissão em vez de arriscar enviar campo errado.
     *
     * Recebe TODAS as linhas (SimplesReceitaAtividade) do mesmo id_atividade
     * no período — pode ser mais de uma, ver comentário em montarDeclaracao()
     * sobre por que elas precisam virar UM único objeto "atividade" com
     * "valorAtividade" somado e "receitasAtividade" com um item por linha.
     *
     * @param  \Illuminate\Support\Collection<int, SimplesReceitaAtividade>  $linhasDaAtividade
     */
    private function montarAtividade($linhasDaAtividade): array
    {
        $idAtividade = $linhasDaAtividade->first()->id_atividade;
        $receitasAtividade = [];

        foreach ($linhasDaAtividade as $atividade) {
            $reducoes = [];
            $qualificacoesTributarias = [];

            foreach ($atividade->tributos as $tributo) {
                if ($tributo->tipo_ajuste === 'exigibilidade_suspensa') {
                    throw new \RuntimeException("Atividade {$atividade->id_atividade}: \"exigibilidade suspensa\" ainda não tem mapeamento confirmado no payload do TRANSDECLARACAO11 — remova esse ajuste ou aguarde essa funcionalidade antes de transmitir.");
                }

                if (
                    $tributo->cod_tributo === PgdasdAtividades::TRIBUTO_ISS
                    && $tributo->tipo_ajuste !== 'normal'
                    && in_array($atividade->id_atividade, PgdasdAtividades::ATIVIDADES_ISS_TRATAMENTO_PROPRIO, true)
                ) {
                    // "retencao_iss" é permitido nas atividades "com retenção/substituição"
                    // (o próprio nome da atividade já declara isso) — só bloqueamos as
                    // outras qualificações (isenção/imunidade/substituição/etc.), que
                    // conflitam com o tratamento de ISS já fixado pela atividade.
                    $retencaoPermitida = $tributo->tipo_ajuste === 'retencao_iss'
                        && in_array($atividade->id_atividade, PgdasdAtividades::ATIVIDADES_ISS_COM_RETENCAO, true);

                    if (! $retencaoPermitida) {
                        throw new \RuntimeException("Atividade {$atividade->id_atividade}: o tratamento do ISS já é definido pela própria descrição da atividade (\"sem/com retenção...\") — não é possível aplicar também uma qualificação tributária (isenção/imunidade/substituição/etc.) no ISS dessa atividade, a API rejeita como conflitante. Se o ISS realmente não incide nessa receita, escolha uma atividade cuja descrição já reflita isso, ou deixe o ISS como \"Normal\".");
                    }
                }

                if (
                    $tributo->cod_tributo === PgdasdAtividades::TRIBUTO_ICMS
                    && in_array($tributo->tipo_ajuste, PgdasdAtividades::ICMS_QUALIFICACOES_SUBSTITUICAO, true)
                    && in_array($atividade->id_atividade, PgdasdAtividades::ATIVIDADES_ICMS_SEM_SUBSTITUICAO, true)
                ) {
                    throw new \RuntimeException("Atividade {$atividade->id_atividade}: essa atividade é \"substituto tributário do ICMS\" (sem substituição na própria receita) — não é possível marcar o ICMS como Substituição Tributária, Tributação Monofásica ou Antecipação com Encerramento, a API rejeita como conflitante. Isenção/Redução/Imunidade/Lançamento de Ofício continuam permitidos normalmente.");
                }

                if (
                    $tributo->cod_tributo === PgdasdAtividades::TRIBUTO_ICMS
                    && in_array($atividade->id_atividade, PgdasdAtividades::ATIVIDADES_ICMS_SUBSTITUIDO, true)
                    && ! in_array($tributo->tipo_ajuste, PgdasdAtividades::ICMS_QUALIFICACOES_SUBSTITUICAO, true)
                ) {
                    throw new \RuntimeException("Atividade {$atividade->id_atividade}: essa atividade é \"substituído tributário do ICMS\" — é OBRIGATÓRIO marcar o ICMS como Substituição Tributária, Tributação Monofásica ou Antecipação com Encerramento (não pode ficar \"Normal\"), confirmado em produção (MSG_E0044).");
                }

                $idQualificacao = match ($tributo->tipo_ajuste) {
                    'imunidade' => 1,
                    'lancamento_oficio' => 3,
                    'substituicao_tributaria' => 8,
                    'tributacao_monofasica' => 9,
                    'antecipacao_encerramento' => 10,
                    'retencao_iss' => 11,
                    default => null,
                };

                if ($idQualificacao !== null) {
                    $qualificacoesTributarias[] = [
                        'codigoTributo' => $tributo->cod_tributo,
                        'id' => $idQualificacao,
                    ];

                    continue;
                }

                if ($tributo->tipo_ajuste === 'isencao' || $tributo->tipo_ajuste === 'reducao') {
                    // "isencao" = redução de 100% (a tela não pede percentual pra
                    // esse caso, ver renderTributoCell no das.blade.php) — a API
                    // rejeita percentualReducao=0 como inválido (confirmado em
                    // produção 2026-08-03, MSG_ISN_008 "Campo 'reducao/
                    // percentualReducao' inválido"), então mandamos 100 fixo
                    // pra isenção em vez do 0 que vinha de percentual_reducao nulo.
                    $reducoes[] = [
                        'codTributo' => $tributo->cod_tributo,
                        'valor' => (float) $tributo->valor,
                        'percentualReducao' => $tributo->tipo_ajuste === 'isencao' ? 100.0 : (float) ($tributo->percentual_reducao ?? 0),
                        'identificador' => $tributo->identificador_isencao ?? 1,
                    ];
                }
            }

            $receitasAtividade[] = [
                'valor' => (float) $atividade->valor,
                'isencoes' => [],
                'reducoes' => $reducoes,
                'qualificacoesTributarias' => $qualificacoesTributarias,
            ];
        }

        return [
            'idAtividade' => $idAtividade,
            'valorAtividade' => (float) $linhasDaAtividade->sum('valor'),
            'receitasAtividade' => $receitasAtividade,
        ];
    }

    /**
     * Transmite a declaração mensal, mas evita rechamar a API se este período
     * já foi transmitido com sucesso para o cliente — a API é cobrada por
     * chamada. Esse guard local só se aplica a Original (tipoDeclaracao=1):
     * numa retificadora queremos SEMPRE rechamar a API de verdade, mesmo já
     * havendo um registro de sucesso anterior — é justamente o objetivo.
     */
    public function transmitirDeclaracao(Cliente $cliente, string $periodoApuracao, array $dadosApuracao, int $tipoDeclaracao = 1): SimplesDasProcessamento
    {
        if ($tipoDeclaracao === 1) {
            $jaProcessado = SimplesDasProcessamento::where('cliente_id', $cliente->id)
                ->where('periodo_apuracao', $periodoApuracao)
                ->whereIn('status', ['sucesso', 'ja_transmitido'])
                ->first();

            if ($jaProcessado) {
                Log::info('[PGDASD] transmitirDeclaracao: já transmitido, ignorando', [
                    'cliente_id' => $cliente->id,
                    'periodo' => $periodoApuracao,
                ]);

                return $jaProcessado;
            }
        }

        $registro = SimplesDasProcessamento::updateOrCreate(
            ['cliente_id' => $cliente->id, 'periodo_apuracao' => $periodoApuracao],
            ['status' => 'pendente', 'tipo_declaracao' => $tipoDeclaracao]
        );

        try {
            $resposta = $this->client->chamarServico(
                metodoGateway: 'Declarar',
                idServico: 'TRANSDECLARACAO11',
                versaoSistema: '1.0',
                cliente: $cliente,
                dados: array_merge(['pa' => (int) $periodoApuracao], $dadosApuracao),
            );

            // Bug: estava lendo "numeroRecibo" direto do envelope de resposta
            // (contratante/pedidoDados/status/dados/mensagens), não do
            // conteúdo de "dados" (que vem como string JSON, precisa
            // decodificar) — por isso numero_recibo sempre ficava nulo.
            // Ainda não confirmado qual o nome exato do campo dentro do
            // "dados" de sucesso do TRANSDECLARACAO11 (nunca visto um
            // sucesso real antes) — logamos o corpo decodificado pra
            // conseguir confirmar no próximo teste real.
            $dadosResposta = json_decode($resposta['dados'] ?? '{}', true) ?? [];

            Log::info('[PGDASD] transmitirDeclaracao: sucesso, corpo de "dados" decodificado', [
                'cliente_id' => $cliente->id,
                'dados' => $dadosResposta,
            ]);

            $registro->update([
                'status' => 'sucesso',
                'numero_recibo' => $dadosResposta['numeroDeclaracao'] ?? $dadosResposta['numeroRecibo'] ?? $dadosResposta['numeroDas'] ?? null,
                'mensagem_erro' => null,
                'processado_em' => now(),
            ]);
        } catch (\Throwable $e) {
            $registro->update([
                'status' => 'erro',
                'mensagem_erro' => $e->getMessage(),
                'processado_em' => now(),
            ]);

            throw $e;
        }

        return $registro->fresh();
    }

    /**
     * Gera o DAS de um período já declarado — confirmado na doc oficial
     * (apicenter.estaleiro.serpro.gov.br/.../solucoes/integra-sn/pgdasd/
     * servicos/gerar_das/ e .../exemplos/retorno_gerar_das/). Payload de
     * requisição: periodoApuracao (AAAAMM) + dataConsolidacao opcional
     * (AAAAMMDD, só datas futuras). Resposta traz "pdf" (base64, SEM
     * "nomeArquivo" ao lado — diferente do CONSDECREC15) e "detalhamento"
     * estruturado: periodoApuracao, numeroDocumento, dataVencimento,
     * dataLimiteAcolhimento, valores{principal,multa,juros,total},
     * composicao[]{codigo,denominacao,...}.
     *
     * Operação de baixo risco (relê/reemite um DAS de declaração já
     * transmitida, não cria nada novo) — diferente de TRANSDECLARACAO11.
     */
    public function emitirDas(Cliente $cliente, string $periodoApuracao, ?string $dataConsolidacao = null): array
    {
        $dados = ['periodoApuracao' => (int) $periodoApuracao];

        if ($dataConsolidacao) {
            $dados['dataConsolidacao'] = $dataConsolidacao;
        }

        return $this->client->chamarServico(
            metodoGateway: 'Emitir',
            idServico: 'GERARDAS12',
            versaoSistema: '1.0',
            cliente: $cliente,
            dados: $dados,
        );
    }
}
