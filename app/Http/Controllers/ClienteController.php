<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Departamento;
use App\Models\HistoricoRegimeTributario;
use App\Models\PortalUsuario;
use App\Models\Possibilidade;
use App\Models\PrecificacaoProduto;
use App\Models\Produto;
use App\Models\Segmentacao;
use App\Models\QuestionarioResposta;
use App\Models\Socio;
use App\Models\Tarefa;
use App\Models\Usuario;
use App\Services\ChecklistObrigacoesService;
use App\Services\CnpjPublicoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ClienteController extends Controller
{
    public const REGIMES = ['Simples Nacional', 'Lucro Presumido', 'Lucro Real', 'MEI', 'Associação'];

    public function __construct(
        private readonly ChecklistObrigacoesService $checklistObrigacoesService,
        private readonly CnpjPublicoService $cnpjPublicoService,
    ) {
    }

    /**
     * Expressão SQL que remove pontuação (. - /) do CPF/CNPJ armazenado,
     * permitindo buscar digitando só os números.
     */
    private function cpfCnpjNormalizadoSql(): string
    {
        return "REPLACE(REPLACE(REPLACE(cpfcnpj, '.', ''), '-', ''), '/', '')";
    }

    private function aplicarFiltroBusca($query, string $busca): void
    {
        $like = '%'.$busca.'%';
        $documento = preg_replace('/[^A-Za-z0-9]/', '', $busca);
        $normalizadoSql = $this->cpfCnpjNormalizadoSql();

        $query->where(function ($q) use ($like, $documento, $normalizadoSql) {
            $q->where('nome', 'like', $like)
                ->orWhere('cpfcnpj', 'like', $like)
                ->orWhere('cidade', 'like', $like)
                ->orWhere('estado', 'like', $like)
                ->orWhere('regime_tributario', 'like', $like);

            if ($documento !== '') {
                $q->orWhereRaw("{$normalizadoSql} LIKE ?", ['%'.$documento.'%']);
            }
        });
    }

    public function busca(Request $request): JsonResponse
    {
        $busca = $request->string('q')->trim();

        if ($busca->isEmpty() || $busca->length() < 2) {
            return response()->json([]);
        }

        $like = '%'.$busca.'%';
        $documento = preg_replace('/[^A-Za-z0-9]/', '', (string) $busca);
        $normalizadoSql = $this->cpfCnpjNormalizadoSql();

        $clientes = Cliente::where(function ($q) use ($like, $documento, $normalizadoSql) {
            $q->where('nome', 'like', $like)
                ->orWhere('cpfcnpj', 'like', $like);

            if ($documento !== '') {
                $q->orWhereRaw("{$normalizadoSql} LIKE ?", ['%'.$documento.'%']);
            }
        })
            ->orderBy('nome')
            ->limit(8)
            ->get(['id', 'nome', 'cpfcnpj', 'status', 'tipo']);

        return response()->json($clientes->map(fn (Cliente $c) => [
            'id' => $c->id,
            'nome' => $c->nome,
            'cpfcnpj' => $c->cpfcnpj,
            'status' => $c->status,
            'tipo' => $c->tipo,
            'url' => route('clientes.show', $c->id),
        ]));
    }

    public function verificarDocumento(Request $request): JsonResponse
    {
        $documento = preg_replace('/[^A-Za-z0-9]/', '', (string) $request->string('cpfcnpj'));

        if ($documento === '') {
            return response()->json(['existe' => false]);
        }

        $normalizadoSql = $this->cpfCnpjNormalizadoSql();

        $query = Cliente::whereRaw("{$normalizadoSql} = ?", [$documento]);

        if ($request->filled('excluir_id')) {
            $query->where('id', '!=', $request->integer('excluir_id'));
        }

        $cliente = $query->first(['id', 'nome', 'status']);

        return response()->json([
            'existe' => (bool) $cliente,
            'cliente' => $cliente ? [
                'id' => $cliente->id,
                'nome' => $cliente->nome,
                'status' => $cliente->status,
                'url' => route('clientes.show', $cliente->id),
            ] : null,
        ]);
    }

    public function showClientes(Request $request): View
    {
        $query = Cliente::orderBy('nome');

        if ($request->filled('busca')) {
            $this->aplicarFiltroBusca($query, $request->string('busca')->trim()->toString());
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('regime_tributario')) {
            $query->where('regime_tributario', $request->input('regime_tributario'));
        }

        $clientes = $query->paginate(50)->withQueryString();
        $segmentacoes = Segmentacao::orderBy('nome')->get();

        return view('clientes.home', compact('clientes', 'segmentacoes'));
    }

    public function formClienteCreate(): View
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $produtos = Produto::where('ativo', true)->orderBy('nome')->get();
        $possibilidades = Possibilidade::where('ativo', true)->orderBy('nome')->get();
        $segmentacoes = Segmentacao::orderBy('nome')->get();

        return view('clientes.partials.formCliente', ['cliente' => null, 'produtos' => $produtos, 'possibilidades' => $possibilidades, 'segmentacoes' => $segmentacoes]);
    }

    public function showCliente(int $id): View
    {
        $cliente = Cliente::with(['produtos', 'possibilidades', 'socios', 'segmentacao', 'certificadoNfse', 'historicoRegimeTributario.alteradoPor'])->findOrFail($id);
        $ultimoIDE = QuestionarioResposta::where('cliente_id', $id)
            ->where('finalizado', true)
            ->latest()
            ->first();
        $precificacaoProdutosCount = PrecificacaoProduto::where('cliente_id', $id)->count();

        return view('clientes.show', compact('cliente', 'ultimoIDE', 'precificacaoProdutosCount'));
    }

    public function formClienteEdit(int $id): View
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::with(['produtos', 'possibilidades', 'socios'])->findOrFail($id);
        $produtos = Produto::where('ativo', true)->orderBy('nome')->get();
        $possibilidades = Possibilidade::where('ativo', true)->orderBy('nome')->get();
        $segmentacoes = Segmentacao::orderBy('nome')->get();

        return view('clientes.partials.formCliente', compact('cliente', 'produtos', 'possibilidades', 'segmentacoes'));
    }

    /**
     * Consulta pública de CNPJ (BrasilAPI) para pré-preencher o formulário de cliente.
     */
    public function consultarCnpj(string $cnpj): JsonResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $dados = $this->cnpjPublicoService->buscarCadastro($cnpj);

        if (! $dados) {
            return response()->json(['error' => 'CNPJ não encontrado ou consulta indisponível.'], 404);
        }

        return response()->json($dados);
    }

    public function saveCliente(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $data = $request->only(['nome', 'tipo', 'pasta_arquivos', 'segmentacao_id', 'atividade', 'descricao', 'cpfcnpj', 'regime_tributario', 'cidade', 'estado', 'fator_r', 'importar_notas_fiscais', 'cliente_desde', 'dataabertura', 'vencimento_certificado', 'faturamento', 'servico', 'honorario']);
        $data['status'] = 'ativo';

        if (! auth()->user()?->canVerInfoComercialCliente()) {
            unset($data['faturamento'], $data['honorario']);
        }

        $validator = Validator::make($data, $this->regrasCliente($request), $this->mensagensCliente());

        if ($validator->fails()) {
            return Redirect::back()->with('error', $validator->errors()->first())->withInput();
        }

        if ((string) ($data['tipo'] ?? '1') !== '1') {
            $data['regime_tributario'] = null;
        }

        $data['fator_r'] = isset($data['fator_r']);
        $data['importar_notas_fiscais'] = $request->boolean('importar_notas_fiscais');
        $data += $this->cnaeDoRequest($request);

        Cliente::create($data);

        $cliente = Cliente::query()->latest()->first();
        $cliente->produtos()->sync($request->input('produtos', []));
        $cliente->possibilidades()->sync($request->input('possibilidades', []));

        return Redirect::route('clientes.checklist.form', $cliente->id)
            ->with('success', 'Cliente criado com sucesso. Revise as obrigações abaixo.');
    }

    public function updateCliente(Request $request, int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::findOrFail($id);

        $data = $request->only(['nome', 'tipo', 'pasta_arquivos', 'segmentacao_id', 'atividade', 'descricao', 'cpfcnpj', 'regime_tributario', 'cidade', 'estado', 'fator_r', 'importar_notas_fiscais', 'cliente_desde', 'dataabertura', 'vencimento_certificado', 'faturamento', 'servico', 'honorario']);

        if (! auth()->user()?->canVerInfoComercialCliente()) {
            unset($data['faturamento'], $data['honorario']);
        }

        $validator = Validator::make($data, $this->regrasCliente($request, $id), $this->mensagensCliente());

        if ($validator->fails()) {
            return Redirect::back()->with('error', $validator->errors()->first())->withInput();
        }

        if ((string) ($data['tipo'] ?? '1') !== '1') {
            $data['regime_tributario'] = null;
        }

        $data['fator_r'] = isset($data['fator_r']);
        $data['importar_notas_fiscais'] = $request->boolean('importar_notas_fiscais');
        $data += $this->cnaeDoRequest($request);

        $regimeAnterior = $cliente->regime_tributario;

        $cliente->update($data);

        $regimeMudou = mb_strtoupper(trim($regimeAnterior ?? '')) !== mb_strtoupper(trim($cliente->regime_tributario ?? ''));

        if ($regimeMudou) {
            HistoricoRegimeTributario::create([
                'cliente_id' => $cliente->id,
                'regime_anterior' => $regimeAnterior,
                'regime_novo' => $cliente->regime_tributario,
                'alterado_por' => auth()->id(),
            ]);
        }

        $cliente->produtos()->sync($request->input('produtos', []));
        $cliente->possibilidades()->sync($request->input('possibilidades', []));

        if ($regimeMudou) {
            return Redirect::route('clientes.checklist.form', $cliente->id)
                ->with('success', 'Regime alterado. Revise as obrigações abaixo.');
        }

        return Redirect::route('clientes.show', $cliente->id)->with('success', 'Cliente atualizado com sucesso.');
    }

    /**
     * Tela de revisão das obrigações sugeridas para o cliente (regime + CNAE).
     */
    public function formChecklistObrigacoes(int $id): View
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::findOrFail($id);

        $this->preencherCnaeSeVazio($cliente);

        $sugestoes = $this->checklistObrigacoesService->sugerirParaCliente($cliente);

        return view('clientes.checklistObrigacoes', [
            'cliente' => $cliente,
            'sugestoes' => $sugestoes,
            'departamentos' => Departamento::orderBy('nome')->get(),
            'usuarios' => Usuario::orderBy('nome')->get(),
        ]);
    }

    public function salvarChecklistObrigacoes(Request $request, int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::findOrFail($id);

        $selecoes = collect($request->input('obrigacoes', []))
            ->filter(fn ($config) => filter_var($config['ativo'] ?? false, FILTER_VALIDATE_BOOLEAN))
            ->map(fn ($config) => [
                'frequencia' => $config['frequencia'] ?? 'mensal',
                'dia_vencimento' => $config['dia_vencimento'] ?? null,
                'departamento_id' => $config['departamento_id'] ?? null,
                'responsavel_id' => $config['responsavel_id'] ?? null,
            ])
            ->all();

        $criadas = $this->checklistObrigacoesService->gerarSelecionadas($cliente, $selecoes);

        $msg = $criadas > 0
            ? "{$criadas} tarefa(s) de obrigações geradas."
            : 'Nenhuma tarefa nova gerada.';

        return Redirect::route('clientes.show', $cliente->id)->with('success', $msg);
    }

    /**
     * Reconsulta o CNAE do cliente na BrasilAPI e volta para a tela de checklist.
     */
    public function atualizarCnaeCliente(int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::findOrFail($id);

        if (! $this->atualizarCnae($cliente)) {
            return Redirect::back()->with('error', 'Não foi possível consultar o CNAE (CNPJ inválido ou API indisponível).');
        }

        return Redirect::back()->with('success', 'CNAE atualizado a partir da Receita Federal.');
    }

    /**
     * Regras de validação do cadastro de cliente, compartilhadas entre criar e editar.
     *
     * @return array<string, mixed>
     */
    private function regrasCliente(Request $request, ?int $ignoreId = null): array
    {
        $isPJ = (string) $request->input('tipo', '1') === '1';

        $unique = Rule::unique('clientes', 'cpfcnpj');
        if ($ignoreId) {
            $unique->ignore($ignoreId);
        }

        $documento = function ($attribute, $value, $fail) use ($isPJ) {
            if (! $value) {
                return;
            }
            $limpo = preg_replace('/[^0-9A-Za-z]/', '', (string) $value);
            if ($isPJ && strlen($limpo) !== 14) {
                $fail('O CNPJ deve ter 14 caracteres.');
            }
            if (! $isPJ && strlen($limpo) !== 11) {
                $fail('O CPF deve ter 11 dígitos.');
            }
        };

        return [
            'tipo' => ['required', 'in:0,1'],
            'nome' => ['required', 'string', 'min:2', 'max:255'],
            'pasta_arquivos' => ['nullable', 'string', 'max:255'],
            'segmentacao_id' => ['nullable', 'integer', 'exists:segmentacoes,id'],
            'atividade' => ['nullable', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:5000'],
            'cpfcnpj' => [$isPJ ? 'required' : 'nullable', 'string', 'max:20', $unique, $documento],
            'regime_tributario' => [$isPJ ? 'required' : 'nullable', Rule::in(self::REGIMES)],
            'cidade' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'size:2', 'alpha'],
            'fator_r' => ['nullable'],
            'importar_notas_fiscais' => ['nullable'],
            'cliente_desde' => ['nullable', 'date'],
            'dataabertura' => ['nullable', 'date', 'before_or_equal:today'],
            'vencimento_certificado' => ['nullable', 'date'],
            'faturamento' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'servico' => ['nullable', 'string', 'max:255'],
            'honorario' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function mensagensCliente(): array
    {
        return [
            'tipo.required' => 'Selecione o tipo (Pessoa Física ou Jurídica).',
            'tipo.in' => 'Tipo de cliente inválido.',
            'nome.required' => 'O nome do cliente é obrigatório.',
            'nome.min' => 'O nome deve ter ao menos 2 caracteres.',
            'nome.max' => 'O nome não pode passar de 255 caracteres.',
            'cpfcnpj.required' => 'O CNPJ é obrigatório para Pessoa Jurídica.',
            'cpfcnpj.unique' => 'Já existe um cliente cadastrado com este CPF/CNPJ.',
            'regime_tributario.required' => 'Selecione o regime tributário.',
            'regime_tributario.in' => 'Regime tributário inválido.',
            'segmentacao_id.exists' => 'Área/segmentação inválida.',
            'estado.size' => 'A UF deve ter exatamente 2 letras.',
            'estado.alpha' => 'A UF deve conter apenas letras.',
            'atividade.max' => 'A atividade não pode passar de 255 caracteres.',
            'descricao.max' => 'A descrição está muito longa (máx. 5000 caracteres).',
            'cliente_desde.date' => 'Data "cliente desde" inválida.',
            'dataabertura.date' => 'Data de abertura inválida.',
            'dataabertura.before_or_equal' => 'A data de abertura não pode ser futura.',
            'vencimento_certificado.date' => 'Data de vencimento do certificado inválida.',
            'faturamento.numeric' => 'O faturamento deve ser um valor numérico.',
            'faturamento.min' => 'O faturamento não pode ser negativo.',
            'honorario.numeric' => 'O honorário deve ser um valor numérico.',
            'honorario.min' => 'O honorário não pode ser negativo.',
        ];
    }

    /**
     * @return array{cnae_principal: ?string, cnae_secundarios: ?array}
     */
    private function cnaeDoRequest(Request $request): array
    {
        $secundarios = json_decode((string) $request->input('cnae_secundarios'), true);

        return [
            'cnae_principal' => $request->input('cnae_principal') ?: null,
            'cnae_secundarios' => is_array($secundarios) && $secundarios ? array_values($secundarios) : null,
        ];
    }

    private function preencherCnaeSeVazio(Cliente $cliente): void
    {
        if (! $cliente->cnae_principal) {
            $this->atualizarCnae($cliente);
        }
    }

    private function atualizarCnae(Cliente $cliente): bool
    {
        $cnpj = preg_replace('/\D/', '', (string) $cliente->cpfcnpj);

        if (strlen($cnpj) !== 14) {
            return false;
        }

        $cnaes = $this->cnpjPublicoService->buscarCnaes($cnpj);

        if (! $cnaes) {
            return false;
        }

        $cliente->update([
            'cnae_principal' => $cnaes['principal'],
            'cnae_secundarios' => $cnaes['secundarios'],
        ]);

        return true;
    }

    public function deleteCliente(int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        Cliente::findOrFail($id)->delete();

        return Redirect::route('clientes')->with('success', 'Cliente excluído com sucesso.');
    }

    public function formEncerrarCliente(int $id): View
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::findOrFail($id);

        $tarefasVigentes = Tarefa::where('cliente_id', $id)
            ->where('ativo', true)
            ->whereNull('data_conclusao')
            ->count();

        return view('clientes.partials.formEncerrarCliente', compact('cliente', 'tarefasVigentes'));
    }

    public function encerrarCliente(Request $request, int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'motivo_encerramento' => ['required', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $cliente->update([
            'status' => 'inativo',
            'motivo_encerramento' => $request->input('motivo_encerramento'),
            'data_encerramento' => Carbon::today(),
        ]);

        $mensagem = 'Cliente encerrado com sucesso.';

        if ($request->boolean('inativar_tarefas')) {
            $tarefas = Tarefa::where('cliente_id', $id)
                ->where('ativo', true)
                ->whereNull('data_conclusao')
                ->get();

            $now = Carbon::now();
            foreach ($tarefas as $tarefa) {
                $tarefa->update([
                    'ativo' => false,
                    'inativado_por' => auth()->id(),
                    'inativado_em' => $now,
                ]);
            }

            if ($tarefas->count() > 0) {
                $mensagem .= " {$tarefas->count()} tarefa(s) vigente(s) também foram inativadas.";
            }
        }

        return Redirect::route('clientes')->with('success', $mensagem);
    }

    public function reativarCliente(int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::findOrFail($id);

        $cliente->update([
            'status' => 'ativo',
            'motivo_encerramento' => null,
            'data_encerramento' => null,
        ]);

        return Redirect::route('clientes')->with('success', 'Cliente reativado com sucesso.');
    }

    public function formImportClientes(): View
    {
        return view('clientes.partials.formImport');
    }

    public function importClientes(Request $request): RedirectResponse
    {
        $request->validate(['arquivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120']]);

        $spreadsheet = IOFactory::load($request->file('arquivo')->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();

        $rows = [];
        foreach ($sheet->getRowIterator() as $row) {
            $rowData = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            foreach ($cellIterator as $cell) {
                $value = $cell->getValue();
                if (Date::isDateTime($cell) && is_numeric($value)) {
                    $value = Date::excelToDateTimeObject((float) $value)->format('Y-m-d');
                }
                $rowData[] = $value;
            }
            $rows[] = $rowData;
        }

        if (empty($rows)) {
            return Redirect::route('clientes')->with('error', 'Arquivo vazio.');
        }

        // Normaliza o cabeçalho (remove acentos, espaços, "/" etc.) para aceitar tanto o
        // modelo de importação (snake_case) quanto o arquivo gerado pela exportação de clientes.
        $normalizeHeader = function ($value): string {
            $value = Str::ascii(mb_strtolower(trim((string) $value)));

            return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
        };

        $headerAliases = [
            'nome' => 'nome',
            'cpfcnpj' => 'cpfcnpj',
            'cpf' => 'cpfcnpj',
            'cnpj' => 'cpfcnpj',
            'tipo' => 'tipo',
            'regimetributario' => 'regime_tributario',
            'regime' => 'regime_tributario',
            'cidade' => 'cidade',
            'estado' => 'estado',
            'uf' => 'estado',
            'status' => 'status',
            'clientedesde' => 'cliente_desde',
            'dataabertura' => 'dataabertura',
            'faturamento' => 'faturamento',
            'servico' => 'servico',
            'honorario' => 'honorario',
            'fatorr' => 'fator_r',
            'atividade' => 'atividade',
            'area' => 'area',
        ];

        $header = array_map(function ($v) use ($normalizeHeader, $headerAliases) {
            $normalized = $normalizeHeader($v);

            return $headerAliases[$normalized] ?? $normalized;
        }, $rows[0]);
        $colIndex = array_flip($header);

        $get = fn ($row, $col) => isset($colIndex[$col]) ? trim((string) ($row[$colIndex[$col]] ?? '')) : '';

        $regimeMap = [
            'simples nacional' => 'Simples Nacional',
            'lucro presumido' => 'Lucro Presumido',
            'lucro real' => 'Lucro Real',
            'mei' => 'MEI',
        ];

        $parseDate = function (string $value): ?string {
            if ($value === '') {
                return null;
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return $value;
            }
            if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
                return Carbon::createFromFormat('d/m/Y', $value)->toDateString();
            }

            return null;
        };

        $importados = 0;
        $atualizados = 0;

        foreach (array_slice($rows, 1) as $row) {
            $nome = $get($row, 'nome');
            if ($nome === '') {
                continue;
            }

            $cpfcnpj = $get($row, 'cpfcnpj') ?: null;

            $tipoRaw = mb_strtoupper($get($row, 'tipo'));
            $tipo = match ($tipoRaw) {
                'PJ' => 1,
                'PF' => 0,
                default => null,
            };

            $fatorR = in_array(mb_strtolower($get($row, 'fator_r')), ['sim', 'yes', '1', 'true']);
            $status = in_array(mb_strtolower($get($row, 'status')), ['ativo', 'active', '1']) ? 'ativo' : 'inativo';

            $regimeRaw = mb_strtolower($get($row, 'regime_tributario'));
            $regime = $regimeMap[$regimeRaw] ?? ($get($row, 'regime_tributario') ?: null);

            $areaRaw = trim($get($row, 'area'));
            $segmentacaoId = null;
            if ($areaRaw !== '') {
                $segmentacaoId = Segmentacao::firstOrCreate(['nome' => $areaRaw])->id;
            }

            $dados = [
                'tipo' => $tipo,
                'atividade' => $get($row, 'atividade') ?: null,
                'segmentacao_id' => $segmentacaoId,
                'regime_tributario' => $regime,
                'cidade' => $get($row, 'cidade') ?: null,
                'estado' => mb_strtoupper($get($row, 'estado')) ?: null,
                'status' => $status,
                'fator_r' => $fatorR,
                'cliente_desde' => $parseDate($get($row, 'cliente_desde')),
                'dataabertura' => $parseDate($get($row, 'dataabertura')),
                'faturamento' => is_numeric($get($row, 'faturamento')) ? (float) $get($row, 'faturamento') : null,
                'servico' => $get($row, 'servico') ?: null,
                'honorario' => is_numeric($get($row, 'honorario')) ? (float) $get($row, 'honorario') : null,
            ];

            $existente = $cpfcnpj ? Cliente::where('cpfcnpj', $cpfcnpj)->first() : null;

            if ($existente) {
                // Atualiza apenas campos que estão nulos/vazios no cadastro atual
                $atualizar = array_filter($dados, fn ($val) => $val !== null && $val !== '');
                $atualizar = array_filter($atualizar, fn ($val, $col) => is_null($existente->$col) || $existente->$col === '', ARRAY_FILTER_USE_BOTH);

                if (! empty($atualizar)) {
                    $existente->update($atualizar);
                }

                $atualizados++;
            } else {
                Cliente::create(array_merge(['nome' => $nome, 'cpfcnpj' => $cpfcnpj], $dados));
                $importados++;
            }
        }

        $msg = "Importação concluída: {$importados} cliente(s) novo(s) importado(s)";
        if ($atualizados > 0) {
            $msg .= ", {$atualizados} cliente(s) existente(s) atualizado(s) com campos faltantes";
        }

        return Redirect::route('clientes')->with('success', $msg.'.');
    }

    public function templateClientes(): Response
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Clientes');

        $columns = [
            'nome', 'cpfcnpj', 'tipo', 'regime_tributario',
            'cidade', 'estado', 'status', 'cliente_desde',
            'dataabertura', 'faturamento', 'servico', 'honorario', 'fator_r', 'atividade', 'area',
        ];

        foreach ($columns as $i => $col) {
            $cell = chr(65 + $i).'1';
            $sheet->setCellValue($cell, $col);
            $sheet->getStyle($cell)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getColumnDimensionByColumn($i + 1)->setAutoSize(true);
        }

        $examples = [
            'Empresa Exemplo Ltda', '12.345.678/0001-99', 'PJ', 'Simples Nacional',
            'São Paulo', 'SP', 'ativo', '01/01/2024',
            '15/03/2010', '50000', 'Contabilidade', '800', 'Não', 'Comércio', 'Varejo',
        ];

        foreach ($examples as $i => $val) {
            $cell = chr(65 + $i).'2';
            $sheet->setCellValue($cell, $val);
            $sheet->getStyle($cell)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F4FF']],
                'font' => ['italic' => true, 'color' => ['rgb' => '6B7280']],
            ]);
        }

        $writer = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="modelo-importacao-clientes.xlsx"',
        ]);
    }

    public function exportClientes(Request $request): Response
    {
        $query = Cliente::orderBy('nome');

        if ($request->filled('busca')) {
            $this->aplicarFiltroBusca($query, $request->string('busca')->trim()->toString());
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('regime_tributario')) {
            $query->where('regime_tributario', $request->input('regime_tributario'));
        }

        $clientes = $query->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Clientes');

        $headers = [
            'Nome', 'CPF/CNPJ', 'Tipo', 'Regime Tributário', 'Cidade', 'Estado',
            'Status', 'Cliente Desde', 'Data Abertura', 'Faturamento',
            'Serviço', 'Honorário', 'Fator R', 'Atividade',
        ];

        foreach ($headers as $i => $header) {
            $cell = chr(65 + $i).'1';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getColumnDimensionByColumn($i + 1)->setAutoSize(true);
        }

        foreach ($clientes as $row => $c) {
            $rowNum = $row + 2;
            $sheet->setCellValue('A'.$rowNum, $c->nome);
            $sheet->setCellValue('B'.$rowNum, $c->cpfcnpj);
            $sheet->setCellValue('C'.$rowNum, $c->tipo == 1 ? 'PJ' : ($c->tipo == 0 ? 'PF' : ''));
            $sheet->setCellValue('D'.$rowNum, $c->regime_tributario);
            $sheet->setCellValue('E'.$rowNum, $c->cidade);
            $sheet->setCellValue('F'.$rowNum, $c->estado);
            $sheet->setCellValue('G'.$rowNum, $c->status);
            $sheet->setCellValue('H'.$rowNum, $c->cliente_desde ? $c->cliente_desde->format('d/m/Y') : '');
            $sheet->setCellValue('I'.$rowNum, $c->dataabertura ? $c->dataabertura->format('d/m/Y') : '');
            $sheet->setCellValue('J'.$rowNum, $c->faturamento);
            $sheet->setCellValue('K'.$rowNum, $c->servico);
            $sheet->setCellValue('L'.$rowNum, $c->honorario);
            $sheet->setCellValue('M'.$rowNum, $c->fator_r ? 'Sim' : 'Não');
            $sheet->setCellValue('N'.$rowNum, $c->atividade);
        }

        $writer = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="clientes.xlsx"',
        ]);
    }

    // ── Quadro Societário ──────────────────────────────────────────────

    public function quadroSocietario(int $id): View
    {
        $cliente = Cliente::with('socios')->findOrFail($id);

        return view('clientes.partials.quadroSocietario', compact('cliente'));
    }

    public function saveSocio(Request $request, int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::findOrFail($id);

        if ($request->filled('capital_social')) {
            $cliente->update(['capital_social' => $request->input('capital_social')]);
        }

        // Se for só atualização do capital, retorna sem criar sócio
        if ($request->boolean('_only_capital')) {
            return Redirect::route('clientes.quadro.modal', $id);
        }

        $validator = Validator::make(
            $request->only(['nome', 'telefone', 'gmail', 'participacao']),
            $this->regrasSocio(),
            $this->mensagensSocio(),
        );

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $ordem = $cliente->socios()->max('ordem') + 1;
        $participacao = (float) $request->input('participacao');
        $cliente->refresh();
        $quotas = $cliente->capital_social ? round($cliente->capital_social * $participacao / 100, 2) : 0;

        $cliente->socios()->create([
            'ordem' => $ordem,
            'nome' => $request->input('nome'),
            'telefone' => $request->input('telefone'),
            'gmail' => $request->input('gmail'),
            'participacao' => $participacao,
            'quotas_integralizadas' => $quotas,
        ]);

        return Redirect::route('clientes.quadro.modal', $id)->with('success', 'Sócio adicionado com sucesso.');
    }

    public function updateSocio(Request $request, int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $socio = Socio::findOrFail($id);

        $validator = Validator::make(
            $request->only(['nome', 'telefone', 'gmail', 'participacao']),
            $this->regrasSocio(),
            $this->mensagensSocio(),
        );

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $cliente = $socio->cliente;

        if ($request->filled('capital_social')) {
            $cliente->update(['capital_social' => $request->input('capital_social')]);
            $cliente->refresh();
        }

        $participacao = (float) $request->input('participacao');
        $quotas = $cliente->capital_social ? round($cliente->capital_social * $participacao / 100, 2) : 0;

        $socio->update([
            'nome' => $request->input('nome'),
            'telefone' => $request->input('telefone'),
            'gmail' => $request->input('gmail'),
            'participacao' => $participacao,
            'quotas_integralizadas' => $quotas,
        ]);

        return Redirect::route('clientes.quadro.modal', $socio->cliente_id)->with('success', 'Sócio atualizado com sucesso.');
    }

    /**
     * @return array<string, mixed>
     */
    private function regrasSocio(): array
    {
        return [
            'nome' => ['required', 'string', 'min:2', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20', 'regex:/^\(?\d{2}\)?[\s-]?\d{4,5}[\s-]?\d{4}$/'],
            'gmail' => ['nullable', 'email:rfc', 'max:255'],
            'participacao' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function mensagensSocio(): array
    {
        return [
            'nome.required' => 'O nome do sócio é obrigatório.',
            'nome.min' => 'O nome deve ter ao menos 2 caracteres.',
            'telefone.regex' => 'Telefone inválido. Use o formato (00) 00000-0000.',
            'gmail.email' => 'E-mail inválido.',
            'participacao.required' => 'Informe a participação (%).',
            'participacao.numeric' => 'A participação deve ser um número.',
            'participacao.min' => 'A participação não pode ser negativa.',
            'participacao.max' => 'A participação não pode passar de 100%.',
        ];
    }

    public function deleteSocio(int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $socio = Socio::findOrFail($id);
        $clienteId = $socio->cliente_id;
        $socio->delete();

        // Renumerar ordem
        Socio::where('cliente_id', $clienteId)->orderBy('ordem')->get()
            ->each(fn ($s, $i) => $s->update(['ordem' => $i + 1]));

        return Redirect::route('clientes.quadro.modal', $clienteId)->with('success', 'Sócio removido com sucesso.');
    }

    public function pastasPortalCliente(int $id): JsonResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::findOrFail($id);

        $pastas = ['Contabilidade', 'Financeiro', 'Fiscal', 'Patrimônio', 'Pessoal'];

        if ($cliente->pasta_arquivos) {
            $sharedRoot = rtrim(Storage::disk('shared')->path(''), '/');
            $pastaPortal = $sharedRoot.'/'.rtrim($cliente->pasta_arquivos, '/').'/Portal';

            foreach ($pastas as $pasta) {
                $caminho = $pastaPortal.'/'.$pasta;
                if (! is_dir($caminho)) {
                    @mkdir($caminho, 0775, true);
                }
            }
        }

        return response()->json(['pastas' => $pastas]);
    }

    public function storeUsuarioPortal(Request $request, int $id): JsonResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::findOrFail($id);

        $validator = Validator::make(
            $request->all(),
            $this->regrasUsuarioPortal() + ['password' => ['required', 'string', 'min:6']],
            $this->mensagensUsuarioPortal(),
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $acessoTotal = $request->boolean('acesso_total', true);

        $usuario = $cliente->portalUsuarios()->create([
            'nome' => $request->nome,
            'username' => $request->username,
            'email' => $request->email,
            'telefone' => $request->telefone,
            'password' => Hash::make($request->password),
            'ativo' => true,
            'acesso_total' => $acessoTotal,
            'pastas_permitidas' => $acessoTotal ? null : ($request->pastas_permitidas ?? []),
        ]);

        return response()->json(['usuario' => $usuario, 'mensagem' => 'Usuário criado com sucesso.']);
    }

    public function updateUsuarioPortal(Request $request, int $clienteId, int $usuarioId): JsonResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $usuario = PortalUsuario::where('cliente_id', $clienteId)->findOrFail($usuarioId);

        $validator = Validator::make(
            $request->all(),
            $this->regrasUsuarioPortal($usuarioId) + [
                'password' => ['nullable', 'string', 'min:6'],
                'ativo' => ['boolean'],
            ],
            $this->mensagensUsuarioPortal(),
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $acessoTotal = $request->boolean('acesso_total', $usuario->acesso_total);

        $dados = [
            'nome' => $request->nome,
            'username' => $request->username,
            'email' => $request->email,
            'telefone' => $request->telefone,
            'ativo' => $request->boolean('ativo', $usuario->ativo),
            'acesso_total' => $acessoTotal,
            'pastas_permitidas' => $acessoTotal ? null : ($request->pastas_permitidas ?? []),
        ];

        if ($request->filled('password')) {
            $dados['password'] = Hash::make($request->password);
        }

        $usuario->update($dados);

        return response()->json(['usuario' => $usuario->fresh(), 'mensagem' => 'Usuário atualizado com sucesso.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function regrasUsuarioPortal(?int $ignoreId = null): array
    {
        $username = Rule::unique('portal_usuarios', 'username');
        if ($ignoreId) {
            $username->ignore($ignoreId);
        }

        return [
            'nome' => ['required', 'string', 'min:2', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:100', 'regex:/^[a-zA-Z0-9._-]+$/', $username],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:30', 'regex:/^\(?\d{2}\)?[\s-]?\d{4,5}[\s-]?\d{4}$/'],
            'acesso_total' => ['boolean'],
            'pastas_permitidas' => ['nullable', 'array'],
            'pastas_permitidas.*' => ['string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function mensagensUsuarioPortal(): array
    {
        return [
            'nome.required' => 'O nome é obrigatório.',
            'nome.min' => 'O nome deve ter ao menos 2 caracteres.',
            'username.required' => 'O usuário (login) é obrigatório.',
            'username.min' => 'O usuário deve ter ao menos 3 caracteres.',
            'username.regex' => 'O usuário pode conter apenas letras, números, ponto, hífen e underline.',
            'username.unique' => 'Este usuário (login) já está em uso.',
            'email.email' => 'E-mail inválido.',
            'telefone.regex' => 'Telefone inválido. Use o formato (00) 00000-0000.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter ao menos 6 caracteres.',
        ];
    }

    public function destroyUsuarioPortal(int $clienteId, int $usuarioId): JsonResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $usuario = PortalUsuario::where('cliente_id', $clienteId)->findOrFail($usuarioId);
        $usuario->delete();

        return response()->json(['mensagem' => 'Usuário removido com sucesso.']);
    }
}
