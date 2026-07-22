<?php

namespace App\Http\Controllers\Precificacao;

use App\Http\Controllers\Controller;
use App\Models\PrecificacaoAliquota;
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

class PrecificacaoAliquotaController extends Controller
{
    public function index(Request $request): View
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $query = PrecificacaoAliquota::orderBy('ncm')->orderBy('cest');

        if ($request->filled('busca')) {
            $busca = '%'.$request->string('busca').'%';
            $query->where(function ($q) use ($busca) {
                $q->where('ncm', 'like', $busca)
                    ->orWhere('cest', 'like', $busca)
                    ->orWhere('descricao', 'like', $busca);
            });
        }

        $aliquotas = $query->paginate(30)->withQueryString();

        return view('precificacao.aliquotas.home', compact('aliquotas'));
    }

    public function formCreate(): View
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        return view('precificacao.aliquotas.partials.form', ['aliquota' => null]);
    }

    public function formEdit(int $id): View
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $aliquota = PrecificacaoAliquota::findOrFail($id);

        return view('precificacao.aliquotas.partials.form', compact('aliquota'));
    }

    public function save(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        [$data, $validator] = $this->validarDados($request);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        PrecificacaoAliquota::create($data);

        return Redirect::route('precificacao.aliquotas')->with('success', 'Alíquota criada com sucesso.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $aliquota = PrecificacaoAliquota::findOrFail($id);

        [$data, $validator] = $this->validarDados($request);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $aliquota->update($data);

        return Redirect::route('precificacao.aliquotas')->with('success', 'Alíquota atualizada com sucesso.');
    }

    public function delete(int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        PrecificacaoAliquota::findOrFail($id)->delete();

        return Redirect::route('precificacao.aliquotas')->with('success', 'Alíquota excluída com sucesso.');
    }

    public function formImport(): View
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        return view('precificacao.aliquotas.partials.formImport');
    }

    public function import(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

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
            return Redirect::route('precificacao.aliquotas')->with('error', 'Arquivo vazio.');
        }

        $header = array_map(fn ($v) => mb_strtolower(trim((string) $v)), $rows[0]);
        $colIndex = array_flip($header);

        $get = fn ($row, $col) => isset($colIndex[$col]) ? trim((string) ($row[$colIndex[$col]] ?? '')) : '';
        $getDecimal = function ($row, $col) use ($get) {
            $raw = str_replace(',', '.', $get($row, $col));

            return is_numeric($raw) ? (float) $raw : 0.0;
        };
        $getBool = function ($row, $col) use ($get) {
            return in_array(mb_strtolower($get($row, $col)), ['sim', 'yes', '1', 'true']);
        };

        $importados = 0;
        $atualizados = 0;

        foreach (array_slice($rows, 1) as $row) {
            $ncm = $get($row, 'ncm');
            if ($ncm === '') {
                continue;
            }

            $cest = $get($row, 'cest') ?: null;
            $ufReferencia = mb_strtoupper($get($row, 'uf_referencia')) ?: null;

            $regimeRaw = mb_strtolower($get($row, 'regime_pis_cofins'));
            $regimePisCofins = str_contains($regimeRaw, 'mono') ? 'monofasico' : 'tributado';

            $dados = [
                'descricao' => $get($row, 'descricao') ?: null,
                'uf_referencia' => $ufReferencia,
                'aliquota_icms_interna' => $getDecimal($row, 'aliquota_icms_interna'),
                'aplica_st' => $getBool($row, 'aplica_st'),
                'aliquota_icms_st' => $getDecimal($row, 'aliquota_icms_st'),
                'regime_pis_cofins' => $regimePisCofins,
                'aliquota_pis' => $getDecimal($row, 'aliquota_pis'),
                'aliquota_cofins' => $getDecimal($row, 'aliquota_cofins'),
                'ativo' => true,
            ];

            $existente = PrecificacaoAliquota::where('ncm', $ncm)
                ->where('cest', $cest)
                ->where('uf_referencia', $ufReferencia)
                ->first();

            if ($existente) {
                $existente->update($dados);
                $atualizados++;
            } else {
                PrecificacaoAliquota::create(array_merge(['ncm' => $ncm, 'cest' => $cest], $dados));
                $importados++;
            }
        }

        $msg = "Importação concluída: {$importados} alíquota(s) nova(s), {$atualizados} atualizada(s).";

        return Redirect::route('precificacao.aliquotas')->with('success', $msg);
    }

    public function template(): Response
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Aliquotas');

        $columns = [
            'ncm', 'cest', 'descricao', 'uf_referencia',
            'aliquota_icms_interna', 'aplica_st', 'aliquota_icms_st',
            'regime_pis_cofins', 'aliquota_pis', 'aliquota_cofins',
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
            '87082999', '0199900', 'Grampo/bucha automotiva', 'MG',
            '18', 'Sim', '18',
            'Monofasico', '1.65', '7.6',
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
            'Content-Disposition' => 'attachment; filename="modelo-importacao-aliquotas.xlsx"',
        ]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: \Illuminate\Contracts\Validation\Validator}
     */
    private function validarDados(Request $request): array
    {
        $data = $request->only([
            'ncm', 'cest', 'descricao', 'uf_referencia',
            'aliquota_icms_interna', 'aplica_st', 'aliquota_icms_st',
            'regime_pis_cofins', 'aliquota_pis', 'aliquota_cofins', 'ativo',
        ]);

        $validator = Validator::make($data, [
            'ncm' => ['required', 'string', 'max:20'],
            'cest' => ['nullable', 'string', 'max:20'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'uf_referencia' => ['nullable', 'string', 'size:2'],
            'aliquota_icms_interna' => ['required', 'numeric', 'min:0', 'max:100'],
            'aplica_st' => ['nullable'],
            'aliquota_icms_st' => ['required', 'numeric', 'min:0', 'max:100'],
            'regime_pis_cofins' => ['required', 'in:monofasico,tributado'],
            'aliquota_pis' => ['required', 'numeric', 'min:0', 'max:100'],
            'aliquota_cofins' => ['required', 'numeric', 'min:0', 'max:100'],
            'ativo' => ['nullable'],
        ]);

        $data['aplica_st'] = isset($data['aplica_st']);
        $data['ativo'] = isset($data['ativo']);
        $data['uf_referencia'] = isset($data['uf_referencia']) ? mb_strtoupper($data['uf_referencia']) : null;

        return [$data, $validator];
    }
}
