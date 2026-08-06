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
 */
class CofreFiscalController extends Controller
{
    // Limite de documentos por zip em lote — protege contra memória/tempo em filtros muito amplos.
    const MAX_ZIP = 500;

    public function index(Request $request): View
    {
        $clientes = Cliente::orderBy('nome')->get(['id', 'nome', 'cpfcnpj']);

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

        $maxZip = self::MAX_ZIP;

        return view('cofre-fiscal.index', compact('documentos', 'clientes', 'maxZip'));
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

        return response($documento->xml_content, 200, [
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
                $zip->addFromString("{$documento->tipo}_{$documento->chave_acesso}.xml", $documento->xml_content);
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
     * Filtros comuns à listagem e ao export em zip.
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

        if ($request->filled('data_inicio')) {
            $query->whereDate('data_emissao', '>=', $request->input('data_inicio'));
        }

        if ($request->filled('data_fim')) {
            $query->whereDate('data_emissao', '<=', $request->input('data_fim'));
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
