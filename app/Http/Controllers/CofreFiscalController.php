<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\DocumentoFiscal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ZipArchive;

/**
 * Cofre de Notas Fiscais: navega diretamente sobre o que já foi sincronizado
 * em `documentos_fiscais` (NfeService/NfeIntegracaoRsService/CteIntegracaoRsService),
 * sem disparar nenhuma consulta à Sefaz — é só leitura do que já está salvo.
 *
 * A navegação é em estilo "pastas" (Cliente → Ano → Mês → Tipo), montada
 * virtualmente a partir de agrupamentos na própria tabela — não existem
 * diretórios reais em disco (ver FileExplorerController para o explorador
 * de arquivos de verdade, baseado em Storage::disk).
 */
class CofreFiscalController extends Controller
{
    // Limite de documentos por zip em lote — protege contra memória/tempo em filtros muito amplos.
    const MAX_ZIP = 500;

    const MESES = [
        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
    ];

    const LABEL_TIPO = ['nfe' => 'NF-e', 'nfce' => 'NFC-e', 'cte' => 'CT-e'];

    public function index(Request $request): View
    {
        $clienteId = $request->integer('cliente_id') ?: null;
        $ano = $request->integer('ano') ?: null;
        $mes = ($clienteId && $ano) ? ($request->integer('mes') ?: null) : null;
        $tipo = ($clienteId && $ano && $mes) ? $request->input('tipo') : null;
        $tipo = in_array($tipo, ['nfe', 'nfce', 'cte'], true) ? $tipo : null;

        $breadcrumbs = $this->montarBreadcrumbs($clienteId, $ano, $mes, $tipo);

        if ($clienteId && $ano && $mes && $tipo) {
            return $this->nivelDocumentos($request, $clienteId, $ano, $mes, $tipo, $breadcrumbs);
        }

        if ($clienteId && $ano && $mes) {
            return $this->nivelTipos($clienteId, $ano, $mes, $breadcrumbs);
        }

        if ($clienteId && $ano) {
            return $this->nivelMeses($clienteId, $ano, $breadcrumbs);
        }

        if ($clienteId) {
            return $this->nivelAnos($clienteId, $breadcrumbs);
        }

        return $this->nivelClientes($request, $breadcrumbs);
    }

