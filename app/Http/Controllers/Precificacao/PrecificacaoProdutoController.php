<?php

namespace App\Http\Controllers\Precificacao;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\PrecificacaoCenario;
use App\Models\PrecificacaoProduto;
use App\Services\Precificacao\PrecificacaoCalculoService;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PrecificacaoProdutoController extends Controller
{
    public function index(Request $request): View
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $cliente = null;
        $resumo = collect();
        $produtos = null;

        if ($request->filled('cliente_id')) {
            $cliente = Cliente::findOrFail($request->integer('cliente_id'));

            $query = PrecificacaoProduto::where('cliente_id', $cliente->id)->orderBy('nome');

            if ($request->filled('busca')) {
                $busca = '%'.$request->string('busca').'%';
                $query->where(function ($q) use ($busca) {
                    $q->where('nome', 'like', $busca)
                        ->orWhere('ncm', 'like', $busca)
                        ->orWhere('cest', 'like', $busca);
                });
            }

            $produtos = $query->with('cenarios')->paginate(25)->withQueryString();
            $service = new PrecificacaoCalculoService;

            $resumo = $produtos->getCollection()->map(function (PrecificacaoProduto $produto) use ($service) {
                $cenarioPrincipal = $produto->cenarios->first();

                return [
                    'produto' => $produto,
                    'cenario' => $cenarioPrincipal,
                    'resultado' => $cenarioPrincipal ? $service->calcular($cenarioPrincipal) : null,
                ];
            });
        }

        return view('precificacao.produtos.index', compact('cliente', 'resumo', 'produtos'));
    }

    public function show(int $id): View
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $produto = PrecificacaoProduto::with(['cenarios', 'cliente'])->findOrFail($id);

        $service = new PrecificacaoCalculoService;
        $resultados = $produto->cenarios->mapWithKeys(fn (PrecificacaoCenario $cenario) => [
            $cenario->id => $service->calcular($cenario),
        ]);

        return view('precificacao.produtos.show', compact('produto', 'resultados'));
    }

    public function formCreateProduto(Request $request): View
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $cliente = Cliente::findOrFail($request->integer('cliente_id'));

        return view('precificacao.produtos.partials.formProduto', ['produto' => null, 'cliente' => $cliente]);
    }

    public function formEditProduto(int $id): View
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $produto = PrecificacaoProduto::with('cliente')->findOrFail($id);

        return view('precificacao.produtos.partials.formProduto', ['produto' => $produto, 'cliente' => $produto->cliente]);
    }

    public function saveProduto(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $clienteId = $request->integer('cliente_id');
        Cliente::findOrFail($clienteId);

        $validator = $this->validarProduto($request);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        PrecificacaoProduto::create(array_merge($validator->validated(), [
            'cliente_id' => $clienteId,
            'ativo' => true,
        ]));

        return Redirect::route('precificacao.produtos', ['cliente_id' => $clienteId])->with('success', 'Produto criado com sucesso.');
    }

    public function updateProduto(Request $request, int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $produto = PrecificacaoProduto::findOrFail($id);

        $validator = $this->validarProduto($request);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $produto->update($validator->validated());

        return Redirect::route('precificacao.produtos.show', $produto->id)->with('success', 'Produto atualizado com sucesso.');
    }

    public function deleteProduto(int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $produto = PrecificacaoProduto::findOrFail($id);
        $clienteId = $produto->cliente_id;
        $produto->delete();

        return Redirect::route('precificacao.produtos', ['cliente_id' => $clienteId])->with('success', 'Produto excluído com sucesso.');
    }

    public function formCreateCenario(int $produtoId): View
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $produto = PrecificacaoProduto::with('cliente')->findOrFail($produtoId);

        return view('precificacao.produtos.partials.formCenario', ['produto' => $produto, 'cenario' => null]);
    }

    public function formEditCenario(int $produtoId, int $id): View
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $produto = PrecificacaoProduto::with('cliente')->findOrFail($produtoId);
        $cenario = $produto->cenarios()->findOrFail($id);

        return view('precificacao.produtos.partials.formCenario', compact('produto', 'cenario'));
    }

    public function saveCenario(Request $request, int $produtoId): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $produto = PrecificacaoProduto::findOrFail($produtoId);

        $validator = $this->validarCenario($request);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $produto->cenarios()->create($validator->validated());

        return Redirect::route('precificacao.produtos.show', $produto->id)->with('success', 'Cenário criado com sucesso.');
    }

    public function updateCenario(Request $request, int $produtoId, int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $produto = PrecificacaoProduto::findOrFail($produtoId);
        $cenario = $produto->cenarios()->findOrFail($id);

        $validator = $this->validarCenario($request);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $cenario->update($validator->validated());

        return Redirect::route('precificacao.produtos.show', $produto->id)->with('success', 'Cenário atualizado com sucesso.');
    }

    public function deleteCenario(int $produtoId, int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $produto = PrecificacaoProduto::findOrFail($produtoId);
        $produto->cenarios()->findOrFail($id)->delete();

        return Redirect::route('precificacao.produtos.show', $produto->id)->with('success', 'Cenário excluído com sucesso.');
    }

    public function calcularPreview(Request $request, int $produtoId): JsonResponse
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $produto = PrecificacaoProduto::findOrFail($produtoId);

        $validator = $this->validarCenario($request);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $cenario = new PrecificacaoCenario($validator->validated());
        $cenario->setRelation('produto', $produto);

        $resultado = (new PrecificacaoCalculoService)->calcular($cenario);

        return response()->json($resultado->toArray());
    }

    public function formImportProdutos(Request $request): View
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $cliente = Cliente::findOrFail($request->integer('cliente_id'));

        return view('precificacao.produtos.partials.formImport', compact('cliente'));
    }

    public function importProdutos(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $clienteId = $request->integer('cliente_id');
        $cliente = Cliente::findOrFail($clienteId);

        $request->validate(['arquivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120']]);

        $spreadsheet = IOFactory::load($request->file('arquivo')->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();

        $rows = [];
        foreach ($sheet->getRowIterator() as $row) {
            $rowData = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }
            $rows[] = $rowData;
        }

        if (empty($rows)) {
            return Redirect::route('precificacao.produtos', ['cliente_id' => $clienteId])->with('error', 'Arquivo vazio.');
        }

        $header = array_map(fn ($v) => mb_strtolower(trim((string) $v)), $rows[0]);
        $colIndex = array_flip($header);

        $get = fn ($row, $col) => isset($colIndex[$col]) ? trim((string) ($row[$colIndex[$col]] ?? '')) : '';

        $importados = 0;

        foreach (array_slice($rows, 1) as $row) {
            $nome = $get($row, 'nome');
            $ncm = $get($row, 'ncm');
            if ($nome === '' || $ncm === '') {
                continue;
            }

            PrecificacaoProduto::create([
                'cliente_id' => $cliente->id,
                'nome' => $nome,
                'ncm' => $ncm,
                'cest' => $get($row, 'cest') ?: null,
                'unidade' => $get($row, 'unidade') ?: null,
                'codigo_interno' => $get($row, 'codigo') ?: null,
                'ativo' => true,
            ]);

            $importados++;
        }

        return Redirect::route('precificacao.produtos', ['cliente_id' => $clienteId])
            ->with('success', "Importação concluída: {$importados} produto(s) importado(s).");
    }

    public function exportCenarios(Request $request): Response
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $cliente = Cliente::findOrFail($request->integer('cliente_id'));
        $produtos = PrecificacaoProduto::where('cliente_id', $cliente->id)->with('cenarios')->get();
        $service = new PrecificacaoCalculoService;

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cenarios');

        $columns = [
            'Produto', 'NCM', 'CEST', 'UF Compra', 'UF Venda', 'Tipo ICMS Compra', 'Alíquota ICMS Compra (%)',
            'Compra Internacional', 'Valor Compra', 'Quantidade', 'Custo Unitário', 'Preço Venda',
            'Margem R$', 'Margem %',
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

        $linha = 2;
        foreach ($produtos as $produto) {
            foreach ($produto->cenarios as $cenario) {
                $r = $service->calcular($cenario);

                $sheet->fromArray([
                    $produto->nome,
                    $produto->ncm,
                    $produto->cest,
                    $cenario->uf_compra,
                    $cenario->uf_venda,
                    $cenario->tipo_icms_compra === 'st' ? 'ST' : 'Normal',
                    (float) $cenario->aliquota_icms_compra_pct,
                    $cenario->compra_internacional ? 'Sim' : 'Não',
                    (float) $cenario->valor_compra_total,
                    (float) $cenario->quantidade,
                    round($r->custoUnitario, 2),
                    round($r->precoVenda, 2),
                    round($r->margemContribuicaoValor, 2),
                    round($r->margemContribuicaoPercentual, 2),
                ], null, 'A'.$linha);

                $linha++;
            }
        }

        $writer = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="relatorio-precificacao-'.\Illuminate\Support\Str::slug($cliente->nome).'.xlsx"',
        ]);
    }

    public function templateProdutos(): Response
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Produtos');

        $columns = ['codigo', 'nome', 'ncm', 'cest', 'unidade'];

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

        $examples = ['COD001', 'Grampo dentadura banco', '87082999', '0199900', 'UN'];

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
            'Content-Disposition' => 'attachment; filename="modelo-importacao-produtos.xlsx"',
        ]);
    }

    private function validarProduto(Request $request): ValidatorContract
    {
        return Validator::make($request->only(['nome', 'ncm', 'cest', 'unidade', 'codigo_interno']), [
            'nome' => ['required', 'string', 'max:255'],
            'ncm' => ['required', 'string', 'max:20'],
            'cest' => ['nullable', 'string', 'max:20'],
            'unidade' => ['nullable', 'string', 'max:10'],
            'codigo_interno' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function validarCenario(Request $request): ValidatorContract
    {
        $request->merge([
            'uf_compra' => mb_strtoupper((string) $request->input('uf_compra')),
            'uf_venda' => mb_strtoupper((string) $request->input('uf_venda')),
            'compra_internacional' => $request->boolean('compra_internacional'),
        ]);

        return Validator::make($request->only([
            'nome', 'uf_compra', 'uf_venda', 'tipo_icms_compra', 'aliquota_icms_compra_pct', 'compra_internacional',
            'valor_compra_total', 'quantidade',
            'frete_compra', 'ipi_pct', 'markup_pct', 'comissao_pct', 'frete_venda_pct',
        ]), [
            'nome' => ['nullable', 'string', 'max:255'],
            'uf_compra' => ['required', 'string', 'size:2'],
            'uf_venda' => ['required', 'string', 'size:2'],
            'tipo_icms_compra' => ['required', 'in:st,normal'],
            'aliquota_icms_compra_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'compra_internacional' => ['boolean'],
            'valor_compra_total' => ['required', 'numeric', 'min:0.01'],
            'quantidade' => ['required', 'numeric', 'min:0.001'],
            'frete_compra' => ['nullable', 'numeric', 'min:0'],
            'ipi_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'markup_pct' => ['required', 'numeric', 'min:0'],
            'comissao_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'frete_venda_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
    }
}
