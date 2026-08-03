<?php

namespace App\Services\SimplesNacional;

use App\Models\Cliente;
use App\Models\SimplesDasProcessamento;
use App\Models\SimplesReceitaAtividade;
use App\Models\SimplesReceitaMensal;
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
     */
    public function transmitirDeclaracaoDoCliente(Cliente $cliente, string $periodoApuracao, bool $confirmarReceitaZerada = false): SimplesDasProcessamento
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

        if ($atividades->isEmpty()) {
            throw new \RuntimeException("Cliente {$cliente->nome} não tem nenhuma atividade com receita lançada para o período {$periodoApuracao} — cadastre em \"Receitas por Atividade\" antes de transmitir.");
        }

        $somaAtividades = round((float) $atividades->sum('valor'), 2);

        if (abs($somaAtividades - round($valorTributavel, 2)) > 0.01) {
            throw new \RuntimeException("Cliente {$cliente->nome}: a soma das receitas por atividade (R$ " . number_format($somaAtividades, 2, ',', '.') . ") não bate com a receita bruta do período em regime \"{$receita->regime_apuracao}\" (R$ " . number_format($valorTributavel, 2, ',', '.') . "). Ajuste os valores por atividade antes de transmitir.");
        }

        if ($this->declaracaoJaExisteNaReceita($cliente, $periodoApuracao)) {
            throw new \RuntimeException("Cliente {$cliente->nome} já tem uma declaração ORIGINAL transmitida para o período {$periodoApuracao} (confirmado via CONSDECLARACAO13). Este sistema ainda não implementa retificadora (tipoDeclaracao 2) — transmitir de novo como Original provavelmente será rejeitado pela Receita Federal.");
        }

        $dadosApuracao = [
            'cnpjCompleto' => preg_replace('/\D/', '', $cliente->cpfcnpj ?? ''),
            'declaracao' => $this->montarDeclaracao($cliente, $receita, $atividades),
            'indicadorTransmissao' => true,
            'indicadorComparacao' => false,
        ];

        return $this->transmitirDeclaracao($cliente, $periodoApuracao, $dadosApuracao);
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
     * @param  \Illuminate\Support\Collection<int, SimplesReceitaAtividade>  $atividades
     */
    private function montarDeclaracao(Cliente $cliente, SimplesReceitaMensal $receita, $atividades): array
    {
        $declaracao = [
            'tipoDeclaracao' => 1, // 1 = Original
            'receitaPaCompetenciaInterno' => (float) $receita->receita_bruta_competencia,
            'receitaPaCompetenciaExterno' => 0.0,
        ];

        if ($receita->regime_apuracao === 'caixa') {
            $declaracao['receitaPaCaixaInterno'] = (float) $receita->receita_bruta_caixa;
            $declaracao['receitaPaCaixaExterno'] = 0.0;
        }

        $declaracao['estabelecimentos'] = [
            [
                'CnpjCompleto' => preg_replace('/\D/', '', $cliente->cpfcnpj ?? ''),
                'atividades' => $atividades->map(fn (SimplesReceitaAtividade $atividade) => $this->montarAtividade($atividade))->values()->all(),
            ],
        ];

        return $declaracao;
    }

    /**
     * Monta uma entrada de "atividades" a partir de SimplesReceitaAtividade,
     * convertendo os tributos com tipo_ajuste != "normal" em "isencoes"/
     * "reducoes" — mapeamento (identificador = código da tabela oficial
     * "Qualificação Tributária" 1/3/8/9/10/11, ou "Identificador de
     * isenção/redução" 1/2 para isencao/reducao) baseado no modelo de
     * terceiro, ainda não confirmado contra a API real.
     *
     * "exigibilidade_suspensa" não tem mapeamento confirmado no payload —
     * bloqueia a transmissão em vez de arriscar enviar campo errado.
     */
    private function montarAtividade(SimplesReceitaAtividade $atividade): array
    {
        $isencoes = [];
        $reducoes = [];

        foreach ($atividade->tributos as $tributo) {
            if ($tributo->tipo_ajuste === 'exigibilidade_suspensa') {
                throw new \RuntimeException("Atividade {$atividade->id_atividade}: \"exigibilidade suspensa\" ainda não tem mapeamento confirmado no payload do TRANSDECLARACAO11 — remova esse ajuste ou aguarde essa funcionalidade antes de transmitir.");
            }

            $identificadorQualificacao = match ($tributo->tipo_ajuste) {
                'imunidade' => 1,
                'lancamento_oficio' => 3,
                'substituicao_tributaria' => 8,
                'tributacao_monofasica' => 9,
                'antecipacao_encerramento' => 10,
                'retencao_iss' => 11,
                default => null,
            };

            if ($identificadorQualificacao !== null) {
                $isencoes[] = [
                    'codTributo' => $tributo->cod_tributo,
                    'valor' => (float) $tributo->valor,
                    'identificador' => $identificadorQualificacao,
                ];

                continue;
            }

            if ($tributo->tipo_ajuste === 'isencao' || $tributo->tipo_ajuste === 'reducao') {
                $reducoes[] = [
                    'codTributo' => $tributo->cod_tributo,
                    'valor' => (float) $tributo->valor,
                    'percentualReducao' => (float) ($tributo->percentual_reducao ?? 0),
                    'identificador' => $tributo->identificador_isencao ?? 1,
                ];
            }
        }

        return [
            'idAtividade' => $atividade->id_atividade,
            'valorAtividade' => (float) $atividade->valor,
            'receitasAtividade' => [
                [
                    'valor' => (float) $atividade->valor,
                    'isencoes' => $isencoes,
                    'reducoes' => $reducoes,
                ],
            ],
        ];
    }

    /**
     * Transmite a declaração mensal, mas evita rechamar a API se este período
     * já foi transmitido com sucesso para o cliente — a API é cobrada por chamada.
     */
    public function transmitirDeclaracao(Cliente $cliente, string $periodoApuracao, array $dadosApuracao): SimplesDasProcessamento
    {
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

        $registro = SimplesDasProcessamento::updateOrCreate(
            ['cliente_id' => $cliente->id, 'periodo_apuracao' => $periodoApuracao],
            ['status' => 'pendente']
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
