<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\PortalUsuario;
use App\Models\Produto;
use App\Models\QuestionarioResposta;
use App\Models\Segmentacao;
use App\Models\Socio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ClienteController extends Controller
{
    public function busca(Request $request): JsonResponse
    {
        $busca = $request->string('q')->trim();

        if ($busca->isEmpty() || $busca->length() < 2) {
            return response()->json([]);
        }

        $like = '%'.$busca.'%';

        $clientes = Cliente::where(function ($q) use ($like) {
            $q->where('nome', 'like', $like)
                ->orWhere('cpfcnpj', 'like', $like);
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

    public function showClientes(Request $request): View
    {
        $query = Cliente::orderBy('nome');

        if ($request->filled('busca')) {
            $busca = '%'.$request->string('busca').'%';
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', $busca)
                    ->orWhere('cpfcnpj', 'like', $busca)
                    ->orWhere('cidade', 'like', $busca)
                    ->orWhere('estado', 'like', $busca)
                    ->orWhere('regime_tributario', 'like', $busca);
            });
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

        if ($request->filled('segmentacao_id')) {
            $query->where('segmentacao_id', $request->integer('segmentacao_id'));
        }

        if ($request->filled('atividade')) {
            $query->where('atividade', 'like', '%'.$request->string('atividade').'%');
        }

        if ($request->input('acesso_extrato') !== null && $request->input('acesso_extrato') !== '') {
            $query->where('acesso_extrato', $request->boolean('acesso_extrato'));
        }

        $clientes = $query->paginate(50)->withQueryString();

        $segmentacoes = Segmentacao::orderBy('nome')->get(['id', 'nome']);

        return view('clientes.home', compact('clientes', 'segmentacoes'));
    }

    public function formClienteCreate(): View
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $produtos = Produto::where('ativo', true)->orderBy('nome')->get();
        $segmentacoes = Segmentacao::orderBy('nome')->get();

        return view('clientes.partials.formCliente', ['cliente' => null, 'produtos' => $produtos, 'segmentacoes' => $segmentacoes]);
    }

    public function showCliente(int $id): View
    {
        $cliente = Cliente::with(['produtos', 'socios', 'segmentacao', 'portalUsuarios'])->findOrFail($id);

        $ultimoIDE = QuestionarioResposta::where('cliente_id', $id)
            ->where('finalizado', true)
            ->latest()
            ->first();

        return view('clientes.show', compact('cliente', 'ultimoIDE'));
    }

    public function formClienteEdit(int $id): View
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::with(['produtos', 'socios'])->findOrFail($id);
        $produtos = Produto::where('ativo', true)->orderBy('nome')->get();
        $segmentacoes = Segmentacao::orderBy('nome')->get();

        return view('clientes.partials.formCliente', compact('cliente', 'produtos', 'segmentacoes'));
    }

    public function saveCliente(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $data = $request->only(['nome', 'pasta_arquivos', 'segmentacao_id', 'atividade', 'descricao', 'cpfcnpj', 'regime_tributario', 'cidade', 'estado', 'fator_r', 'acesso_extrato', 'cliente_desde', 'dataabertura', 'vencimento_certificado', 'faturamento', 'servico', 'honorario', 'possibilidade']);
        $data['status'] = 'ativo';

        $validator = Validator::make($data, [
            'nome' => ['required', 'string', 'max:255'],
            'pasta_arquivos' => ['nullable', 'string', 'max:255'],
            'segmentacao_id' => ['nullable', 'integer', 'exists:segmentacoes,id'],
            'atividade' => ['nullable', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'cpfcnpj' => ['nullable', 'string', 'max:255', 'unique:clientes,cpfcnpj'],
            'regime_tributario' => ['nullable', 'string', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:2'],
            'fator_r' => ['nullable'],
            'cliente_desde' => ['nullable', 'date'],
            'dataabertura' => ['nullable', 'date'],
            'vencimento_certificado' => ['nullable', 'date'],
            'faturamento' => ['nullable', 'numeric', 'min:0'],
            'servico' => ['nullable', 'string', 'max:255'],
            'honorario' => ['nullable', 'numeric', 'min:0'],
            'possibilidade' => ['nullable', 'string'],
            'acesso_extrato' => ['nullable', 'boolean'],
        ], [
            'cpfcnpj.unique' => 'Já existe um cliente cadastrado com este CPF/CNPJ.',
        ]);

        if ($validator->fails()) {
            return Redirect::back()->with('error', $validator->errors()->first())->withInput();
        }

        $data['fator_r'] = isset($data['fator_r']);
        $data['acesso_extrato'] = $request->filled('acesso_extrato') ? $request->boolean('acesso_extrato') : null;
        $data['tipo'] = $request->input('tipo', '1');

        Cliente::create($data);

        $cliente = Cliente::query()->latest()->first();
        $cliente->produtos()->sync($request->input('produtos', []));

        $redirect = Redirect::route('clientes.show', $cliente->id)->with('success', 'Cliente criado com sucesso.');

        if ($data['tipo'] === '1') {
            $redirect = $redirect->with('open_quadro_societario', true);
        }

        return $redirect;
    }

    public function updateCliente(Request $request, int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::findOrFail($id);

        $data = $request->only(['nome', 'pasta_arquivos', 'segmentacao_id', 'atividade', 'descricao', 'cpfcnpj', 'regime_tributario', 'cidade', 'estado', 'fator_r', 'acesso_extrato', 'cliente_desde', 'dataabertura', 'vencimento_certificado', 'faturamento', 'servico', 'honorario', 'possibilidade']);

        $validator = Validator::make($data, [
            'nome' => ['required', 'string', 'max:255'],
            'pasta_arquivos' => ['nullable', 'string', 'max:255'],
            'segmentacao_id' => ['nullable', 'integer', 'exists:segmentacoes,id'],
            'atividade' => ['nullable', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'cpfcnpj' => ['nullable', 'string', 'max:255', 'unique:clientes,cpfcnpj,'.$id],
            'regime_tributario' => ['nullable', 'string', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:2'],
            'fator_r' => ['nullable'],
            'cliente_desde' => ['nullable', 'date'],
            'dataabertura' => ['nullable', 'date'],
            'vencimento_certificado' => ['nullable', 'date'],
            'faturamento' => ['nullable', 'numeric', 'min:0'],
            'servico' => ['nullable', 'string', 'max:255'],
            'honorario' => ['nullable', 'numeric', 'min:0'],
            'possibilidade' => ['nullable', 'string'],
            'acesso_extrato' => ['nullable', 'boolean'],
        ], [
            'cpfcnpj.unique' => 'Já existe um cliente cadastrado com este CPF/CNPJ.',
        ]);

        if ($validator->fails()) {
            return Redirect::back()->with('error', $validator->errors()->first())->withInput();
        }

        $data['fator_r'] = isset($data['fator_r']);
        $data['acesso_extrato'] = $request->filled('acesso_extrato') ? $request->boolean('acesso_extrato') : null;
        $data['tipo'] = $request->input('tipo', '1');

        $cliente->update($data);

        $cliente->produtos()->sync($request->input('produtos', []));

        if ($request->input('redirect_to') === 'list') {
            $page = (int) $request->input('redirect_page', 1);

            return Redirect::route('clientes', $page > 1 ? ['page' => $page] : [])->with('success', 'Cliente atualizado com sucesso.');
        }

        return Redirect::route('clientes.show', $cliente->id)->with('success', 'Cliente atualizado com sucesso.');
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

        return view('clientes.partials.formEncerrarCliente', compact('cliente'));
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

        return Redirect::route('clientes')->with('success', 'Cliente encerrado com sucesso.');
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

        $header = array_map(fn ($v) => mb_strtolower(trim((string) $v)), $rows[0]);
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

    public function exportClientes(Request $request): Response
    {
        $query = Cliente::with(['segmentacao', 'produtos'])->orderBy('nome');

        if ($request->filled('busca')) {
            $busca = '%'.$request->string('busca').'%';
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', $busca)
                    ->orWhere('cpfcnpj', 'like', $busca)
                    ->orWhere('cidade', 'like', $busca)
                    ->orWhere('estado', 'like', $busca)
                    ->orWhere('regime_tributario', 'like', $busca);
            });
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

        if ($request->filled('segmentacao_id')) {
            $query->where('segmentacao_id', $request->integer('segmentacao_id'));
        }

        if ($request->filled('atividade')) {
            $query->where('atividade', 'like', '%'.$request->string('atividade').'%');
        }

        $clientes = $query->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Clientes');

        $headers = [
            'Nome', 'CPF/CNPJ', 'Tipo', 'Regime Tributário', 'Área', 'Atividade',
            'Cidade', 'UF', 'Status', 'Fator R', 'Cliente Desde', 'Data Abertura',
            'Vencimento Certificado', 'Faturamento', 'Serviço', 'Honorário', 'Capital Social',
            'Produtos', 'Motivo Encerramento', 'Data Encerramento',
        ];

        foreach ($headers as $i => $header) {
            $col = $i + 1;
            $cell = Coordinate::stringFromColumnIndex($col).'1';
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        $row = 2;
        foreach ($clientes as $cliente) {
            $tipo = match ((string) $cliente->tipo) {
                '1' => 'PJ',
                '0' => 'PF',
                default => '',
            };

            $produtos = $cliente->produtos->pluck('nome')->join(', ');

            $rowData = [
                $cliente->nome,
                $cliente->cpfcnpj,
                $tipo,
                $cliente->regime_tributario ? mb_strtoupper($cliente->regime_tributario) : '',
                $cliente->segmentacao?->nome ?? '',
                $cliente->atividade,
                $cliente->cidade,
                $cliente->estado,
                $cliente->status === 'ativo' ? 'Ativo' : 'Inativo',
                $cliente->fator_r ? 'Sim' : 'Não',
                $cliente->cliente_desde?->format('d/m/Y') ?? '',
                $cliente->dataabertura?->format('d/m/Y') ?? '',
                $cliente->vencimento_certificado?->format('d/m/Y') ?? '',
                $cliente->faturamento,
                $cliente->servico,
                $cliente->honorario,
                $cliente->capital_social,
                $produtos,
                $cliente->motivo_encerramento,
                $cliente->data_encerramento?->format('d/m/Y') ?? '',
            ];

            foreach ($rowData as $i => $value) {
                $col = $i + 1;
                $cell = Coordinate::stringFromColumnIndex($col).$row;
                $sheet->setCellValue($cell, $value);
            }

            $fillColor = $row % 2 === 0 ? 'F8FAFC' : 'FFFFFF';
            $lastCol = Coordinate::stringFromColumnIndex(count($headers));
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fillColor]],
            ]);

            $row++;
        }

        // Freeze header row
        $sheet->freezePane('A2');

        // Auto-filter
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->setAutoFilter("A1:{$lastCol}1");

        $writer = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        $filename = 'clientes-'.now()->format('Y-m-d').'.xlsx';

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
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

        $validator = Validator::make($request->only(['nome', 'telefone', 'gmail', 'participacao']), [
            'nome' => ['required', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'gmail' => ['nullable', 'email', 'max:255'],
            'participacao' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

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

        $validator = Validator::make($request->only(['nome', 'telefone', 'gmail', 'participacao']), [
            'nome' => ['required', 'string', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'gmail' => ['nullable', 'email', 'max:255'],
            'participacao' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

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

        if (! $cliente->pasta_arquivos) {
            return response()->json(['pastas' => []]);
        }

        $sharedRoot = rtrim(Storage::disk('shared')->path(''), '/');
        $pastaPortal = $sharedRoot.'/'.rtrim($cliente->pasta_arquivos, '/').'/Portal';

        if (! is_dir($pastaPortal)) {
            return response()->json(['pastas' => []]);
        }

        $pastas = [];
        foreach (new \DirectoryIterator($pastaPortal) as $item) {
            if ($item->isDot() || ! $item->isDir()) {
                continue;
            }
            $pastas[] = $item->getFilename();
        }

        sort($pastas);

        return response()->json(['pastas' => $pastas]);
    }

    public function storeUsuarioPortal(Request $request, int $id): JsonResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nome' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'unique:portal_usuarios,username'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6'],
            'acesso_total' => ['boolean'],
            'pastas_permitidas' => ['nullable', 'array'],
            'pastas_permitidas.*' => ['string', 'max:100'],
        ]);

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

        $validator = Validator::make($request->all(), [
            'nome' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'unique:portal_usuarios,username,'.$usuarioId],
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:6'],
            'ativo' => ['boolean'],
            'acesso_total' => ['boolean'],
            'pastas_permitidas' => ['nullable', 'array'],
            'pastas_permitidas.*' => ['string', 'max:100'],
        ]);

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

    public function destroyUsuarioPortal(int $clienteId, int $usuarioId): JsonResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $usuario = PortalUsuario::where('cliente_id', $clienteId)->findOrFail($usuarioId);
        $usuario->delete();

        return response()->json(['mensagem' => 'Usuário removido com sucesso.']);
    }

    // ── Logo do cliente ────────────────────────────────────────────────

    public function uploadLogo(Request $request, int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->with('error', $validator->errors()->first());
        }

        if ($cliente->logo) {
            Storage::disk('public')->delete($cliente->logo);
        }

        $path = $request->file('logo')->store('clientes/logos', 'public');

        $cliente->update(['logo' => $path]);

        return Redirect::route('clientes.show', $cliente->id)->with('success', 'Logo atualizada com sucesso.');
    }

    public function removeLogo(int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::findOrFail($id);

        if ($cliente->logo) {
            Storage::disk('public')->delete($cliente->logo);
            $cliente->update(['logo' => null]);
        }

        return Redirect::route('clientes.show', $cliente->id)->with('success', 'Logo removida com sucesso.');
    }
}
