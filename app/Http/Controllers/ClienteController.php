<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Produto;
use App\Models\Socio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ClienteController extends Controller
{
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

        $clientes = $query->get();

        return view('clientes.home', compact('clientes'));
    }

    public function formClienteCreate(): View
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $produtos = Produto::where('ativo', true)->orderBy('nome')->get();

        return view('clientes.partials.formCliente', ['cliente' => null, 'produtos' => $produtos]);
    }

    public function formClienteEdit(int $id): View
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::with(['produtos', 'socios'])->findOrFail($id);
        $produtos = Produto::where('ativo', true)->orderBy('nome')->get();

        return view('clientes.partials.formCliente', compact('cliente', 'produtos'));
    }

    public function saveCliente(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $data = $request->only(['nome', 'descricao', 'cpfcnpj', 'regime_tributario', 'cidade', 'estado', 'status', 'fator_r', 'cliente_desde', 'dataabertura', 'vencimento_certificado', 'faturamento', 'servico', 'honorario', 'possibilidade']);

        $validator = Validator::make($data, [
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'cpfcnpj' => ['nullable', 'string', 'max:255', 'unique:clientes,cpfcnpj'],
            'regime_tributario' => ['nullable', 'string', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:2'],
            'status' => ['nullable', 'string', 'max:255'],
            'fator_r' => ['nullable'],
            'cliente_desde' => ['nullable', 'date'],
            'dataabertura' => ['nullable', 'date'],
            'vencimento_certificado' => ['nullable', 'date'],
            'faturamento' => ['nullable', 'numeric', 'min:0'],
            'servico' => ['nullable', 'string', 'max:255'],
            'honorario' => ['nullable', 'numeric', 'min:0'],
            'possibilidade' => ['nullable', 'string'],
        ], [
            'cpfcnpj.unique' => 'Já existe um cliente cadastrado com este CPF/CNPJ.',
        ]);

        if ($validator->fails()) {
            return Redirect::back()->with('error', $validator->errors()->first())->withInput();
        }

        $data['fator_r'] = isset($data['fator_r']);

        Cliente::create($data);

        $cliente = Cliente::query()->latest()->first();
        $cliente->produtos()->sync($request->input('produtos', []));

        return Redirect::route('clientes')->with('success', 'Cliente criado com sucesso.');
    }

    public function updateCliente(Request $request, int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::findOrFail($id);

        $data = $request->only(['nome', 'descricao', 'cpfcnpj', 'regime_tributario', 'cidade', 'estado', 'status', 'fator_r', 'cliente_desde', 'dataabertura', 'vencimento_certificado', 'faturamento', 'servico', 'honorario', 'possibilidade']);

        $validator = Validator::make($data, [
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'cpfcnpj' => ['nullable', 'string', 'max:255', 'unique:clientes,cpfcnpj,'.$id],
            'regime_tributario' => ['nullable', 'string', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:2'],
            'status' => ['nullable', 'string', 'max:255'],
            'fator_r' => ['nullable'],
            'cliente_desde' => ['nullable', 'date'],
            'dataabertura' => ['nullable', 'date'],
            'vencimento_certificado' => ['nullable', 'date'],
            'faturamento' => ['nullable', 'numeric', 'min:0'],
            'servico' => ['nullable', 'string', 'max:255'],
            'honorario' => ['nullable', 'numeric', 'min:0'],
            'possibilidade' => ['nullable', 'string'],
        ], [
            'cpfcnpj.unique' => 'Já existe um cliente cadastrado com este CPF/CNPJ.',
        ]);

        if ($validator->fails()) {
            return Redirect::back()->with('error', $validator->errors()->first())->withInput();
        }

        $data['fator_r'] = isset($data['fator_r']);

        $cliente->update($data);

        $cliente->produtos()->sync($request->input('produtos', []));

        return Redirect::route('clientes')->with('success', 'Cliente atualizado com sucesso.');
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

    public function formImportClientes(): View
    {
        return view('clientes.partials.formImport');
    }

    public function importClientes(Request $request): RedirectResponse
    {
        $request->validate(['arquivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120']]);

        $spreadsheet = IOFactory::load($request->file('arquivo')->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);

        if (empty($rows)) {
            return Redirect::route('clientes')->with('error', 'Arquivo vazio.');
        }

        $header = array_map(fn ($v) => mb_strtolower(trim((string) $v)), $rows[0]);
        $colIndex = array_flip($header);

        $get = fn ($row, $col) => isset($colIndex[$col]) ? trim((string) ($row[$colIndex[$col]] ?? '')) : '';

        $importados = 0;
        $ignorados = 0;

        foreach (array_slice($rows, 1) as $row) {
            $nome = $get($row, 'nome');
            if ($nome === '') {
                continue;
            }

            $cpfcnpj = $get($row, 'cpfcnpj') ?: null;

            if ($cpfcnpj && Cliente::where('cpfcnpj', $cpfcnpj)->exists()) {
                $ignorados++;

                continue;
            }

            $tipoRaw = mb_strtoupper($get($row, 'tipo'));
            $tipo = match ($tipoRaw) {
                'PJ' => 1,
                'PF' => 0,
                default => null,
            };

            $fatorR = in_array(mb_strtolower($get($row, 'fator_r')), ['sim', 'yes', '1', 'true']);

            $status = in_array(mb_strtolower($get($row, 'status')), ['ativo', 'active', '1']) ? 'ativo' : 'inativo';

            $parseDate = function (string $value): ?string {
                if ($value === '') {
                    return null;
                }
                // Serial numérico do Excel (ex: 45292)
                if (is_numeric($value) && (float) $value > 1) {
                    try {
                        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)
                            ->format('Y-m-d');
                    } catch (\Exception $e) {}
                }
                // DD/MM/AAAA
                if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
                    return Carbon::createFromFormat('d/m/Y', $value)->toDateString();
                }
                // AAAA-MM-DD
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    return $value;
                }

                return null;
            };

            Cliente::create([
                'nome' => $nome,
                'cpfcnpj' => $cpfcnpj,
                'tipo' => $tipo,
                'regime_tributario' => $get($row, 'regime_tributario') ?: null,
                'cidade' => $get($row, 'cidade') ?: null,
                'estado' => mb_strtoupper($get($row, 'estado')) ?: null,
                'status' => $status,
                'fator_r' => $fatorR,
                'cliente_desde' => $parseDate($get($row, 'cliente_desde')),
                'dataabertura' => $parseDate($get($row, 'dataabertura')),
                'faturamento' => is_numeric($get($row, 'faturamento')) ? (float) $get($row, 'faturamento') : null,
                'servico' => $get($row, 'servico') ?: null,
                'honorario' => is_numeric($get($row, 'honorario')) ? (float) $get($row, 'honorario') : null,
            ]);

            $importados++;
        }

        $msg = "Importação concluída: {$importados} cliente(s) importado(s)";
        if ($ignorados > 0) {
            $msg .= ", {$ignorados} ignorado(s) por CPF/CNPJ duplicado";
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
            'dataabertura', 'faturamento', 'servico', 'honorario', 'fator_r',
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
            '15/03/2010', '50000', 'Contabilidade', '800', 'Não',
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
}
