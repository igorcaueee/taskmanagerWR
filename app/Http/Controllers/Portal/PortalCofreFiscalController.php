<?php

namespace App\Http\Controllers\Portal;

use App\Exports\NfeRelatorioExport;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\DocumentoFiscal;
use App\Models\PortalUsuario;
use App\Services\NfeXmlParser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use NFePHP\DA\CTe\Dacte;
use NFePHP\DA\NFe\Danfce;
use NFePHP\DA\NFe\Danfe;
use ZipArchive;

/**
 * Versão do Cofre Fiscal (ver CofreFiscalController) exposta ao próprio cliente
 * dentro do portal — mesma navegação em "pastas" (Ano → Mês → Tipo), mas sempre
 * restrita ao cliente autenticado no guard `portal`, sem o nível de escolha de
 * cliente nem as ações de upload/importação (essas continuam admin-only).
 */
class PortalCofreFiscalController extends Controller
{
    const MAX_ZIP = 500;

    const MESES = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];

    const LABEL_TIPO = ['nfe' => 'NF-e', 'nfce' => 'NFC-e', 'cte' => 'CT-e'];

    public function index(Request $request): View
    {
        $cliente = $this->clienteAtual();

        $ano = $request->integer('ano') ?: null;
        $mes = $ano ? ($request->integer('mes') ?: null) : null;
        $tipo = ($ano && $mes) ? $request->input('tipo') : null;
        $tipo = in_array($tipo, ['nfe', 'nfce', 'cte'], true) ? $tipo : null;

        $breadcrumbs = $this->montarBreadcrumbs($ano, $mes, $tipo);

        // Busca por número ou valor pula direto para a lista de documentos.
        if ($request->filled('busca') || ($ano && $mes && $tipo)) {
            return $this->nivelDocumentos($request, $cliente->id, $ano, $mes, $tipo, $breadcrumbs);
        }

        if ($ano && $mes) {
            return $this->nivelTipos($cliente->id, $ano, $mes, $breadcrumbs);
        }

        if ($ano) {
            return $this->nivelMeses($cliente->id, $ano, $breadcrumbs);
        }

        return $this->nivelAnos($cliente->id, $breadcrumbs);
    }

    private function clienteAtual(): Cliente
    {
        /** @var PortalUsuario $portalUsuario */
        $portalUsuario = Auth::guard('portal')->user();

        return $portalUsuario->cliente;
    }

    private function nivelAnos(int $clienteId, array $breadcrumbs): View
    {
        $anos = DocumentoFiscal::where('cliente_id', $clienteId)
            ->selectRaw('YEAR(data_emissao) as ano, COUNT(*) as total')
            ->whereNotNull('data_emissao')
            ->groupBy('ano')
            ->orderByDesc('ano')
            ->get();

        $pastas = $anos->map(fn ($row) => [
            'label' => (string) $row->ano,
            'sublabel' => null,
            'url' => route('portal.cofre.index', ['ano' => $row->ano]),
            'total' => $row->total,
            'icon_class' => 'text-yellow-500',
        ]);

        return view('portal.cofre.index', [
            'nivel' => 'anos',
            'pastas' => $pastas,
            'breadcrumbs' => $breadcrumbs,
            'urlVoltar' => null,
        ]);
    }

    private function nivelMeses(int $clienteId, int $ano, array $breadcrumbs): View
    {
        $meses = DocumentoFiscal::where('cliente_id', $clienteId)
            ->whereYear('data_emissao', $ano)
            ->selectRaw('MONTH(data_emissao) as mes, COUNT(*) as total')
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $pastas = $meses->map(fn ($row) => [
            'label' => self::MESES[$row->mes] ?? $row->mes,
            'sublabel' => null,
            'url' => route('portal.cofre.index', ['ano' => $ano, 'mes' => $row->mes]),
            'total' => $row->total,
            'icon_class' => 'text-yellow-500',
        ]);

        return view('portal.cofre.index', [
            'nivel' => 'meses',
            'pastas' => $pastas,
            'breadcrumbs' => $breadcrumbs,
            'urlVoltar' => route('portal.cofre.index'),
        ]);
    }

    private function nivelTipos(int $clienteId, int $ano, int $mes, array $breadcrumbs): View
    {
        $tipos = DocumentoFiscal::where('cliente_id', $clienteId)
            ->whereYear('data_emissao', $ano)
            ->whereMonth('data_emissao', $mes)
            ->selectRaw('tipo, COUNT(*) as total')
            ->groupBy('tipo')
            ->orderBy('tipo')
            ->get();

        $iconePorTipo = [
            'nfe' => 'text-blue-500',
            'nfce' => 'text-amber-500',
            'cte' => 'text-purple-500',
        ];

        $pastas = $tipos->map(fn ($row) => [
            'label' => self::LABEL_TIPO[$row->tipo] ?? $row->tipo,
            'sublabel' => null,
            'url' => route('portal.cofre.index', ['ano' => $ano, 'mes' => $mes, 'tipo' => $row->tipo]),
            'total' => $row->total,
            'icon_class' => $iconePorTipo[$row->tipo] ?? 'text-yellow-500',
        ]);

        return view('portal.cofre.index', [
            'nivel' => 'tipos',
            'pastas' => $pastas,
            'breadcrumbs' => $breadcrumbs,
            'urlVoltar' => route('portal.cofre.index', ['ano' => $ano]),
        ]);
    }

    private function nivelDocumentos(Request $request, int $clienteId, ?int $ano, ?int $mes, ?string $tipo, array $breadcrumbs): View
    {
        $documentos = $this->filtrar($request, $clienteId)
            ->select([
                'id', 'cliente_id', 'chave_acesso', 'tipo', 'origem', 'nsu',
                'numero', 'data_emissao', 'emitente_nome', 'emitente_doc',
                'valor', 'situacao', 'updated_at',
            ])
            ->orderByDesc('data_emissao')
            ->paginate(50)
            ->withQueryString();

        $urlVoltar = match (true) {
            $mes !== null => route('portal.cofre.index', ['ano' => $ano, 'mes' => $mes]),
            $ano !== null => route('portal.cofre.index', ['ano' => $ano]),
            default => route('portal.cofre.index'),
        };

        return view('portal.cofre.index', [
            'nivel' => 'documentos',
            'documentos' => $documentos,
            'maxZip' => self::MAX_ZIP,
            'breadcrumbs' => $breadcrumbs,
            'urlVoltar' => $urlVoltar,
        ]);
    }

    private function montarBreadcrumbs(?int $ano, ?int $mes, ?string $tipo): array
    {
        $crumbs = [];

        if ($ano) {
            $crumbs[] = [
                'label' => (string) $ano,
                'url' => ($mes || $tipo) ? route('portal.cofre.index', ['ano' => $ano]) : null,
            ];
        }

        if ($mes) {
            $crumbs[] = [
                'label' => self::MESES[$mes] ?? $mes,
                'url' => $tipo ? route('portal.cofre.index', ['ano' => $ano, 'mes' => $mes]) : null,
            ];
        }

        if ($tipo) {
            $crumbs[] = ['label' => self::LABEL_TIPO[$tipo] ?? $tipo, 'url' => null];
        }

        return $crumbs;
    }

    public function downloadXml(string $chaveAcesso)
    {
        $documento = DocumentoFiscal::where('chave_acesso', $chaveAcesso)
            ->where('cliente_id', $this->clienteAtual()->id)
            ->firstOrFail();

        if (empty($documento->xml_content)) {
            return response()->json(['error' => 'XML não disponível para este documento.'], 422);
        }

        return response(DocumentoFiscal::removerWrapperProc($documento->xml_content), 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $documento->tipo . '_' . $chaveAcesso . '.xml"',
        ]);
    }

    public function downloadZip(Request $request)
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(600);

        $clienteId = $this->clienteAtual()->id;
        $zipPath = storage_path('app/temp/portal_cofre_' . time() . '_' . rand(1000, 9999) . '.zip');

        if (! is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return response()->json(['error' => 'Não foi possível criar o arquivo ZIP.'], 500);
        }

        $total = 0;

        $this->filtrar($request, $clienteId)
            ->whereNotNull('xml_content')
            ->orderByDesc('data_emissao')
            ->limit(self::MAX_ZIP)
            ->select(['tipo', 'chave_acesso', 'xml_content'])
            ->cursor()
            ->each(function (DocumentoFiscal $documento) use ($zip, &$total) {
                $zip->addFromString(
                    "{$documento->tipo}_{$documento->chave_acesso}.xml",
                    DocumentoFiscal::removerWrapperProc($documento->xml_content)
                );
                $total++;
            });

        if ($total === 0) {
            $zip->close();
            @unlink($zipPath);

            return response()->json(['error' => 'Nenhum documento com XML disponível para os filtros atuais.'], 422);
        }

        if (! $zip->close()) {
            @unlink($zipPath);

            return response()->json(['error' => 'Falha ao finalizar o arquivo ZIP.'], 500);
        }

        return response()->download($zipPath, 'cofre-fiscal.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    public function downloadZipPdfs(Request $request)
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(600);

        $clienteId = $this->clienteAtual()->id;
        $zipPath = storage_path('app/temp/portal_cofre_pdfs_' . time() . '_' . rand(1000, 9999) . '.zip');

        if (! is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return response()->json(['error' => 'Não foi possível criar o arquivo ZIP.'], 500);
        }

        $total = 0;
        $falhas = 0;

        $this->filtrar($request, $clienteId)
            ->whereNotNull('xml_content')
            ->orderByDesc('data_emissao')
            ->limit(self::MAX_ZIP)
            ->select(['tipo', 'chave_acesso', 'xml_content'])
            ->cursor()
            ->each(function (DocumentoFiscal $documento) use ($zip, &$total, &$falhas) {
                try {
                    $gerador = match ($documento->tipo) {
                        'nfe' => new Danfe(DocumentoFiscal::adicionarCestNaDescricao($documento->xml_content)),
                        'nfce' => new Danfce(DocumentoFiscal::adicionarCestNaDescricao($documento->xml_content)),
                        'cte' => new Dacte($documento->xml_content),
                        default => throw new \RuntimeException("Tipo '{$documento->tipo}' não suporta PDF."),
                    };

                    $gerador->monta();
                    $pdf = $gerador->render();

                    $zip->addFromString("{$documento->tipo}_{$documento->chave_acesso}.pdf", $pdf);
                    $total++;
                } catch (\Throwable $e) {
                    $falhas++;
                    Log::warning('[Portal Cofre Fiscal] downloadZipPdfs: falha ao gerar PDF', [
                        'chave_acesso' => $documento->chave_acesso,
                        'tipo' => $documento->tipo,
                        'msg' => $e->getMessage(),
                    ]);
                }
            });

        if ($total === 0) {
            $zip->close();
            @unlink($zipPath);

            return response()->json(['error' => 'Nenhum PDF pôde ser gerado para os documentos filtrados.'], 422);
        }

        if (! $zip->close()) {
            @unlink($zipPath);

            return response()->json(['error' => 'Falha ao finalizar o arquivo ZIP.'], 500);
        }

        return response()->download($zipPath, 'cofre-fiscal-pdfs.zip', [
            'Content-Type' => 'application/zip',
            'X-Pdfs-Falhas' => (string) $falhas,
        ])->deleteFileAfterSend(true);
    }

    public function danfe(Request $request)
    {
        $validated = $request->validate([
            'chave_acesso' => 'required|string|size:44',
        ]);

        $documento = DocumentoFiscal::where('chave_acesso', $validated['chave_acesso'])
            ->where('cliente_id', $this->clienteAtual()->id)
            ->first();

        if (! $documento || empty($documento->xml_content)) {
            return response()->json(['error' => 'XML deste documento não está disponível para gerar o PDF.'], 422);
        }

        try {
            $gerador = match ($documento->tipo) {
                'nfe' => new Danfe(DocumentoFiscal::adicionarCestNaDescricao($documento->xml_content)),
                'nfce' => new Danfce(DocumentoFiscal::adicionarCestNaDescricao($documento->xml_content)),
                'cte' => new Dacte($documento->xml_content),
                default => throw new \RuntimeException("Tipo de documento '{$documento->tipo}' não suporta geração de PDF."),
            };

            $gerador->monta();
            $pdf = $gerador->render();
        } catch (\Throwable $e) {
            Log::warning('[Portal Cofre Fiscal] danfe: falha ao gerar PDF', [
                'chave_acesso' => $validated['chave_acesso'],
                'tipo' => $documento->tipo,
                'msg' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Não foi possível gerar o PDF deste documento — provavelmente o XML completo ainda '
                    . 'não está disponível. Detalhe técnico: ' . $e->getMessage(),
            ], 422);
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $validated['chave_acesso'] . '.pdf"',
        ]);
    }

    public function exportarRelatorio(Request $request)
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(600);

        $validated = $request->validate([
            'ano' => 'required|integer|digits:4',
            'mes' => 'required|integer|between:1,12',
            'tipos' => 'required|array|min:1',
            'tipos.*' => 'in:nfe,nfce,cte',
        ]);

        $cliente = $this->clienteAtual();
        $tipos = $validated['tipos'];

        $dataInicio = sprintf('%04d-%02d-01', $validated['ano'], $validated['mes']);
        $dataFim = date('Y-m-t', strtotime($dataInicio));

        try {
            $linhasNf = in_array('nfe', $tipos, true)
                ? $this->linhasRelatorio($cliente->id, 'nfe', $dataInicio, $dataFim)
                : null;

            $linhasNfc = in_array('nfce', $tipos, true)
                ? $this->linhasRelatorio($cliente->id, 'nfce', $dataInicio, $dataFim)
                : null;

            $linhasCte = in_array('cte', $tipos, true)
                ? $this->linhasRelatorioCte($cliente->id, $dataInicio, $dataFim)
                : null;

            $mesLabel = self::MESES[$validated['mes']] ?? $validated['mes'];
            $nomeCliente = preg_replace('/[^A-Za-z0-9_-]+/', '_', $cliente->nome);
            $nomeArquivo = "Relatorio_Cofre_{$mesLabel}_{$validated['ano']}_{$nomeCliente}.xlsx";

            return (new NfeRelatorioExport($linhasNf, $linhasNfc, $linhasCte))->download($nomeArquivo);
        } catch (\Throwable $e) {
            Log::error('[Portal Cofre Fiscal] exportarRelatorio: Throwable inesperado', ['msg' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString()]);

            return response()->json(['error' => 'Erro inesperado ao gerar o relatório: ' . $e->getMessage()], 500);
        }
    }

    /** @return \Generator<array<string, mixed>> */
    private function linhasRelatorio(int $clienteId, string $tipo, string $dataInicio, string $dataFim): \Generator
    {
        foreach (DocumentoFiscal::queryPeriodo($clienteId, $tipo, $dataInicio, $dataFim)->cursor() as $documento) {
            yield from NfeXmlParser::paraRelatorio($documento);
        }
    }

    /** @return \Generator<array<string, mixed>> */
    private function linhasRelatorioCte(int $clienteId, string $dataInicio, string $dataFim): \Generator
    {
        foreach (DocumentoFiscal::queryPeriodo($clienteId, 'cte', $dataInicio, $dataFim)->cursor() as $documento) {
            yield from NfeXmlParser::paraRelatorioCte($documento);
        }
    }

    /**
     * Filtros dentro da pasta Ano/Mês/Tipo — sempre restrito ao cliente logado
     * no portal (nunca recebe cliente_id do request, ao contrário do admin).
     */
    private function filtrar(Request $request, int $clienteId): Builder
    {
        $query = DocumentoFiscal::query()->where('cliente_id', $clienteId);

        $cnpj = preg_replace('/[.\-\/\s]/', '', $this->clienteAtual()->cpfcnpj ?? '');

        if ($cnpj !== '') {
            DocumentoFiscal::filtrarEstabelecimento($query, $cnpj);
        }

        if ($request->filled('direcao') && $cnpj !== '') {
            $request->input('direcao') === 'saida'
                ? $query->where('emitente_doc', $cnpj)
                : $query->where('emitente_doc', '!=', $cnpj);
        }

        if ($request->filled('ano')) {
            $query->whereYear('data_emissao', $request->integer('ano'));
        }

        if ($request->filled('mes')) {
            $query->whereMonth('data_emissao', $request->integer('mes'));
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        if ($request->filled('origem')) {
            $query->where('origem', $request->input('origem'));
        }

        if ($request->input('situacao') === 'cancelada') {
            $query->where('situacao', 'cancelada');
        } elseif ($request->input('situacao') === 'normal') {
            $query->where(function (Builder $q) {
                $q->whereNull('situacao')->orWhere('situacao', '!=', 'cancelada');
            });
        }

        if ($request->filled('busca')) {
            $termo = trim((string) $request->string('busca'));
            $busca = '%' . $termo . '%';
            $buscaValor = '%' . str_replace(',', '.', $termo) . '%';

            $query->where(function (Builder $q) use ($busca, $buscaValor) {
                $q->where('chave_acesso', 'like', $busca)
                    ->orWhere('numero', 'like', $busca)
                    ->orWhere('emitente_nome', 'like', $busca)
                    ->orWhere('valor', 'like', $buscaValor);
            });
        }

        return $query;
    }
}
