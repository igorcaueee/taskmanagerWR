<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteDadosSimples;
use App\Models\DefisDeclaracao;
use App\Models\DefisSocio;
use App\Models\IntegraContadorConfiguracao;
use App\Models\MitApuracaoRascunho;
use App\Models\SimplesDasProcessamento;
use App\Models\SimplesReceitaAtividade;
use App\Models\SimplesReceitaMensal;
use App\Services\CnpjPublicoService;
use App\Services\NfseService;
use App\Services\SimplesNacional\CaixaPostalService;
use App\Services\SimplesNacional\DefisService;
use App\Services\SimplesNacional\DctfWebService;
use App\Services\SimplesNacional\DominioImportParser;
use App\Services\SimplesNacional\DteService;
use App\Services\SimplesNacional\IntegraContadorAuthService;
use App\Services\SimplesNacional\MitService;
use App\Services\SimplesNacional\ParcelamentoMeiService;
use App\Services\SimplesNacional\ParcelamentoService;
use App\Services\SimplesNacional\PgdasdService;
use App\Services\SimplesNacional\ProcuracoesService;
use App\Services\SimplesNacional\SitfisService;
use App\Support\MitCodigosReceita;
use App\Support\PgdasdAtividades;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SimplesNacionalController extends Controller
{
    public function __construct(
        private NfseService $nfse,
        private CnpjPublicoService $cnpjPublico,
        private DominioImportParser $dominioParser,
    ) {}

    /**
     * Hub — grid de atalhos para cada módulo (cada um em sua própria tela,
     * ver telaConfiguracao/telaImportarDominio/etc.).
     */
    public function index()
    {
        return view('simples-nacional.hub');
    }

    private function clientesSimplesAtivos()
    {
        return Cliente::where('regime_tributario', 'Simples Nacional')
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get(['id', 'nome', 'cpfcnpj']);
    }

    /**
     * Clientes ativos de qualquer regime tributário — usado pelas telas que
     * não são exclusivas do Simples Nacional (Caixa Postal, SITFIS,
     * Procurações, MIT: consultas gerais da Receita Federal que valem pra
     * qualquer CNPJ, ex.: MIT é mais comum em Lucro Real/Presumido).
     */
    private function clientesAtivos()
    {
        return Cliente::where('status', 'ativo')
            ->orderBy('nome')
            ->get(['id', 'nome', 'cpfcnpj']);
    }

    private function clientesMeiAtivos()
    {
        return Cliente::where('regime_tributario', 'MEI')
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get(['id', 'nome', 'cpfcnpj']);
    }

    public function telaConfiguracao()
    {
        return view('simples-nacional.configuracao');
    }

    /**
     * Tela única do DAS mensal (PGDASD) — reúne em abas o que antes eram 4
     * telas separadas (lançar receita/transmitir, consultar declarações,
     * importar do Domínio, processamentos em lote), já que todas giram em
     * torno do mesmo fluxo mensal.
     */
    public function telaDas(Request $request)
    {
        $periodo = $request->get('periodo', now()->subMonthNoOverflow()->format('Ym'));

        $processamentos = SimplesDasProcessamento::with('cliente')
            ->where('periodo_apuracao', $periodo)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->get('status')))
            ->orderBy('cliente_id')
            ->paginate(30)
            ->withQueryString();

        return view('simples-nacional.das', [
            'clientes' => $this->clientesSimplesAtivos(),
            'atividadesCatalogo' => PgdasdAtividades::catalogo(),
            'nomesTributos' => PgdasdAtividades::NOMES_TRIBUTOS,
            'atividadesFatorR' => PgdasdAtividades::ATIVIDADES_FATOR_R,
            'atividadesIssTratamentoProprio' => PgdasdAtividades::ATIVIDADES_ISS_TRATAMENTO_PROPRIO,
            'atividadesIssComRetencao' => PgdasdAtividades::ATIVIDADES_ISS_COM_RETENCAO,
            'processamentos' => $processamentos,
            'periodo' => $periodo,
        ]);
    }

    public function telaParcelamentos()
    {
        return view('simples-nacional.parcelamentos', ['clientes' => $this->clientesSimplesAtivos()]);
    }

    public function telaParcelamentosMei()
    {
        return view('simples-nacional.parcelamentos-mei', [
            'clientes' => $this->clientesMeiAtivos(),
            'programas' => ParcelamentoMeiService::programasValidos(),
        ]);
    }

    public function telaDefis()
    {
        return view('simples-nacional.defis', ['clientes' => $this->clientesSimplesAtivos()]);
    }

    public function telaCaixaPostal()
    {
        return view('simples-nacional.caixa-postal', ['clientes' => $this->clientesAtivos()]);
    }

    public function telaSitfis()
    {
        return view('simples-nacional.sitfis', ['clientes' => $this->clientesAtivos()]);
    }

    public function telaProcuracoes()
    {
        return view('simples-nacional.procuracoes', ['clientes' => $this->clientesAtivos()]);
    }

    public function telaMit()
    {
        return view('simples-nacional.mit', [
            'clientes' => $this->clientesAtivos(),
            'catalogoReceitaMit' => MitCodigosReceita::catalogo(),
        ]);
    }

    public function telaDctfWeb()
    {
        return view('simples-nacional.dctfweb', [
            'clientes' => $this->clientesAtivos(),
            'categorias' => DctfWebService::CATEGORIAS,
        ]);
    }

    /**
     * Busca as declarações do PGDASD de um cliente por CNPJ/ano (CONSDECLARACAO13),
     * já normalizando o histórico bruto da API em uma lista por período com o
     * número da declaração e o status do DAS prontos para exibir na tela.
     */
    public function consultarDeclaracoes(Request $request, PgdasdService $pgdasd): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'ano_calendario' => 'required|digits:4',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        if (empty($cliente->cpfcnpj)) {
            return response()->json(['error' => "Cliente {$cliente->nome} não tem CNPJ cadastrado."], 422);
        }

        try {
            $resposta = $pgdasd->consultarDeclaracao($cliente, $validated['ano_calendario']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];

        return response()->json([
            'success' => true,
            'cliente' => ['id' => $cliente->id, 'nome' => $cliente->nome, 'cpfcnpj' => $cliente->cpfcnpj],
            'periodos' => $this->normalizarPeriodos($dados['periodos'] ?? []),
        ]);
    }

    /**
     * Busca o recibo/declaração completa (com RBT12, receitas anteriores etc.
     * dentro do PDF) de uma declaração específica já identificada na consulta
     * acima — o usuário não precisa mais informar CNPJ/número manualmente.
     */
    public function buscarRbt12(Request $request, PgdasdService $pgdasd): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'numero_declaracao' => 'required|string',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resposta = $pgdasd->consultarRecibo($cliente, $validated['numero_declaracao']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];
        $arquivos = $this->extrairESalvarPdfs($dados);

        return response()->json([
            'success' => true,
            'arquivos' => $arquivos,
        ]);
    }

    /**
     * Busca o extrato de um DAS específico (CONSEXTRATO16) — número do DAS já
     * vem da lista de declarações consultada, sem precisar digitar manualmente.
     * Resposta ainda não validada contra a API real (ver ressalva no PgdasdService),
     * por isso devolvemos tanto os PDFs quanto o JSON restante para inspeção.
     */
    public function consultarExtrato(Request $request, PgdasdService $pgdasd): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'numero_das' => 'required|string',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resposta = $pgdasd->consultarExtratoDas($cliente, $validated['numero_das']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];
        $arquivos = $this->extrairESalvarPdfs($dados);

        return response()->json([
            'success' => true,
            'arquivos' => $arquivos,
            'dados_sem_pdf' => $this->removerCamposPdf($dados),
        ]);
    }

    /**
     * Gera/reemite o DAS de um período já declarado (GERARDAS12) — operação de
     * baixo risco (relê/reemite, não cria nada novo), confirmada em produção.
     * A resposta vem como uma LISTA (mesmo para um único DAS) e o campo certo
     * é "detalhamentoDas" (não "detalhamento" como a doc resumida sugeria) —
     * confirmado em 2026-07-21: {pdf, cnpjCompleto, detalhamentoDas: {
     * periodoApuracao, numeroDocumento, dataVencimento (AAAAMMDD),
     * dataLimiteAcolhimento, valores{principal,multa,juros,total}, composicao[]}}.
     */
    public function emitirDas(Request $request, PgdasdService $pgdasd): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'periodo_apuracao' => 'required|digits:6',
            'data_consolidacao' => 'nullable|date_format:Ymd|after:today',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resposta = $pgdasd->emitirDas($cliente, $validated['periodo_apuracao'], $validated['data_consolidacao'] ?? null);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];
        $primeiroItem = array_is_list($dados) ? ($dados[0] ?? []) : $dados;
        $detalhamento = $primeiroItem['detalhamentoDas'] ?? $primeiroItem['detalhamento'] ?? $primeiroItem;

        $nomeArquivo = "DAS-{$validated['periodo_apuracao']}-{$cliente->id}.pdf";
        $arquivo = $this->extrairPdfAvulso($dados, $nomeArquivo);

        return response()->json([
            'success' => true,
            'arquivo' => $arquivo,
            'detalhamento' => $detalhamento,
        ]);
    }

    /**
     * Lista o histórico de parcelamentos PARCSN do cliente (PEDIDOSPARC163),
     * com a situação de cada um — é o ponto de partida para achar qual
     * parcelamento está ativo/com gargalo.
     */
    public function consultarParcelamentosPedidos(Request $request, ParcelamentoService $parcelamento): JsonResponse
    {
        $validated = $request->validate(['cliente_id' => 'required|exists:clientes,id']);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        if (empty($cliente->cpfcnpj)) {
            return response()->json(['error' => "Cliente {$cliente->nome} não tem CNPJ cadastrado."], 422);
        }

        try {
            $resposta = $parcelamento->consultarPedidos($cliente);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];
        $pedidos = array_is_list($dados) ? $dados : ($dados['pedidos'] ?? $dados['parcelamentos'] ?? []);

        return response()->json(['success' => true, 'pedidos' => $pedidos]);
    }

    /**
     * Detalha um parcelamento específico (OBTERPARC164): consolidação
     * original e demonstrativo de pagamentos mês a mês.
     */
    public function consultarParcelamentoDetalhe(Request $request, ParcelamentoService $parcelamento): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'numero_parcelamento' => 'required|string',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resposta = $parcelamento->consultarParcelamento($cliente, $validated['numero_parcelamento']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];

        return response()->json(['success' => true, 'parcelamento' => $dados]);
    }

    /**
     * Lista as parcelas do parcelamento ativo do cliente ainda pendentes de
     * emissão/pagamento (PARCELASPARAGERAR162) — a fila de gargalo em si.
     */
    public function consultarParcelasPendentes(Request $request, ParcelamentoService $parcelamento): JsonResponse
    {
        $validated = $request->validate(['cliente_id' => 'required|exists:clientes,id']);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resposta = $parcelamento->consultarParcelasParaImpressao($cliente);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];

        return response()->json(['success' => true, 'parcelas' => $dados['listaParcelas'] ?? []]);
    }

    /**
     * Emite o DAS de uma parcela pendente (GERARDAS161) — o campo do PDF vem
     * como "docArrecadacaoPdfB64" (ver buscarCampoPdf), sem "nomeArquivo".
     */
    public function emitirDasParcelamento(Request $request, ParcelamentoService $parcelamento): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'parcela' => 'required|digits:6',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resposta = $parcelamento->emitirDas($cliente, $validated['parcela']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];
        $nomeArquivo = "DAS-PARCELAMENTO-{$validated['parcela']}-{$cliente->id}.pdf";
        $arquivo = $this->extrairPdfAvulso($dados, $nomeArquivo);

        return response()->json(['success' => true, 'arquivo' => $arquivo]);
    }

    /**
     * Lista o histórico de pedidos de parcelamento MEI do cliente no programa
     * escolhido (PARCMEI/PARCMEI-ESP/PERTMEI/RELPMEI) — mesma lógica do
     * consultarParcelamentosPedidos, só que parametrizada por programa.
     */
    public function consultarParcelamentosMeiPedidos(Request $request, ParcelamentoMeiService $parcelamento): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'programa' => ['required', 'string', Rule::in(ParcelamentoMeiService::programasValidos())],
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        if (empty($cliente->cpfcnpj)) {
            return response()->json(['error' => "Cliente {$cliente->nome} não tem CNPJ cadastrado."], 422);
        }

        try {
            $resposta = $parcelamento->consultarPedidos($cliente, $validated['programa']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];
        $pedidos = array_is_list($dados) ? $dados : ($dados['pedidos'] ?? $dados['parcelamentos'] ?? []);

        return response()->json(['success' => true, 'pedidos' => $pedidos]);
    }

    /**
     * Detalha um parcelamento MEI específico do programa escolhido.
     */
    public function consultarParcelamentoMeiDetalhe(Request $request, ParcelamentoMeiService $parcelamento): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'programa' => ['required', 'string', Rule::in(ParcelamentoMeiService::programasValidos())],
            'numero_parcelamento' => 'required|string',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resposta = $parcelamento->consultarParcelamento($cliente, $validated['programa'], $validated['numero_parcelamento']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];

        return response()->json(['success' => true, 'parcelamento' => $dados]);
    }

    /**
     * Lista as parcelas MEI ainda pendentes de emissão/pagamento do programa
     * escolhido.
     */
    public function consultarParcelasMeiPendentes(Request $request, ParcelamentoMeiService $parcelamento): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'programa' => ['required', 'string', Rule::in(ParcelamentoMeiService::programasValidos())],
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resposta = $parcelamento->consultarParcelasParaImpressao($cliente, $validated['programa']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];

        return response()->json(['success' => true, 'parcelas' => $dados['listaParcelas'] ?? []]);
    }

    /**
     * Emite o DAS de uma parcela MEI pendente do programa escolhido.
     */
    public function emitirDasParcelamentoMei(Request $request, ParcelamentoMeiService $parcelamento): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'programa' => ['required', 'string', Rule::in(ParcelamentoMeiService::programasValidos())],
            'parcela' => 'required|digits:6',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resposta = $parcelamento->emitirDas($cliente, $validated['programa'], $validated['parcela']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];
        $nomeArquivo = "DAS-{$validated['programa']}-{$validated['parcela']}-{$cliente->id}.pdf";
        $arquivo = $this->extrairPdfAvulso($dados, $nomeArquivo);

        return response()->json(['success' => true, 'arquivo' => $arquivo]);
    }

    /**
     * Salva um PDF base64 avulso (sem "nomeArquivo" acompanhando, diferente do
     * padrão do CONSDECREC15/CONSEXTRATO16) e devolve o link de download.
     */
    /**
     * Procura um campo "pdf" em qualquer profundidade da estrutura (o
     * GERARDAS12 pode devolver o PDF sozinho, sem "nomeArquivo" ao lado, e
     * não confirmamos ainda se vem no nível raiz ou dentro de uma lista) e,
     * se achar, salva e devolve o link de download.
     */
    private function extrairPdfAvulso(array $dados, string $nomeArquivo): ?array
    {
        $pdfBase64 = $this->buscarCampoPdf($dados);

        if (! $pdfBase64) {
            return null;
        }

        return $this->salvarPdfBase64($pdfBase64, $nomeArquivo);
    }

    /**
     * Decodifica e salva um PDF base64 já identificado (sem precisar procurar
     * o campo recursivamente) — usado quando a resposta tem múltiplos PDFs em
     * campos com nomes fixos conhecidos (ex.: DEFIS: "reciboPdf"/"declaracaoPdf").
     */
    private function salvarPdfBase64(string $pdfBase64, string $nomeArquivo): array
    {
        $dir = 'integracontador/testes';
        $destPath = storage_path("app/{$dir}");

        if (! is_dir($destPath)) {
            mkdir($destPath, 0755, true);
        }

        $nomeSeguro = time().'-'.preg_replace('/[^A-Za-z0-9._-]/', '_', $nomeArquivo);
        file_put_contents("{$destPath}/{$nomeSeguro}", base64_decode($pdfBase64));

        return [
            'nomeArquivo' => $nomeArquivo,
            'url' => route('simples-nacional.configuracao.download-teste', ['arquivo' => $nomeSeguro]),
        ];
    }

    private function buscarCampoPdf(array $dados): ?string
    {
        if (! empty($dados['pdf']) && is_string($dados['pdf'])) {
            return $dados['pdf'];
        }

        // Campo usado pelo Integra-Parcelamento (ex.: GERARDAS161 do PARCSN),
        // nome diferente do "pdf" do PGDASD.
        if (! empty($dados['docArrecadacaoPdfB64']) && is_string($dados['docArrecadacaoPdfB64'])) {
            return $dados['docArrecadacaoPdfB64'];
        }

        foreach ($dados as $valor) {
            if (is_array($valor)) {
                $encontrado = $this->buscarCampoPdf($valor);

                if ($encontrado) {
                    return $encontrado;
                }
            }
        }

        return null;
    }

    /**
     * Normaliza a estrutura bruta de "periodos"/"operacoes" da CONSDECLARACAO13
     * (confirmada em produção) em uma lista simples: um item por período, com
     * o número da declaração original e o status de cada DAS gerado.
     */
    private function normalizarPeriodos(array $periodos): array
    {
        $resultado = [];

        foreach ($periodos as $periodo) {
            $numeroDeclaracao = null;
            $dataTransmissao = null;
            $dasList = [];

            foreach ($periodo['operacoes'] ?? [] as $operacao) {
                if (! empty($operacao['indiceDeclaracao']['numeroDeclaracao'])) {
                    $numeroDeclaracao = $operacao['indiceDeclaracao']['numeroDeclaracao'];
                    $dataTransmissao = $operacao['indiceDeclaracao']['dataHoraTransmissao'] ?? null;
                }

                if (! empty($operacao['indiceDas'])) {
                    $dasList[] = $operacao['indiceDas'];
                }
            }

            $resultado[] = [
                'periodoApuracao' => $periodo['periodoApuracao'] ?? null,
                'numeroDeclaracao' => $numeroDeclaracao,
                'dataTransmissao' => $dataTransmissao,
                'das' => $dasList,
            ];
        }

        usort($resultado, fn ($a, $b) => ($b['periodoApuracao'] ?? 0) <=> ($a['periodoApuracao'] ?? 0));

        return $resultado;
    }

    /**
     * Dados fiscais do cliente (CNAE de referência, id_atividade, anexo) —
     * cadastro manual, obrigatório antes da primeira transmissão real desse cliente.
     */
    public function getDadosFiscais(Request $request): JsonResponse
    {
        $validated = $request->validate(['cliente_id' => 'required|exists:clientes,id']);

        $dados = ClienteDadosSimples::where('cliente_id', $validated['cliente_id'])->first();

        return response()->json([
            'cnae_principal' => $dados->cnae_principal ?? null,
            'id_atividade' => $dados->id_atividade ?? null,
            'anexo_simples' => $dados->anexo_simples ?? null,
        ]);
    }

    /**
     * Sugestão de CNAE via consulta pública gratuita (BrasilAPI/Receita Federal),
     * não a API paga Integra Contador — só preenche o campo de referência,
     * nunca o idAtividade (esse continua manual, sem fonte automática confirmada).
     */
    public function sugerirCnae(Request $request): JsonResponse
    {
        $validated = $request->validate(['cliente_id' => 'required|exists:clientes,id']);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        if (empty($cliente->cpfcnpj)) {
            return response()->json(['cnae' => null]);
        }

        $cnae = $this->cnpjPublico->buscarCnae($cliente->cpfcnpj);

        return response()->json(['cnae' => $cnae]);
    }

    public function salvarDadosFiscais(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'cnae_principal' => 'nullable|string|max:255',
            'anexo_simples' => 'nullable|string|max:10',
        ]);

        ClienteDadosSimples::updateOrCreate(
            ['cliente_id' => $validated['cliente_id']],
            [
                'cnae_principal' => $validated['cnae_principal'] ?? null,
                'anexo_simples' => $validated['anexo_simples'] ?? null,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Dados fiscais salvos com sucesso.']);
    }

    /**
     * Receita bruta do mês corrente lançada manualmente — a SERPRO não devolve
     * esse valor, só histórico já transmitido.
     */
    public function getReceitaMensal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'periodo_apuracao' => 'required|digits:6',
        ]);

        $receita = SimplesReceitaMensal::where('cliente_id', $validated['cliente_id'])
            ->where('periodo_apuracao', $validated['periodo_apuracao'])
            ->first();

        return response()->json([
            'receita_bruta_competencia' => $receita->receita_bruta_competencia ?? null,
            'receita_bruta_caixa' => $receita->receita_bruta_caixa ?? null,
            'regime_apuracao' => $receita->regime_apuracao ?? 'competencia',
            'folha_salario' => $receita->folha_salario ?? null,
        ]);
    }

    public function salvarReceitaMensal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'periodo_apuracao' => 'required|digits:6',
            'receita_bruta_competencia' => 'required|numeric|min:0',
            'receita_bruta_caixa' => 'nullable|numeric|min:0',
            'regime_apuracao' => 'required|in:competencia,caixa',
            'folha_salario' => 'nullable|numeric|min:0',
        ]);

        SimplesReceitaMensal::updateOrCreate(
            ['cliente_id' => $validated['cliente_id'], 'periodo_apuracao' => $validated['periodo_apuracao']],
            [
                'receita_bruta_competencia' => $validated['receita_bruta_competencia'],
                'receita_bruta_caixa' => $validated['receita_bruta_caixa'] ?? null,
                'regime_apuracao' => $validated['regime_apuracao'],
                'folha_salario' => $validated['folha_salario'] ?? null,
            ]
        );

        return response()->json(['success' => true, 'message' => 'Receita do mês salva com sucesso.']);
    }

    /**
     * Receitas por atividade já lançadas para o período — réplica da etapa
     * "Atividades"/"Receitas" do e-CAC (um cliente pode ter mais de uma
     * atividade cadastrada no Simples Nacional ao mesmo tempo).
     */
    public function getReceitasAtividades(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'periodo_apuracao' => 'required|digits:6',
        ]);

        $atividades = SimplesReceitaAtividade::with('tributos')
            ->where('cliente_id', $validated['cliente_id'])
            ->where('periodo_apuracao', $validated['periodo_apuracao'])
            ->get()
            ->map(fn (SimplesReceitaAtividade $a) => [
                'id_atividade' => $a->id_atividade,
                'valor' => $a->valor,
                'tributos' => $a->tributos->map(fn ($t) => [
                    'cod_tributo' => $t->cod_tributo,
                    'tipo_ajuste' => $t->tipo_ajuste,
                    'identificador_isencao' => $t->identificador_isencao,
                    'percentual_reducao' => $t->percentual_reducao,
                    'motivo_suspensao' => $t->motivo_suspensao,
                    'valor' => $t->valor,
                ]),
            ]);

        return response()->json(['atividades' => $atividades]);
    }

    /**
     * Salva (substituindo por completo) as atividades selecionadas e o
     * tratamento tributário por tributo de cada uma, para um cliente/período.
     */
    public function salvarReceitasAtividades(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'periodo_apuracao' => 'required|digits:6',
            'atividades' => 'required|array|min:1',
            'atividades.*.id_atividade' => 'required|integer|min:1|max:43',
            'atividades.*.valor' => 'required|numeric|min:0',
            'atividades.*.tributos' => 'array',
            'atividades.*.tributos.*.cod_tributo' => 'required|integer',
            'atividades.*.tributos.*.tipo_ajuste' => 'required|in:normal,isencao,reducao,imunidade,lancamento_oficio,substituicao_tributaria,tributacao_monofasica,antecipacao_encerramento,retencao_iss,exigibilidade_suspensa',
            'atividades.*.tributos.*.identificador_isencao' => 'nullable|integer|in:1,2',
            'atividades.*.tributos.*.percentual_reducao' => 'nullable|numeric|min:0|max:100',
            'atividades.*.tributos.*.motivo_suspensao' => 'nullable|integer|min:1|max:6',
            'atividades.*.tributos.*.valor' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($validated) {
            SimplesReceitaAtividade::where('cliente_id', $validated['cliente_id'])
                ->where('periodo_apuracao', $validated['periodo_apuracao'])
                ->delete(); // cascata apaga também os tributos

            foreach ($validated['atividades'] as $atividade) {
                $registro = SimplesReceitaAtividade::create([
                    'cliente_id' => $validated['cliente_id'],
                    'periodo_apuracao' => $validated['periodo_apuracao'],
                    'id_atividade' => $atividade['id_atividade'],
                    'valor' => $atividade['valor'],
                ]);

                foreach ($atividade['tributos'] ?? [] as $tributo) {
                    if (($tributo['tipo_ajuste'] ?? 'normal') === 'normal') {
                        continue; // tributação normal não precisa de registro
                    }

                    $registro->tributos()->create([
                        'cod_tributo' => $tributo['cod_tributo'],
                        'tipo_ajuste' => $tributo['tipo_ajuste'],
                        'identificador_isencao' => $tributo['identificador_isencao'] ?? null,
                        'percentual_reducao' => $tributo['percentual_reducao'] ?? null,
                        'motivo_suspensao' => $tributo['motivo_suspensao'] ?? null,
                        'valor' => $tributo['valor'] ?? 0,
                    ]);
                }
            }
        });

        return response()->json(['success' => true, 'message' => 'Receitas por atividade salvas com sucesso.']);
    }

    /**
     * Lê o relatório .txt exportado pelo sistema Domínio e devolve uma prévia
     * (não salva nada ainda) — cliente identificado por CNPJ, atividade
     * sugerida por similaridade de texto, valores lidos. O usuário confirma
     * (e pode corrigir) antes de gravar via confirmarImportacaoDominio().
     *
     * Foge do padrão de import do projeto (que salva direto) de propósito:
     * aqui envolve dado fiscal + match automático de atividade que pode errar
     * (ver PgdasdAtividades/DominioImportParser), então uma prévia antes de
     * persistir é mais seguro.
     *
     * LIMITAÇÃO CONHECIDA: só usa `atividades[0]` de cada estabelecimento — o
     * parser já suporta múltiplas atividades por estabelecimento, mas esta
     * tela/preview ainda não exibe nem salva mais de uma. Cliente com duas
     * atividades no mesmo CNPJ (ex.: comércio + serviço) precisa lançar a
     * segunda manualmente em "Atividades e receitas" depois de importar.
     */
    public function previaImportacaoDominio(Request $request): JsonResponse
    {
        $request->validate([
            'arquivo' => 'required|file|mimes:txt|max:5120',
        ]);

        $conteudo = file_get_contents($request->file('arquivo')->getRealPath());
        $resultado = $this->dominioParser->parse($conteudo);

        if (! $resultado['periodo_apuracao']) {
            return response()->json(['error' => 'Não foi possível identificar o período de apuração no arquivo.'], 422);
        }

        $catalogo = PgdasdAtividades::catalogo();

        // cpfcnpj é salvo com máscara ("00.000.000/0000-00"), mas o relatório do
        // Domínio só traz dígitos — comparar direto nunca bate. Carrega todos os
        // clientes com CNPJ uma única vez e indexa pelos dígitos para casar os dois lados.
        $clientesPorCnpj = Cliente::whereNotNull('cpfcnpj')
            ->get(['id', 'nome', 'cpfcnpj'])
            ->keyBy(fn (Cliente $c) => preg_replace('/\D/', '', (string) $c->cpfcnpj));

        $estabelecimentos = collect($resultado['estabelecimentos'])->map(function (array $e) use ($catalogo, $clientesPorCnpj) {
            $cliente = $clientesPorCnpj->get($e['cnpj']);

            $atividades = collect($e['atividades'])->map(function (array $a) use ($catalogo) {
                $idAtividadeSugerido = $a['id_atividade_sugerido'] ?? null;

                // O ISS de algumas atividades (10-27) já tem o tratamento
                // (sem/com retenção) definido pela própria descrição da
                // atividade — aplicar OUTRA qualificação tributária no ISS
                // por cima é conflitante pra API (confirmado em produção
                // 2026-08-04, ver PgdasdAtividades::ATIVIDADES_ISS_TRATAMENTO_
                // PROPRIO). Nas atividades "com retenção/substituição"
                // (ATIVIDADES_ISS_COM_RETENCAO), "Não incidência" do relatório
                // provavelmente significa "retido pelo tomador" — sugerimos
                // "retencao_iss" nesse caso (hipótese, ver docblock da
                // constante). Nas demais (sem retenção), não tem qualificação
                // válida pro ISS — força "normal" e sinaliza pra revisão manual.
                $issTemTratamentoProprio = $idAtividadeSugerido !== null
                    && in_array($idAtividadeSugerido, PgdasdAtividades::ATIVIDADES_ISS_TRATAMENTO_PROPRIO, true);
                $issComRetencao = $idAtividadeSugerido !== null
                    && in_array($idAtividadeSugerido, PgdasdAtividades::ATIVIDADES_ISS_COM_RETENCAO, true);

                $tributosProcessados = collect($a['tributos'] ?? [])->map(function ($t) use ($issTemTratamentoProprio, $issComRetencao) {
                    $conflitanteComIss = $issTemTratamentoProprio
                        && $t['cod_tributo'] === PgdasdAtividades::TRIBUTO_ISS
                        && $t['tipo_ajuste_sugerido'] !== 'normal';

                    if ($conflitanteComIss && $issComRetencao) {
                        return [
                            'cod_tributo' => $t['cod_tributo'],
                            'situacao' => $t['situacao'],
                            'tipo_ajuste_sugerido' => 'retencao_iss',
                            'precisa_revisao' => true, // hipótese, vale conferir
                        ];
                    }

                    return [
                        'cod_tributo' => $t['cod_tributo'],
                        'situacao' => $t['situacao'],
                        'tipo_ajuste_sugerido' => $conflitanteComIss ? 'normal' : $t['tipo_ajuste_sugerido'],
                        'precisa_revisao' => $conflitanteComIss,
                    ];
                });

                // "tipo_ajuste_sugerido" já traduz a maioria das situações do
                // Domínio (Tributado/Não incidência/Subst. Trib./Trib. Monof.)
                // pro enum usado no cadastro manual — sinalizamos como
                // "precisa revisão manual" quando a situação NÃO é "Tributado"
                // mas caiu no fallback "normal" (parser não conseguiu mapear),
                // ou quando o mapeamento foi descartado por conflitar com o
                // tratamento de ISS já embutido na atividade.
                $tributosNaoReconhecidos = $tributosProcessados
                    ->filter(fn ($t) => $t['precisa_revisao'] || ($t['situacao'] !== 'Tributado' && $t['tipo_ajuste_sugerido'] === 'normal'))
                    ->values();

                $tributos = $tributosProcessados
                    ->map(fn ($t) => [
                        'cod_tributo' => $t['cod_tributo'],
                        'tipo_ajuste' => $t['tipo_ajuste_sugerido'],
                        'situacao_relatorio' => $t['situacao'],
                    ])
                    ->values();

                return [
                    'id_atividade_sugerido' => $a['id_atividade_sugerido'] ?? null,
                    'atividade_descricao_sugerida' => isset($catalogo[$a['id_atividade_sugerido'] ?? null])
                        ? $catalogo[$a['id_atividade_sugerido']]['descricao']
                        : null,
                    'confianca_match' => $a['confianca_match'] ?? 0,
                    'tabela_texto' => $a['tabela_texto'] ?? null,
                    'receita_tributada_total' => $a['receita_tributada_total'] ?? null,
                    'tributos' => $tributos,
                    'tributos_nao_reconhecidos' => $tributosNaoReconhecidos,
                ];
            })->values();

            return [
                'cnpj' => $e['cnpj'],
                'nome_relatorio' => $e['nome'],
                'cliente_id' => $cliente?->id,
                'cliente_nome' => $cliente?->nome,
                'rbt12' => $e['rbt12'],
                'rba_atual' => $e['rba_atual'],
                'rba_anterior' => $e['rba_anterior'],
                'rpa_competencia' => $e['rpa_competencia'],
                'rpa_caixa' => $e['rpa_caixa'],
                'anexo_sugerido' => $e['anexo_sugerido'] ?? null,
                'atividades' => $atividades,
            ];
        });

        return response()->json([
            'success' => true,
            'periodo_apuracao' => $resultado['periodo_apuracao'],
            'estabelecimentos' => $estabelecimentos,
        ]);
    }

    /**
     * Salva os dados já revisados/corrigidos pelo usuário na prévia. Não
     * recebe o arquivo de novo — recebe a estrutura que a tela já mostrou,
     * possivelmente editada.
     */
    public function confirmarImportacaoDominio(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'periodo_apuracao' => 'required|digits:6',
            'estabelecimentos' => 'required|array|min:1',
            'estabelecimentos.*.cliente_id' => 'required|exists:clientes,id',
            'estabelecimentos.*.rpa_competencia' => 'nullable|numeric|min:0',
            'estabelecimentos.*.rpa_caixa' => 'nullable|numeric|min:0',
            'estabelecimentos.*.regime_apuracao' => 'required|in:competencia,caixa',
            'estabelecimentos.*.anexo_simples' => 'nullable|string|max:10',
            // min:0 (não min:1): um estabelecimento "sem movimento" (receita
            // zerada) não tem nenhum bloco de Anexo/Atividade no relatório do
            // Domínio — é uma declaração legítima, não um erro de parseamento.
            'estabelecimentos.*.atividades' => 'present|array',
            'estabelecimentos.*.atividades.*.id_atividade' => 'required|integer|min:1|max:43',
            'estabelecimentos.*.atividades.*.receita_tributada_total' => 'required|numeric|min:0',
            'estabelecimentos.*.atividades.*.tributos' => 'array',
            'estabelecimentos.*.atividades.*.tributos.*.cod_tributo' => 'required|integer',
            'estabelecimentos.*.atividades.*.tributos.*.tipo_ajuste' => 'required|in:normal,isencao,reducao,imunidade,lancamento_oficio,substituicao_tributaria,tributacao_monofasica,antecipacao_encerramento,retencao_iss,exigibilidade_suspensa',
            'estabelecimentos.*.atividades.*.tributos.*.identificador_isencao' => 'nullable|integer|in:1,2',
            'estabelecimentos.*.atividades.*.tributos.*.percentual_reducao' => 'nullable|numeric|min:0|max:100',
            'estabelecimentos.*.atividades.*.tributos.*.motivo_suspensao' => 'nullable|integer|min:1|max:6',
        ]);

        $periodo = $validated['periodo_apuracao'];
        $salvos = 0;

        DB::transaction(function () use ($validated, $periodo, &$salvos) {
            foreach ($validated['estabelecimentos'] as $e) {
                $regime = $e['regime_apuracao'];

                SimplesReceitaMensal::updateOrCreate(
                    ['cliente_id' => $e['cliente_id'], 'periodo_apuracao' => $periodo],
                    [
                        'receita_bruta_competencia' => $e['rpa_competencia'] ?? 0,
                        'receita_bruta_caixa' => $e['rpa_caixa'] ?? null,
                        'regime_apuracao' => $regime,
                    ]
                );

                if (! empty($e['anexo_simples'])) {
                    ClienteDadosSimples::updateOrCreate(
                        ['cliente_id' => $e['cliente_id']],
                        ['anexo_simples' => $e['anexo_simples']]
                    );
                }

                // Substitui por completo as atividades desse cliente/período — mesmo
                // padrão "apaga tudo e recria" do salvarReceitasAtividades manual,
                // pra reimportar o mesmo período não deixar atividade órfã de uma
                // importação anterior.
                SimplesReceitaAtividade::where('cliente_id', $e['cliente_id'])
                    ->where('periodo_apuracao', $periodo)
                    ->delete();

                foreach ($e['atividades'] as $atividade) {
                    $registro = SimplesReceitaAtividade::create([
                        'cliente_id' => $e['cliente_id'],
                        'periodo_apuracao' => $periodo,
                        'id_atividade' => $atividade['id_atividade'],
                        'valor' => $atividade['receita_tributada_total'],
                    ]);

                    foreach ($atividade['tributos'] ?? [] as $tributo) {
                        if (($tributo['tipo_ajuste'] ?? 'normal') === 'normal') {
                            continue; // tributação normal não precisa de registro
                        }

                        $registro->tributos()->create([
                            'cod_tributo' => $tributo['cod_tributo'],
                            'tipo_ajuste' => $tributo['tipo_ajuste'],
                            'identificador_isencao' => $tributo['identificador_isencao'] ?? null,
                            'percentual_reducao' => $tributo['percentual_reducao'] ?? null,
                            'motivo_suspensao' => $tributo['motivo_suspensao'] ?? null,
                            'valor' => $atividade['receita_tributada_total'],
                        ]);
                    }
                }

                $salvos++;
            }
        });

        return response()->json([
            'success' => true,
            'message' => "{$salvos} estabelecimento(s) importado(s) com sucesso para o período {$periodo}.",
        ]);
    }

    /**
     * Transmite a declaração de verdade — cria uma declaração fiscal REAL
     * perante a Receita Federal. Requer receita do mês e dados fiscais já
     * cadastrados (ver getReceitaMensal/getDadosFiscais).
     */
    public function transmitir(Request $request, PgdasdService $pgdasd): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'periodo_apuracao' => 'required|digits:6',
            'confirmar_receita_zerada' => 'nullable|boolean',
            'confirmar_retificadora' => 'nullable|boolean',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $processamento = $pgdasd->transmitirDeclaracaoDoCliente(
                $cliente,
                $validated['periodo_apuracao'],
                (bool) ($validated['confirmar_receita_zerada'] ?? false),
                (bool) ($validated['confirmar_retificadora'] ?? false),
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        if ($processamento->status === 'erro') {
            return response()->json(['error' => $processamento->mensagem_erro], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Declaração transmitida — status: {$processamento->status}, recibo: ".($processamento->numero_recibo ?? '—'),
            'numero_recibo' => $processamento->numero_recibo,
        ]);
    }

    public function getConfiguracao(): JsonResponse
    {
        $config = IntegraContadorConfiguracao::first();

        if (! $config) {
            return response()->json(['configurado' => false]);
        }

        return response()->json([
            'configurado' => true,
            'arquivo_ok' => file_exists(storage_path('app/'.$config->arquivo_certificado)),
            'cnpj_contratante' => $config->cnpj_contratante,
            'ambiente' => $config->ambiente,
        ]);
    }

    public function salvarConfiguracao(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'certificado' => 'nullable|file|max:10240', // sem mimes: .pfx é detectado como octet-stream
            'senha' => 'nullable|string|min:1',
            'cnpj_contratante' => 'required|string|min:14|max:18',
            'consumer_key' => 'required|string',
            'consumer_secret' => 'required|string',
            'ambiente' => 'required|in:trial,producao',
        ]);

        $config = IntegraContadorConfiguracao::first() ?? new IntegraContadorConfiguracao;
        $file = $request->file('certificado');

        if ($file) {
            if (empty($validated['senha'])) {
                return response()->json(['error' => 'Informe a senha do certificado ao enviar um novo arquivo.'], 422);
            }

            try {
                $this->nfse->validarCertificado($file->getRealPath(), $validated['senha']);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            $dir = 'integracontador/certificados';
            $destPath = storage_path("app/{$dir}");

            if (! is_dir($destPath)) {
                mkdir($destPath, 0755, true);
            }

            $destFile = "{$destPath}/certificado.pfx";

            if (! copy($file->getRealPath(), $destFile)) {
                return response()->json(['error' => 'Falha ao salvar o arquivo do certificado no servidor.'], 500);
            }

            $config->arquivo_certificado = "{$dir}/certificado.pfx";
            $config->senha_certificado = $validated['senha'];
        } elseif (! $config->exists) {
            return response()->json(['error' => 'Envie o certificado (.pfx/.p12) e a senha na primeira configuração.'], 422);
        }

        $config->fill([
            'cnpj_contratante' => preg_replace('/\D/', '', $validated['cnpj_contratante']),
            'consumer_key' => $validated['consumer_key'],
            'consumer_secret' => $validated['consumer_secret'],
            'ambiente' => $validated['ambiente'],
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Configuração da API Integra Contador salva com sucesso!',
        ]);
    }

    public function testarConexao(IntegraContadorAuthService $auth): JsonResponse
    {
        $auth->invalidarTokens();

        try {
            $auth->obterTokens();
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Autenticação na API Integra Contador funcionou — token obtido com sucesso.',
        ]);
    }

    /**
     * Testa a chamada real do PGDASD (CONSDECLARACAO13), sem persistir nada — só
     * para validar se o endpoint/idServico usado no client está correto.
     *
     * No ambiente trial usa o CNPJ de demonstração fixo (00000000000000, ano
     * 2018), que só existe nessa sandbox. No ambiente produção não há CNPJ de
     * demonstração — é necessário informar um CNPJ real (ex.: o do próprio
     * escritório, que deveria ter procuração automática para consultar a si mesmo).
     */
    public function testarConsultaDemo(Request $request, PgdasdService $pgdasd): JsonResponse
    {
        $config = IntegraContadorConfiguracao::first();

        if (! $config) {
            return response()->json(['error' => 'Configuração da API Integra Contador não encontrada.'], 422);
        }

        $cnpjInformado = preg_replace('/\D/', '', (string) $request->get('cnpj'));

        if ($config->ambiente === 'producao') {
            if (! $cnpjInformado) {
                return response()->json(['error' => 'No ambiente de produção não existe CNPJ de demonstração — informe um CNPJ real para testar (ex.: o do próprio escritório).'], 422);
            }

            $cliente = new Cliente(['cpfcnpj' => $cnpjInformado]);
            $anoCalendario = (string) (now()->year - 1);
        } else {
            // "00000000000000"/2018 são os valores fixos do cenário de demonstração oficial do trial PGDASD.
            $cliente = new Cliente(['cpfcnpj' => $cnpjInformado ?: '00000000000000']);
            $anoCalendario = '2018';
        }

        try {
            $resposta = $pgdasd->consultarDeclaracao($cliente, $anoCalendario);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Consulta PGDASD (CONSDECLARACAO13) respondeu com sucesso — CNPJ {$cliente->cpfcnpj}, ano {$anoCalendario}.",
            'resposta' => $resposta,
        ]);
    }

    /**
     * Testa CONSDECREC15 (recibo de uma declaração específica) — usado para
     * descobrir se é aqui que a API expõe os valores de apuração (receita
     * bruta, RBT12 etc.), já que CONSDECLARACAO13 não traz esses campos.
     *
     * A resposta traz PDFs em base64 (recibo/declaração), não campos numéricos
     * diretos — decodifica e salva em storage privado para inspeção visual,
     * em vez de devolver o base64 gigante pro navegador.
     */
    public function testarConsultaRecibo(Request $request, PgdasdService $pgdasd): JsonResponse
    {
        $validated = $request->validate([
            'cnpj' => 'required|string',
            'numero_declaracao' => 'required|string',
        ]);

        $cliente = new Cliente(['cpfcnpj' => preg_replace('/\D/', '', $validated['cnpj'])]);

        try {
            $resposta = $pgdasd->consultarRecibo($cliente, $validated['numero_declaracao']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];
        $arquivos = $this->extrairESalvarPdfs($dados);

        return response()->json([
            'success' => true,
            'message' => "Consulta de recibo (CONSDECREC15) respondeu com sucesso — declaração {$validated['numero_declaracao']}.",
            'dados_sem_pdf' => $this->removerCamposPdf($dados),
            'arquivos' => $arquivos,
        ]);
    }

    /**
     * Lista as declarações DEFIS já transmitidas pelo cliente (CONSDECLARACAO142).
     */
    public function consultarDeclaracoesDefis(Request $request, DefisService $defis): JsonResponse
    {
        $validated = $request->validate(['cliente_id' => 'required|exists:clientes,id']);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        if (empty($cliente->cpfcnpj)) {
            return response()->json(['error' => "Cliente {$cliente->nome} não tem CNPJ cadastrado."], 422);
        }

        try {
            $resposta = $defis->consultarDeclaracoes($cliente);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];
        $declaracoes = array_is_list($dados) ? $dados : ($dados['declaracoes'] ?? []);

        usort($declaracoes, fn ($a, $b) => ($b['anoCalendario'] ?? 0) <=> ($a['anoCalendario'] ?? 0));

        return response()->json(['success' => true, 'declaracoes' => $declaracoes]);
    }

    /**
     * Busca o recibo e a declaração completa (PDFs) de uma DEFIS específica
     * (CONSDECREC144) — campos "reciboPdf"/"declaracaoPdf", sem "nomeArquivo".
     */
    public function buscarReciboDefis(Request $request, DefisService $defis): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'id_defis' => 'required|string',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resposta = $defis->consultarDeclaracaoRecibo($cliente, $validated['id_defis']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];
        $arquivos = [];

        if (! empty($dados['reciboPdf'])) {
            $arquivos[] = $this->salvarPdfBase64($dados['reciboPdf'], "DEFIS-{$validated['id_defis']}-recibo.pdf");
        }

        if (! empty($dados['declaracaoPdf'])) {
            $arquivos[] = $this->salvarPdfBase64($dados['declaracaoPdf'], "DEFIS-{$validated['id_defis']}-declaracao.pdf");
        }

        return response()->json(['success' => true, 'arquivos' => $arquivos]);
    }

    /**
     * Busca o rascunho de DEFIS do cliente/ano (ou vazio, se ainda não existe).
     */
    public function getDefisDados(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'ano_calendario' => 'required|digits:4',
        ]);

        $declaracao = DefisDeclaracao::with('socios')
            ->where('cliente_id', $validated['cliente_id'])
            ->where('ano_calendario', $validated['ano_calendario'])
            ->first();

        if (! $declaracao) {
            return response()->json(['declaracao' => null]);
        }

        return response()->json([
            'declaracao' => $declaracao,
            'socios' => $declaracao->socios,
        ]);
    }

    /**
     * Salva (substituindo por completo) o rascunho de DEFIS do cliente/ano —
     * cabeçalho + lista de sócios. Não transmite nada, só persiste localmente.
     */
    public function salvarDefisDados(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'ano_calendario' => 'required|digits:4',
            'inatividade' => 'nullable|integer|in:0,1,2',
            'ganho_capital' => 'required|numeric|min:0',
            'qtd_empregado_inicial' => 'required|integer|min:0',
            'qtd_empregado_final' => 'required|integer|min:0',
            'lucro_contabil' => 'nullable|numeric|min:0',
            'receita_exportacao_direta' => 'required|numeric|min:0',
            'participacao_cotas_tesouraria' => 'nullable|numeric|min:0',
            'ganho_renda_variavel' => 'required|numeric|min:0',
            'estoque_inicial' => 'required|numeric|min:0',
            'estoque_final' => 'required|numeric|min:0',
            'saldo_caixa_inicial' => 'required|numeric|min:0',
            'saldo_caixa_final' => 'required|numeric|min:0',
            'aquisicoes_mercado_interno' => 'required|numeric|min:0',
            'importacoes' => 'required|numeric|min:0',
            'total_entradas_por_transferencia' => 'required|numeric|min:0',
            'total_saidas_por_transferencia' => 'required|numeric|min:0',
            'total_devolucoes_vendas' => 'required|numeric|min:0',
            'total_entradas' => 'required|numeric|min:0',
            'total_devolucoes_compras' => 'required|numeric|min:0',
            'total_despesas' => 'required|numeric|min:0',
            'iss_retidos_fonte' => 'nullable|numeric|min:0',
            'prestacoes_servico_comunicacao' => 'nullable|numeric|min:0',
            'prestacoes_servico_transporte' => 'nullable|numeric|min:0',
            'socios' => 'required|array|min:1',
            'socios.*.cpf' => 'required|digits:11',
            'socios.*.rendimentos_isentos' => 'required|numeric|min:0',
            'socios.*.rendimentos_tributaveis' => 'required|numeric|min:0',
            'socios.*.participacao_capital_social' => 'required|numeric|min:0|max:100',
            'socios.*.ir_retido_fonte' => 'required|numeric|min:0',
        ]);

        $declaracao = DB::transaction(function () use ($validated) {
            $cabecalho = collect($validated)->except(['socios'])->all();

            $declaracao = DefisDeclaracao::updateOrCreate(
                ['cliente_id' => $validated['cliente_id'], 'ano_calendario' => $validated['ano_calendario']],
                $cabecalho
            );

            $declaracao->socios()->delete();

            foreach ($validated['socios'] as $socio) {
                $declaracao->socios()->create($socio);
            }

            return $declaracao;
        });

        return response()->json([
            'success' => true,
            'message' => 'Rascunho da DEFIS salvo com sucesso.',
            'declaracao_id' => $declaracao->id,
        ]);
    }

    /**
     * Transmite a DEFIS de verdade a partir do rascunho já salvo — cria uma
     * declaração fiscal REAL perante a Receita Federal, irreversível.
     */
    public function transmitirDefis(Request $request, DefisService $defis): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'ano_calendario' => 'required|digits:4',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        $declaracao = DefisDeclaracao::with('socios')
            ->where('cliente_id', $validated['cliente_id'])
            ->where('ano_calendario', $validated['ano_calendario'])
            ->first();

        if (! $declaracao) {
            return response()->json(['error' => 'Salve o rascunho da DEFIS antes de transmitir.'], 422);
        }

        try {
            $declaracao = $defis->transmitirDefisDoCliente($cliente, $declaracao);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        if ($declaracao->status === 'erro') {
            return response()->json(['error' => $declaracao->mensagem_erro], 422);
        }

        // Reaproveita a consulta já confirmada (CONSDECREC144) pra pegar os PDFs,
        // em vez de confiar num parsing novo/não confirmado direto da resposta
        // da própria transmissão.
        $arquivos = [];

        if ($declaracao->id_defis) {
            try {
                $respostaRecibo = $defis->consultarDeclaracaoRecibo($cliente, $declaracao->id_defis);
                $dadosRecibo = json_decode($respostaRecibo['dados'] ?? '{}', true) ?? [];

                if (! empty($dadosRecibo['reciboPdf'])) {
                    $arquivos[] = $this->salvarPdfBase64($dadosRecibo['reciboPdf'], "DEFIS-{$declaracao->id_defis}-recibo.pdf");
                }

                if (! empty($dadosRecibo['declaracaoPdf'])) {
                    $arquivos[] = $this->salvarPdfBase64($dadosRecibo['declaracaoPdf'], "DEFIS-{$declaracao->id_defis}-declaracao.pdf");
                }
            } catch (\Throwable $e) {
                // A transmissão já teve sucesso — não falha a resposta só porque
                // buscar o PDF do recibo em seguida deu erro (ex.: SERPRO ainda
                // processando). O usuário pode baixar depois pela tela de consulta.
            }
        }

        return response()->json([
            'success' => true,
            'message' => "DEFIS transmitida com sucesso — idDefis: {$declaracao->id_defis}",
            'id_defis' => $declaracao->id_defis,
            'arquivos' => $arquivos,
        ]);
    }

    /**
     * Lista mensagens da caixa postal do cliente (MSGCONTRIBUINTE61), paginado.
     */
    public function consultarMensagensCaixaPostal(Request $request, CaixaPostalService $caixaPostal): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'ponteiro_pagina' => 'nullable|string',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        if (empty($cliente->cpfcnpj)) {
            return response()->json(['error' => "Cliente {$cliente->nome} não tem CNPJ cadastrado."], 422);
        }

        try {
            $resposta = $caixaPostal->obterListaMensagens($cliente, $validated['ponteiro_pagina'] ?? null);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];
        $conteudo = $dados['conteudo'][0] ?? [];

        return response()->json([
            'success' => true,
            'mensagens' => $conteudo['listaMensagens'] ?? [],
            'indicador_ultima_pagina' => $conteudo['indicadorUltimaPagina'] ?? 'S',
            'ponteiro_proxima_pagina' => $conteudo['ponteiroProximaPagina'] ?? null,
        ]);
    }

    /**
     * Detalhes de uma mensagem específica (MSGDETALHAMENTO62) — corpo em HTML.
     */
    public function consultarDetalheMensagemCaixaPostal(Request $request, CaixaPostalService $caixaPostal): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'isn' => 'required|string',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resposta = $caixaPostal->obterDetalhesMensagem($cliente, $validated['isn']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];
        $conteudo = $dados['conteudo'][0] ?? $dados['conteudo'] ?? [];

        return response()->json(['success' => true, 'mensagem' => $conteudo]);
    }

    /**
     * Indicador rápido de mensagens novas (INNOVAMSG63) — 0/1/2.
     */
    public function consultarIndicadorNovasCaixaPostal(Request $request, CaixaPostalService $caixaPostal): JsonResponse
    {
        $validated = $request->validate(['cliente_id' => 'required|exists:clientes,id']);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resposta = $caixaPostal->obterIndicadorNovasMensagens($cliente);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];
        $conteudo = $dados['conteudo'][0] ?? $dados;

        return response()->json(['success' => true, 'indicador' => $conteudo]);
    }

    /**
     * Consulta o indicador de enquadramento no DTE do cliente
     * (CONSULTASITUACAODTE111) — 0 (DTE), 1 (DTE-SN), 2 (ambos), -1 (não
     * participante), -2 (CNPJ inválido).
     */
    public function consultarSituacaoDte(Request $request, DteService $dte): JsonResponse
    {
        $validated = $request->validate(['cliente_id' => 'required|exists:clientes,id']);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        if (empty($cliente->cpfcnpj)) {
            return response()->json(['error' => "Cliente {$cliente->nome} não tem CNPJ cadastrado."], 422);
        }

        try {
            $resposta = $dte->consultarSituacao($cliente);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];

        return response()->json([
            'success' => true,
            'indicador_enquadramento' => $dados['indicadorEnquadramento'] ?? null,
            'status_enquadramento' => $dados['statusEnquadramento'] ?? null,
        ]);
    }

    /**
     * Valida os campos comuns às 5 operações do DCTFWeb — categoria/ano
     * sempre obrigatórios, mês obrigatório exceto categorias de 13º salário
     * (41/51), os demais condicionais por categoria (regra confirmada na
     * doc oficial: diaPA só na 45, cnoAfericao só na 44,
     * numProcReclamatoria só na 46).
     */
    private function validarDadosDctfWeb(Request $request): array
    {
        return $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'categoria' => ['required', 'integer', Rule::in(array_keys(DctfWebService::CATEGORIAS))],
            'ano_pa' => 'required|digits:4',
            'mes_pa' => ['required_unless:categoria,41,51', 'nullable', 'integer', 'between:1,12'],
            'dia_pa' => ['required_if:categoria,45', 'nullable', 'integer', 'between:1,31'],
            'cno_afericao' => ['required_if:categoria,44', 'nullable', 'string'],
            'num_proc_reclamatoria' => ['required_if:categoria,46', 'nullable', 'string'],
            'numero_recibo_entrega' => 'nullable|string',
        ]);
    }

    /**
     * Gera o DARF (guia de arrecadação) de uma declaração DCTFWeb já
     * entregue (GERARGUIA31).
     */
    public function gerarGuiaDctfWeb(Request $request, DctfWebService $dctfWeb): JsonResponse
    {
        $validated = $this->validarDadosDctfWeb($request);
        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resposta = $dctfWeb->gerarGuia(
                $cliente,
                $validated['categoria'],
                $validated['ano_pa'],
                $validated['mes_pa'] ?? null,
                $validated['dia_pa'] ?? null,
                $validated['cno_afericao'] ?? null,
                $validated['num_proc_reclamatoria'] ?? null,
                $validated['numero_recibo_entrega'] ?? null,
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];
        $arquivo = $this->extrairPdfAvulso($dados, "DCTFWEB-GUIA-{$validated['categoria']}-{$validated['ano_pa']}-{$cliente->id}.pdf");

        return response()->json(['success' => true, 'arquivo' => $arquivo]);
    }

    /**
     * Gera o DARF de uma declaração DCTFWeb ainda em andamento
     * (GERARGUIAANDAMENTO313).
     */
    public function gerarGuiaAndamentoDctfWeb(Request $request, DctfWebService $dctfWeb): JsonResponse
    {
        $validated = $this->validarDadosDctfWeb($request);
        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resposta = $dctfWeb->gerarGuiaAndamento(
                $cliente,
                $validated['categoria'],
                $validated['ano_pa'],
                $validated['mes_pa'] ?? null,
                $validated['dia_pa'] ?? null,
                $validated['cno_afericao'] ?? null,
                $validated['num_proc_reclamatoria'] ?? null,
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];
        $arquivo = $this->extrairPdfAvulso($dados, "DCTFWEB-GUIA-ANDAMENTO-{$validated['categoria']}-{$validated['ano_pa']}-{$cliente->id}.pdf");

        return response()->json(['success' => true, 'arquivo' => $arquivo]);
    }

    /**
     * Consulta o recibo de transmissão de uma declaração DCTFWeb já
     * entregue (CONSRECIBO32).
     */
    public function consultarReciboDctfWeb(Request $request, DctfWebService $dctfWeb): JsonResponse
    {
        $validated = $this->validarDadosDctfWeb($request);
        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resposta = $dctfWeb->consultarRecibo(
                $cliente,
                $validated['categoria'],
                $validated['ano_pa'],
                $validated['mes_pa'] ?? null,
                $validated['dia_pa'] ?? null,
                $validated['cno_afericao'] ?? null,
                $validated['num_proc_reclamatoria'] ?? null,
                $validated['numero_recibo_entrega'] ?? null,
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];
        $arquivo = $this->extrairPdfAvulso($dados, "DCTFWEB-RECIBO-{$validated['categoria']}-{$validated['ano_pa']}-{$cliente->id}.pdf");

        return response()->json(['success' => true, 'arquivo' => $arquivo]);
    }

    /**
     * Consulta o relatório completo da declaração DCTFWeb (CONSDECCOMPLETA33).
     */
    public function consultarDeclaracaoCompletaDctfWeb(Request $request, DctfWebService $dctfWeb): JsonResponse
    {
        $validated = $this->validarDadosDctfWeb($request);
        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resposta = $dctfWeb->consultarDeclaracaoCompleta(
                $cliente,
                $validated['categoria'],
                $validated['ano_pa'],
                $validated['mes_pa'] ?? null,
                $validated['dia_pa'] ?? null,
                $validated['cno_afericao'] ?? null,
                $validated['num_proc_reclamatoria'] ?? null,
                $validated['numero_recibo_entrega'] ?? null,
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];
        $arquivo = $this->extrairPdfAvulso($dados, "DCTFWEB-COMPLETA-{$validated['categoria']}-{$validated['ano_pa']}-{$cliente->id}.pdf");

        return response()->json(['success' => true, 'arquivo' => $arquivo]);
    }

    /**
     * Consulta o XML da declaração DCTFWeb (CONSXMLDECLARACAO38) — devolve
     * o XML decodificado como arquivo .xml pra download (não usamos esse
     * XML pra transmitir, ver docblock do DctfWebService).
     */
    public function consultarXmlDctfWeb(Request $request, DctfWebService $dctfWeb): JsonResponse
    {
        $validated = $this->validarDadosDctfWeb($request);
        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resposta = $dctfWeb->consultarXmlDeclaracao(
                $cliente,
                $validated['categoria'],
                $validated['ano_pa'],
                $validated['mes_pa'] ?? null,
                $validated['dia_pa'] ?? null,
                $validated['cno_afericao'] ?? null,
                $validated['num_proc_reclamatoria'] ?? null,
                $validated['numero_recibo_entrega'] ?? null,
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];
        $xmlBase64 = $dados['XMLStringBase64'] ?? $dados['xmlStringBase64'] ?? null;

        if (! $xmlBase64) {
            return response()->json(['success' => true, 'arquivo' => null]);
        }

        $nomeArquivo = "DCTFWEB-{$validated['categoria']}-{$validated['ano_pa']}-{$cliente->id}.xml";
        $arquivo = $this->salvarPdfBase64($xmlBase64, $nomeArquivo);

        return response()->json(['success' => true, 'arquivo' => $arquivo]);
    }

    /**
     * Solicita o protocolo do relatório SITFIS (SOLICITARPROTOCOLO91) —
     * primeiro passo do fluxo assíncrono.
     */
    public function solicitarSitfis(Request $request, SitfisService $sitfis): JsonResponse
    {
        $validated = $request->validate(['cliente_id' => 'required|exists:clientes,id']);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        if (empty($cliente->cpfcnpj)) {
            return response()->json(['error' => "Cliente {$cliente->nome} não tem CNPJ cadastrado."], 422);
        }

        try {
            $resposta = $sitfis->solicitarProtocolo($cliente);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];

        return response()->json([
            'success' => true,
            'protocolo' => $dados['protocoloRelatorio'] ?? null,
            'tempo_espera_ms' => $dados['tempoEspera'] ?? 4000,
        ]);
    }

    /**
     * Tenta emitir o relatório com o protocolo já obtido (RELATORIOSITFIS92)
     * — pode responder "ainda não pronto", quem chama decide se tenta de
     * novo (ver SitfisService::extrairResultado).
     */
    public function emitirSitfis(Request $request, SitfisService $sitfis): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'protocolo' => 'required|string',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resposta = $sitfis->emitirRelatorio($cliente, $validated['protocolo']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $resultado = $sitfis->extrairResultado($resposta);

        if (!$resultado['pronto']) {
            return response()->json(['success' => true, 'pronto' => false, 'tempo_espera_ms' => $resultado['tempo_espera_ms']]);
        }

        $nomeArquivo = "SITFIS-{$cliente->id}.pdf";
        $arquivo = $this->salvarPdfBase64($resultado['pdf'], $nomeArquivo);

        return response()->json(['success' => true, 'pronto' => true, 'arquivo' => $arquivo]);
    }

    /**
     * Consulta se o cliente tem procuração eletrônica ativa em nome do
     * escritório e para quais sistemas (OBTERPROCURACAO41).
     */
    public function consultarProcuracao(Request $request, ProcuracoesService $procuracoes): JsonResponse
    {
        $validated = $request->validate(['cliente_id' => 'required|exists:clientes,id']);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        if (empty($cliente->cpfcnpj)) {
            return response()->json(['error' => "Cliente {$cliente->nome} não tem CNPJ cadastrado."], 422);
        }

        try {
            $resposta = $procuracoes->obterProcuracao($cliente);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '[]', true) ?? [];

        return response()->json([
            'success' => true,
            'cliente' => ['id' => $cliente->id, 'nome' => $cliente->nome, 'cpfcnpj' => $cliente->cpfcnpj],
            'procuracoes' => $dados,
        ]);
    }

    /**
     * Lista as apurações MIT do cliente por ano (e opcionalmente mês/situação)
     * — LISTAAPURACOES317.
     */
    public function consultarApuracoesMit(Request $request, MitService $mit): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'ano_apuracao' => 'required|digits:4',
            'mes_apuracao' => 'nullable|integer|between:1,12',
            'situacao_apuracao' => 'nullable|integer|between:1,4',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        if (empty($cliente->cpfcnpj)) {
            return response()->json(['error' => "Cliente {$cliente->nome} não tem CNPJ cadastrado."], 422);
        }

        try {
            $resposta = $mit->listarApuracoes(
                $cliente,
                (int) $validated['ano_apuracao'],
                isset($validated['mes_apuracao']) ? (int) $validated['mes_apuracao'] : null,
                isset($validated['situacao_apuracao']) ? (int) $validated['situacao_apuracao'] : null,
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];

        return response()->json([
            'success' => true,
            'apuracoes' => $dados['Apuracoes'] ?? $dados['apuracoes'] ?? [],
        ]);
    }

    /**
     * Detalha uma apuração MIT específica (débitos por tributo, suspensões
     * etc.) — CONSAPURACAO316.
     */
    public function consultarApuracaoMitDetalhe(Request $request, MitService $mit): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'id_apuracao' => 'required|integer',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resposta = $mit->consultarApuracao($cliente, (int) $validated['id_apuracao']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];

        return response()->json(['success' => true, 'apuracao' => $dados]);
    }

    /**
     * Encerra uma apuração MIT SEM MOVIMENTO (ENCAPURACAO314) — declaração
     * fiscal REAL e irreversível perante a Receita Federal.
     */
    public function encerrarApuracaoMitSemMovimento(Request $request, MitService $mit): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'ano_apuracao' => 'required|digits:4',
            'mes_apuracao' => 'required|integer|between:1,12',
            'qualificacao_pj' => 'required|integer|between:1,12',
            'cpf_responsavel' => 'required|digits:11',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resposta = $mit->encerrarApuracaoSemMovimento(
                $cliente,
                (int) $validated['ano_apuracao'],
                (int) $validated['mes_apuracao'],
                (int) $validated['qualificacao_pj'],
                $validated['cpf_responsavel'],
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];

        return response()->json([
            'success' => true,
            'message' => 'Apuração MIT sem movimento encerrada com sucesso.',
            'dados' => $dados,
        ]);
    }

    /**
     * Busca o rascunho de apuração MIT com movimento do cliente/ano/mês (ou
     * vazio, se ainda não existe).
     */
    public function getMitApuracaoRascunho(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'ano_apuracao' => 'required|digits:4',
            'mes_apuracao' => 'required|integer|between:1,12',
        ]);

        $rascunho = MitApuracaoRascunho::with('debitos')
            ->where('cliente_id', $validated['cliente_id'])
            ->where('ano_apuracao', $validated['ano_apuracao'])
            ->where('mes_apuracao', $validated['mes_apuracao'])
            ->first();

        if (! $rascunho) {
            return response()->json(['rascunho' => null]);
        }

        return response()->json([
            'rascunho' => $rascunho,
            'debitos' => $rascunho->debitos,
        ]);
    }

    /**
     * Salva (substituindo por completo) o rascunho de apuração MIT com
     * movimento do cliente/ano/mês — cabeçalho + lista de débitos. Não
     * transmite nada, só persiste localmente.
     */
    public function salvarMitApuracaoRascunho(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'ano_apuracao' => 'required|digits:4',
            'mes_apuracao' => 'required|integer|between:1,12',
            'qualificacao_pj' => 'required|integer|between:1,12',
            'tributacao_lucro' => 'nullable|integer|between:1,7',
            'variacoes_monetarias' => 'required|integer|in:1,2',
            'regime_pis_cofins' => 'nullable|integer|between:1,4',
            'cpf_responsavel' => 'required|digits:11',
            'balanco_irpj' => 'nullable|boolean',
            'balanco_csll' => 'nullable|boolean',
            'debitos' => 'required|array|min:1',
            'debitos.*.grupo' => 'required|string',
            'debitos.*.codigo_receita' => 'required|string',
            'debitos.*.periodicidade' => 'required|string|in:ME,TR,AN',
            'debitos.*.ano_referencia' => 'required|digits:4',
            'debitos.*.mes_referencia' => 'nullable|integer|between:1,12',
            'debitos.*.trimestre_referencia' => 'nullable|integer|between:1,4',
            'debitos.*.valor' => 'required|numeric|min:0.01',
            'debitos.*.cnpj_estabelecimento' => 'nullable|digits:14',
            'debitos.*.cnpj_incorporacao' => 'nullable|digits:14',
            'debitos.*.cnpj_scp' => 'nullable|digits:14',
            'debitos.*.codigo_municipio_ouro' => 'nullable|string',
        ]);

        $rascunho = DB::transaction(function () use ($validated) {
            $cabecalho = collect($validated)->except(['debitos'])->all();
            $cabecalho['sem_movimento'] = false;

            $rascunho = MitApuracaoRascunho::updateOrCreate(
                [
                    'cliente_id' => $validated['cliente_id'],
                    'ano_apuracao' => $validated['ano_apuracao'],
                    'mes_apuracao' => $validated['mes_apuracao'],
                ],
                $cabecalho
            );

            $rascunho->debitos()->delete();

            foreach ($validated['debitos'] as $debito) {
                $rascunho->debitos()->create($debito);
            }

            return $rascunho;
        });

        return response()->json([
            'success' => true,
            'message' => 'Rascunho da apuração MIT salvo com sucesso.',
            'rascunho_id' => $rascunho->id,
        ]);
    }

    /**
     * Encerra a apuração MIT COM MOVIMENTO de verdade a partir do rascunho já
     * salvo — declaração fiscal REAL perante a Receita Federal, irreversível.
     */
    public function encerrarApuracaoMitComMovimento(Request $request, MitService $mit): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'ano_apuracao' => 'required|digits:4',
            'mes_apuracao' => 'required|integer|between:1,12',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        $rascunho = MitApuracaoRascunho::with('debitos')
            ->where('cliente_id', $validated['cliente_id'])
            ->where('ano_apuracao', $validated['ano_apuracao'])
            ->where('mes_apuracao', $validated['mes_apuracao'])
            ->first();

        if (! $rascunho || $rascunho->debitos->isEmpty()) {
            return response()->json(['error' => 'Salve o rascunho da apuração (com ao menos 1 débito) antes de encerrar.'], 422);
        }

        try {
            $resposta = $mit->encerrarApuracaoComMovimento($cliente, $rascunho);
        } catch (\Throwable $e) {
            $rascunho->update(['status' => 'erro', 'mensagem_erro' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dados = json_decode($resposta['dados'] ?? '{}', true) ?? [];

        $rascunho->update([
            'status' => 'transmitida',
            'id_apuracao_serpro' => $dados['idApuracao'] ?? $dados['IdApuracao'] ?? null,
            'mensagem_erro' => null,
            'encerrado_em' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Apuração MIT com movimento encerrada com sucesso.',
            'dados' => $dados,
        ]);
    }

    public function downloadTeste(string $arquivo)
    {
        $path = 'integracontador/testes/'.basename($arquivo);

        abort_unless(file_exists(storage_path("app/{$path}")), 404);

        return response()->file(storage_path("app/{$path}"), ['Content-Type' => 'application/pdf']);
    }

    /**
     * Percorre recursivamente a estrutura procurando por objetos {nomeArquivo, pdf}
     * (formato observado na resposta real do CONSDECREC15) e salva cada PDF
     * decodificado em storage/app/integracontador/testes/.
     */
    private function extrairESalvarPdfs(array $dados): array
    {
        $arquivos = [];
        $dir = 'integracontador/testes';
        $destPath = storage_path("app/{$dir}");

        if (! is_dir($destPath)) {
            mkdir($destPath, 0755, true);
        }

        $percorrer = function ($valor) use (&$percorrer, &$arquivos, $destPath) {
            if (isset($valor['nomeArquivo'], $valor['pdf']) && is_string($valor['pdf'])) {
                $nomeSeguro = time().'-'.preg_replace('/[^A-Za-z0-9._-]/', '_', $valor['nomeArquivo']);
                file_put_contents("{$destPath}/{$nomeSeguro}", base64_decode($valor['pdf']));

                $arquivos[] = [
                    'nomeArquivo' => $valor['nomeArquivo'],
                    'url' => route('simples-nacional.configuracao.download-teste', ['arquivo' => $nomeSeguro]),
                ];

                return;
            }

            if (is_array($valor)) {
                foreach ($valor as $item) {
                    if (is_array($item)) {
                        $percorrer($item);
                    }
                }
            }
        };

        $percorrer($dados);

        return $arquivos;
    }

    private function removerCamposPdf(array $dados): array
    {
        array_walk_recursive($dados, function (&$valor, $chave) {
            if ($chave === 'pdf') {
                $valor = '(removido — veja em "arquivos")';
            }
        });

        return $dados;
    }
}