    private function nivelClientes(Request $request, array $breadcrumbs): View
    {
        $pastasPaginadas = DocumentoFiscal::query()
            ->join('clientes', 'clientes.id', '=', 'documentos_fiscais.cliente_id')
            ->select('documentos_fiscais.cliente_id', 'clientes.nome', 'clientes.cpfcnpj')
            ->selectRaw('COUNT(*) as total')
            ->when($request->filled('busca'), fn (Builder $q) => $q->where('clientes.nome', 'like', '%'.$request->string('busca').'%'))
            ->groupBy('documentos_fiscais.cliente_id', 'clientes.nome', 'clientes.cpfcnpj')
            ->orderBy('clientes.nome')
            ->paginate(50)
            ->withQueryString();

        $pastas = $pastasPaginadas->getCollection()->map(fn ($row) => [
            'label' => $row->nome,
            'sublabel' => $row->cpfcnpj,
            'url' => route('cofre-fiscal.index', ['cliente_id' => $row->cliente_id]),
            'total' => $row->total,
            'icon_class' => 'text-sky-500',
        ]);

        return view('cofre-fiscal.index', [
            'nivel' => 'clientes',
            'pastas' => $pastas,
            'paginador' => $pastasPaginadas,
            'breadcrumbs' => $breadcrumbs,
            'urlVoltar' => null,
        ]);
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
            'url' => route('cofre-fiscal.index', ['cliente_id' => $clienteId, 'ano' => $row->ano]),
            'total' => $row->total,
            'icon_class' => 'text-yellow-500',
        ]);

        return view('cofre-fiscal.index', [
            'nivel' => 'anos',
            'pastas' => $pastas,
            'paginador' => null,
            'breadcrumbs' => $breadcrumbs,
            'urlVoltar' => route('cofre-fiscal.index'),
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
            'url' => route('cofre-fiscal.index', ['cliente_id' => $clienteId, 'ano' => $ano, 'mes' => $row->mes]),
            'total' => $row->total,
            'icon_class' => 'text-yellow-500',
        ]);

        return view('cofre-fiscal.index', [
            'nivel' => 'meses',
            'pastas' => $pastas,
            'paginador' => null,
            'breadcrumbs' => $breadcrumbs,
            'urlVoltar' => route('cofre-fiscal.index', ['cliente_id' => $clienteId]),
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
            'url' => route('cofre-fiscal.index', ['cliente_id' => $clienteId, 'ano' => $ano, 'mes' => $mes, 'tipo' => $row->tipo]),
            'total' => $row->total,
            'icon_class' => $iconePorTipo[$row->tipo] ?? 'text-yellow-500',
        ]);

        return view('cofre-fiscal.index', [
            'nivel' => 'tipos',
            'pastas' => $pastas,
            'paginador' => null,
            'breadcrumbs' => $breadcrumbs,
            'urlVoltar' => route('cofre-fiscal.index', ['cliente_id' => $clienteId, 'ano' => $ano]),
        ]);
    }

    private function nivelDocumentos(Request $request, int $clienteId, int $ano, int $mes, string $tipo, array $breadcrumbs): View
    {
        $documentos = $this->filtrar($request)
            ->select([
                'id', 'cliente_id', 'chave_acesso', 'tipo', 'origem', 'nsu',
                'numero', 'data_emissao', 'emitente_nome', 'emitente_doc',
                'valor', 'situacao', 'updated_at',
            ])
            ->with('cliente:id,nome')
            ->orderByDesc('data_emissao')
            ->paginate(50)
            ->withQueryString();

        return view('cofre-fiscal.index', [
            'nivel' => 'documentos',
            'documentos' => $documentos,
            'maxZip' => self::MAX_ZIP,
            'breadcrumbs' => $breadcrumbs,
            'urlVoltar' => route('cofre-fiscal.index', ['cliente_id' => $clienteId, 'ano' => $ano, 'mes' => $mes]),
        ]);
    }

    /**
     * Monta o rastro de navegação (Cliente → Ano → Mês → Tipo) — cada nível
     * já percorrido vira um link clicável para voltar direto a ele; o nível
     * atual (o último) não é clicável.
     */
    private function montarBreadcrumbs(?int $clienteId, ?int $ano, ?int $mes, ?string $tipo): array
    {
        $crumbs = [];

        if ($clienteId) {
            $nomeCliente = Cliente::find($clienteId)?->nome ?? 'Cliente';
            $crumbs[] = [
                'label' => $nomeCliente,
                'url' => ($ano || $mes || $tipo) ? route('cofre-fiscal.index', ['cliente_id' => $clienteId]) : null,
            ];
        }

        if ($ano) {
            $crumbs[] = [
                'label' => (string) $ano,
                'url' => ($mes || $tipo) ? route('cofre-fiscal.index', ['cliente_id' => $clienteId, 'ano' => $ano]) : null,
            ];
        }

        if ($mes) {
            $crumbs[] = [
                'label' => self::MESES[$mes] ?? $mes,
                'url' => $tipo ? route('cofre-fiscal.index', ['cliente_id' => $clienteId, 'ano' => $ano, 'mes' => $mes]) : null,
            ];
        }

        if ($tipo) {
            $crumbs[] = ['label' => self::LABEL_TIPO[$tipo] ?? $tipo, 'url' => null];
        }

        return $crumbs;
    }

    /**
     * Baixa o XML de um único documento (sob demanda — o XML não é carregado
     * na listagem paginada para não pesar a query).
     */
    public function downloadXml(string $chaveAcesso)
    {
        $documento = DocumentoFiscal::where('chave_acesso', $chaveAcesso)->firstOrFail();

        if (empty($documento->xml_content)) {
            return response()->json(['error' => 'XML não disponível para este documento.'], 422);
        }

        return response(DocumentoFiscal::removerWrapperProc($documento->xml_content), 200, [
            'Content-Type'        => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $documento->tipo . '_' . $chaveAcesso . '.xml"',
        ]);
    }

    /**
     * Gera um .zip com os XMLs de todos os documentos que batem com os filtros
     * atuais (não só a página exibida), até o limite de segurança MAX_ZIP.
     */
    public function downloadZip(Request $request)
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(600);

        $zipPath = storage_path('app/temp/cofre_' . time() . '_' . rand(1000, 9999) . '.zip');

        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return response()->json(['error' => 'Não foi possível criar o arquivo ZIP.'], 500);
        }

        // cursor() em vez de get(): com filtros amplos (milhares de docs) carregar o
        // xml_content de todo mundo numa Collection de uma vez estourava o memory_limit
        // padrão e corrompia o zip pela metade — ver mesmo fix em NfeController::downloadZipXmls.
        $total = 0;

        $this->filtrar($request)
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

    /**
     * Filtros comuns à listagem final (dentro da pasta Cliente/Ano/Mês/Tipo)
     * e ao export em zip — cliente_id/ano/mes/tipo definem a pasta atual;
     * direção, situação e busca continuam como filtros finos dentro dela.
     */
    private function filtrar(Request $request): Builder
    {
        $query = DocumentoFiscal::query();

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->integer('cliente_id'));

            if ($request->filled('direcao')) {
                $cnpj = preg_replace('/[.\-\/\s]/', '', Cliente::find($request->integer('cliente_id'))?->cpfcnpj ?? '');

                if ($cnpj !== '') {
                    $request->input('direcao') === 'saida'
                        ? $query->where('emitente_doc', $cnpj)
                        : $query->where('emitente_doc', '!=', $cnpj);
                }
            }
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
            $busca = '%' . $request->string('busca') . '%';
            $query->where(function (Builder $q) use ($busca) {
                $q->where('chave_acesso', 'like', $busca)
                    ->orWhere('numero', 'like', $busca)
                    ->orWhere('emitente_nome', 'like', $busca);
            });
        }

        return $query;
    }
}
