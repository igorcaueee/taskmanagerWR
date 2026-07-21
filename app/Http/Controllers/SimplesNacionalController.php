<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteDadosSimples;
use App\Models\IntegraContadorConfiguracao;
use App\Models\SimplesDasProcessamento;
use App\Models\SimplesReceitaAtividade;
use App\Models\SimplesReceitaMensal;
use App\Services\CnpjPublicoService;
use App\Services\NfseService;
use App\Services\SimplesNacional\IntegraContadorAuthService;
use App\Services\SimplesNacional\PgdasdService;
use App\Support\PgdasdAtividades;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SimplesNacionalController extends Controller
{
    public function __construct(
        private NfseService $nfse,
        private CnpjPublicoService $cnpjPublico,
    ) {}

    public function index(Request $request)
    {
        $periodo = $request->get('periodo', now()->subMonthNoOverflow()->format('Ym'));

        $processamentos = SimplesDasProcessamento::with('cliente')
            ->where('periodo_apuracao', $periodo)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->get('status')))
            ->orderBy('cliente_id')
            ->paginate(30)
            ->withQueryString();

        $clientes = Cliente::where('regime_tributario', 'Simples Nacional')
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get(['id', 'nome', 'cpfcnpj']);

        $atividadesCatalogo = PgdasdAtividades::catalogo();
        $nomesTributos = PgdasdAtividades::NOMES_TRIBUTOS;

        return view('simples-nacional.index', compact('processamentos', 'periodo', 'clientes', 'atividadesCatalogo', 'nomesTributos'));
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

        if (!$pdfBase64) {
            return null;
        }

        $dir = 'integracontador/testes';
        $destPath = storage_path("app/{$dir}");

        if (!is_dir($destPath)) {
            mkdir($destPath, 0755, true);
        }

        $nomeSeguro = time() . '-' . preg_replace('/[^A-Za-z0-9._-]/', '_', $nomeArquivo);
        file_put_contents("{$destPath}/{$nomeSeguro}", base64_decode($pdfBase64));

        return [
            'nomeArquivo' => $nomeArquivo,
            'url' => route('simples-nacional.configuracao.download-teste', ['arquivo' => $nomeSeguro]),
        ];
    }

    private function buscarCampoPdf(array $dados): ?string
    {
        if (!empty($dados['pdf']) && is_string($dados['pdf'])) {
            return $dados['pdf'];
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
                if (!empty($operacao['indiceDeclaracao']['numeroDeclaracao'])) {
                    $numeroDeclaracao = $operacao['indiceDeclaracao']['numeroDeclaracao'];
                    $dataTransmissao = $operacao['indiceDeclaracao']['dataHoraTransmissao'] ?? null;
                }

                if (!empty($operacao['indiceDas'])) {
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
        ]);

        SimplesReceitaMensal::updateOrCreate(
            ['cliente_id' => $validated['cliente_id'], 'periodo_apuracao' => $validated['periodo_apuracao']],
            [
                'receita_bruta_competencia' => $validated['receita_bruta_competencia'],
                'receita_bruta_caixa' => $validated['receita_bruta_caixa'] ?? null,
                'regime_apuracao' => $validated['regime_apuracao'],
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
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $processamento = $pgdasd->transmitirDeclaracaoDoCliente(
                $cliente,
                $validated['periodo_apuracao'],
                (bool) ($validated['confirmar_receita_zerada'] ?? false),
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        if ($processamento->status === 'erro') {
            return response()->json(['error' => $processamento->mensagem_erro], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Declaração transmitida — status: {$processamento->status}, recibo: " . ($processamento->numero_recibo ?? '—'),
            'numero_recibo' => $processamento->numero_recibo,
        ]);
    }

    public function getConfiguracao(): JsonResponse
    {
        $config = IntegraContadorConfiguracao::first();

        if (!$config) {
            return response()->json(['configurado' => false]);
        }

        return response()->json([
            'configurado' => true,
            'arquivo_ok' => file_exists(storage_path('app/' . $config->arquivo_certificado)),
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

        $config = IntegraContadorConfiguracao::first() ?? new IntegraContadorConfiguracao();
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

            if (!is_dir($destPath)) {
                mkdir($destPath, 0755, true);
            }

            $destFile = "{$destPath}/certificado.pfx";

            if (!copy($file->getRealPath(), $destFile)) {
                return response()->json(['error' => 'Falha ao salvar o arquivo do certificado no servidor.'], 500);
            }

            $config->arquivo_certificado = "{$dir}/certificado.pfx";
            $config->senha_certificado = $validated['senha'];
        } elseif (!$config->exists) {
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

        if (!$config) {
            return response()->json(['error' => 'Configuração da API Integra Contador não encontrada.'], 422);
        }

        $cnpjInformado = preg_replace('/\D/', '', (string) $request->get('cnpj'));

        if ($config->ambiente === 'producao') {
            if (!$cnpjInformado) {
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

    public function downloadTeste(string $arquivo)
    {
        $path = "integracontador/testes/" . basename($arquivo);

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

        if (!is_dir($destPath)) {
            mkdir($destPath, 0755, true);
        }

        $percorrer = function ($valor) use (&$percorrer, &$arquivos, $destPath, $dir) {
            if (isset($valor['nomeArquivo'], $valor['pdf']) && is_string($valor['pdf'])) {
                $nomeSeguro = time() . '-' . preg_replace('/[^A-Za-z0-9._-]/', '_', $valor['nomeArquivo']);
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
