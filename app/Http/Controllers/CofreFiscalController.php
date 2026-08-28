<?php

namespace App\Http\Controllers;

use App\Exports\NfeRelatorioExport;
use App\Models\Cliente;
use App\Models\DocumentoFiscal;
use App\Services\NfeXmlParser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use NFePHP\DA\CTe\Dacte;
use NFePHP\DA\NFe\Danfce;
use NFePHP\DA\NFe\Danfe;
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

    // Limite de XMLs por .zip enviado manualmente — mesmo racional do MAX_ZIP, mas pro sentido
    // inverso (upload em vez de download).
    const MAX_UPLOAD_XMLS = 2000;

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

        // Compartilhado com a view independente do nível — o modal de upload de XMLs
        // fica disponível em qualquer pasta do Cofre, não só na raiz (clientes).
        view()->share('clientesUpload', Cliente::where('status', 'ativo')->orderBy('nome')->get(['id', 'nome']));

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
            ->paginate(25)
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
     * Gera um .zip com os PDFs (DANFE/DACTE) de todos os documentos que batem
     * com os filtros atuais, montando cada PDF localmente a partir do
     * xml_content via nfephp-org/sped-da — mesmo esquema de downloadZip(),
     * trocando o conteúdo do XML pelo PDF renderizado. Documentos cujo XML
     * ainda está em formato resumido não geram DANFE completo; são pulados
     * e contados em $falhas em vez de derrubar o zip inteiro.
     */
    public function downloadZipPdfs(Request $request)
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(600);

        $zipPath = storage_path('app/temp/cofre_pdfs_' . time() . '_' . rand(1000, 9999) . '.zip');

        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return response()->json(['error' => 'Não foi possível criar o arquivo ZIP.'], 500);
        }

        $total = 0;
        $falhas = 0;

        $this->filtrar($request)
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
                    Log::warning('[Cofre Fiscal] downloadZipPdfs: falha ao gerar PDF', [
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

    /**
     * Importa XMLs de NF-e/NFC-e/CT-e pra dentro do Cofre a partir de um .zip enviado
     * manualmente pro cliente selecionado — usado quando a nota não veio pela
     * distribuição DFe (nacional/RS) automática, ex.: XMLs recebidos de outra
     * contabilidade na migração de um cliente. Só .zip é suportado (sem .rar: o
     * servidor não tem unrar/7z instalado, nem a extensão PHP correspondente).
     *
     * Cada entrada .xml do zip é lida com NfeXmlParser::extrairMetadados() e gravada
     * via updateOrCreate por chave_acesso — mesmo padrão de idempotência usado por
     * NfeService::persistir/CteDistribuicaoDFeService::persistir, então reenviar o
     * mesmo zip não duplica nada. Documentos cuja chave já pertence a OUTRO cliente
     * são pulados (não reatribuídos), pra não corromper a pasta de outro cliente por
     * upload no cliente errado.
     */
    public function uploadZip(Request $request)
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(300);

        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'arquivo' => 'required|file|max:102400', // 100MB
        ]);

        $arquivo = $request->file('arquivo');

        if (strtolower($arquivo->getClientOriginalExtension()) !== 'zip') {
            return response()->json(['error' => 'Envie um arquivo .zip. Outros formatos (ex.: .rar) não são suportados — compacte os XMLs em .zip antes de enviar.'], 422);
        }

        $zip = new ZipArchive();
        $abriu = $zip->open($arquivo->getRealPath());

        if ($abriu !== true) {
            Log::warning('[Cofre Fiscal] uploadZip: falha ao abrir .zip', ['codigo_zip' => $abriu, 'arquivo_original' => $arquivo->getClientOriginalName()]);

            return response()->json(['error' => 'Não foi possível abrir o arquivo .zip.'], 422);
        }

        Log::info('[Cofre Fiscal] uploadZip: zip aberto', ['arquivo_original' => $arquivo->getClientOriginalName(), 'num_files' => $zip->numFiles]);

        // (int) é necessário aqui: o valor chega como string do multipart/form-data, enquanto
        // $existente->cliente_id vem como int do Eloquent — sem o cast, a comparação abaixo
        // com !== (estrita) nunca bate, mesmo sendo o mesmo cliente.
        $clienteId = (int) $validated['cliente_id'];
        $importados = 0;
        $atualizados = 0;
        $ignoradosInvalidos = 0;
        $ignoradosOutroCliente = 0;
        $processados = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nome = $zip->getNameIndex($i);

            if ($nome === false || !str_ends_with(strtolower($nome), '.xml')) {
                continue;
            }

            if ($processados >= self::MAX_UPLOAD_XMLS) {
                $ignoradosInvalidos += $zip->numFiles - $i;
                break;
            }
            $processados++;

            $conteudo = $zip->getFromIndex($i);

            $meta = $conteudo !== false ? NfeXmlParser::extrairMetadados($conteudo) : null;

            if ($meta === null) {
                $ignoradosInvalidos++;
                Log::warning('[Cofre Fiscal] uploadZip: XML ignorado (não reconhecido)', [
                    'nome' => $nome,
                    'conteudo_leu' => $conteudo !== false,
                    'tamanho' => $conteudo !== false ? strlen($conteudo) : null,
                    'amostra' => $conteudo !== false ? substr($conteudo, 0, 300) : null,
                ]);
                continue;
            }

            $existente = DocumentoFiscal::where('chave_acesso', $meta['chaveAcesso'])->first();

            if ($existente && $existente->cliente_id !== $clienteId) {
                $ignoradosOutroCliente++;
                Log::warning('[Cofre Fiscal] uploadZip: XML ignorado (chave já pertence a outro cliente)', [
                    'chave_acesso' => $meta['chaveAcesso'],
                    'cliente_id_selecionado' => $clienteId,
                    'cliente_id_existente' => $existente->cliente_id,
                ]);
                continue;
            }

            DocumentoFiscal::updateOrCreate(
                ['chave_acesso' => $meta['chaveAcesso']],
                [
                    'cliente_id'         => $clienteId,
                    'tipo'               => $meta['tipo'],
                    'origem'             => $existente->origem ?? 'manual',
                    'numero'             => $meta['numero'] ?: null,
                    'data_emissao'       => !empty($meta['dataEmissao']) ? substr($meta['dataEmissao'], 0, 10) : null,
                    'data_saida_entrada' => !empty($meta['dataSaidaEntrada']) ? substr($meta['dataSaidaEntrada'], 0, 10) : null,
                    'emitente_nome'      => $meta['emitenteNome'],
                    'emitente_doc'       => $meta['emitenteDoc'] ?: null,
                    'valor'              => $meta['valor'] ?: null,
                    'situacao'           => $existente?->situacao === 'cancelada' ? 'cancelada' : ($meta['situacao'] ?? null),
                    'tp_nf'              => $meta['tpNf'] ?? $existente?->tp_nf,
                    'xml_content'        => $meta['xmlContent'],
                ]
            );

            $existente ? $atualizados++ : $importados++;
        }

        $zip->close();

        if ($importados === 0 && $atualizados === 0) {
            if ($ignoradosOutroCliente > 0 && $ignoradosInvalidos === 0) {
                return response()->json(['error' => "Todos os {$ignoradosOutroCliente} XML(s) reconhecidos já pertencem a OUTRO cliente no Cofre — nada foi importado pra não corromper a pasta dele. Confira se selecionou a empresa certa."], 422);
            }

            return response()->json(['error' => 'Nenhum XML de NF-e/NFC-e/CT-e válido foi encontrado no .zip.'], 422);
        }

        return response()->json([
            'importados' => $importados,
            'atualizados' => $atualizados,
            'ignorados' => $ignoradosInvalidos + $ignoradosOutroCliente,
            'ignorados_outro_cliente' => $ignoradosOutroCliente,
        ]);
    }

    /**
     * Gera o relatório mensal em Excel (NF-e/NFC-e com IBS/CBS por item, CT-e com
     * IBS/CBS a nível de documento) para o Cliente/Ano/Mês da pasta atual, restrito
     * aos tipos de nota marcados em `tipos[]`. Mesmo padrão de resposta (stream do
     * .xlsx, erro em JSON) usado por NfeController::exportarRelatorio.
     */
    public function exportarRelatorio(Request $request)
    {
        // Clientes com muitos documentos passam do memory_limit/max_execution_time padrão
        // só juntando as linhas + gerando o xlsx — mesmo ajuste usado em downloadZip e em
        // NfeController::exportarRelatorio (mesmo padrão, controller diferente).
        @ini_set('memory_limit', '1024M');
        @set_time_limit(600);

        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'ano' => 'required|integer|digits:4',
            'mes' => 'required|integer|between:1,12',
            'tipos' => 'required|array|min:1',
            'tipos.*' => 'in:nfe,nfce,cte',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);
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
            Log::error('[Cofre Fiscal] exportarRelatorio: Throwable inesperado', ['msg' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString()]);

            return response()->json(['error' => 'Erro inesperado ao gerar o relatório: '.$e->getMessage()], 500);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function linhasRelatorio(int $clienteId, string $tipo, string $dataInicio, string $dataFim): array
    {
        $linhas = [];

        foreach (DocumentoFiscal::queryPeriodo($clienteId, $tipo, $dataInicio, $dataFim)->cursor() as $documento) {
            array_push($linhas, ...NfeXmlParser::paraRelatorio($documento));
        }

        return $linhas;
    }

    /** @return array<int, array<string, mixed>> */
    private function linhasRelatorioCte(int $clienteId, string $dataInicio, string $dataFim): array
    {
        $linhas = [];

        foreach (DocumentoFiscal::queryPeriodo($clienteId, 'cte', $dataInicio, $dataFim)->cursor() as $documento) {
            array_push($linhas, ...NfeXmlParser::paraRelatorioCte($documento));
        }

        return $linhas;
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
