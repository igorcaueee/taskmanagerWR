<?php

namespace App\Http\Controllers;

use App\Exports\NfeRelatorioExport;
use App\Models\CertificadoContabilidade;
use App\Models\Cliente;
use App\Models\ClienteCertificadoNfse;
use App\Models\DocumentoFiscal;
use App\Models\SincronizacaoFiscalRs;
use App\Services\CteDistribuicaoDFeService;
use App\Services\CteIntegracaoRsService;
use App\Services\NfeIntegracaoRsService;
use App\Services\NfeService;
use App\Services\NfeXmlParser;
use App\Services\NfseService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use NFePHP\DA\CTe\Dacte;
use NFePHP\DA\NFe\Danfce;
use NFePHP\DA\NFe\Danfe;
use ZipArchive;

class NfeController extends Controller
{
    // Cada documento carrega o xml_content inteiro; buscar todos de um período
    // grande (milhares de docs) de uma vez estoura o memory_limit do PHP.
    private const DOCUMENTOS_POR_PAGINA = 500;

    public function __construct(
        private NfeService $nfe,
        private NfeIntegracaoRsService $nfeRs,
        private CteIntegracaoRsService $cteRs,
        private CteDistribuicaoDFeService $cteNacional,
        private NfseService $nfse,
    ) {}

    public function index()
    {
        $clientes = Cliente::where('status', 'ativo')
            ->orderBy('nome')
            ->get(['id', 'nome', 'cpfcnpj']);

        return view('nfe.index', compact('clientes'));
    }

    /**
     * Painel de acompanhamento do cron `fiscal:sincronizar-notas-rs` — mostra
     * o log gravado em `sincronizacoes_fiscais_rs` (uma linha por
     * cliente/fase/execução), com filtros por cliente e status.
     */
    public function telaSincronizacaoRs(Request $request)
    {
        abort_if(! auth()->user()?->canConfigurarCertificadoContabilidade(), 403);

        $sincronizacoes = SincronizacaoFiscalRs::with('cliente')
            ->when($request->filled('cliente_id'), fn ($q) => $q->where('cliente_id', $request->get('cliente_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->get('status')))
            ->orderByDesc('executado_em')
            ->paginate(30)
            ->withQueryString();

        $clientes = Cliente::where('status', 'ativo')
            ->where('importar_notas_fiscais', true)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return view('nfe.sincronizacao-rs', compact('sincronizacoes', 'clientes'));
    }

    /**
     * Sincroniza uma fatia (chunk) do certificado nacional. O front-end chama
     * esta rota repetidas vezes, passando 'nsu_inicio' = 'proximo_nsu' da
     * resposta anterior, até receber 'concluido' = true — cada chamada fica
     * curta o suficiente para não esbarrar no timeout de proxy/CDN (Cloudflare
     * derruba com HTTP 524 conexões que o backend demora demais pra responder).
     */
    public function sincronizarChunk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'nsu_inicio' => 'sometimes|integer|min:0',
        ]);

        $cert = ClienteCertificadoNfse::with('cliente')->where('cliente_id', $validated['cliente_id'])->first();

        if (! $cert) {
            return response()->json(['error' => 'Certificado digital não configurado para este cliente. Configure-o na tela de NFS-e antes de buscar.'], 422);
        }

        $nsuInicio = array_key_exists('nsu_inicio', $validated) ? (int) $validated['nsu_inicio'] : null;

        try {
            $resultado = $this->nfe->sincronizarChunk($cert, $nsuInicio);

            return response()->json([
                'success' => true,
                'concluido' => $resultado['concluido'],
                'proximo_nsu' => $resultado['proximoNsu'],
                'aviso' => $resultado['aviso'],
            ]);
        } catch (\Throwable $e) {
            Log::error('[NF-e] sincronizarChunk: Throwable inesperado', ['msg' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString()]);

            return response()->json(['error' => 'Erro inesperado: '.$e->getMessage()], 500);
        }
    }

    /**
     * Sincroniza uma fatia (chunk) de CT-e via CTeDistribuicaoDFe (nacional),
     * mesmo certificado do cliente usado em sincronizarChunk (NF-e) — ver
     * CteDistribuicaoDFeService quanto ao que esse serviço traz/não traz.
     */
    public function sincronizarChunkCte(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'nsu_inicio' => 'sometimes|integer|min:0',
        ]);

        $cert = ClienteCertificadoNfse::with('cliente')->where('cliente_id', $validated['cliente_id'])->first();

        if (! $cert) {
            return response()->json(['error' => 'Certificado digital não configurado para este cliente. Configure-o na tela de NFS-e antes de buscar.'], 422);
        }

        $nsuInicio = array_key_exists('nsu_inicio', $validated) ? (int) $validated['nsu_inicio'] : null;

        try {
            $resultado = $this->cteNacional->sincronizarChunk($cert, $nsuInicio);

            return response()->json([
                'success' => true,
                'concluido' => $resultado['concluido'],
                'proximo_nsu' => $resultado['proximoNsu'],
                'aviso' => $resultado['aviso'],
            ]);
        } catch (\Throwable $e) {
            Log::error('[CT-e Nacional] sincronizarChunk: Throwable inesperado', ['msg' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString()]);

            return response()->json(['error' => 'Erro inesperado: '.$e->getMessage()], 500);
        }
    }

    /**
     * Busca um único CT-e pela chave de acesso (via CTeIntegracao/SEFAZ-RS) e
     * salva no cofre — cobre o caso de um documento não vir na sincronização
     * sequencial por NSU mesmo estando dentro do período (ver
     * CteIntegracaoRsService::buscarPorChave).
     */
    public function buscarCtePorChave(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id'   => 'required|exists:clientes,id',
            'chave_acesso' => 'required|string',
        ]);

        $cert = CertificadoContabilidade::first();

        if (! $cert) {
            return response()->json(['error' => 'Certificado da contabilidade não configurado. Cadastre-o antes de buscar.'], 422);
        }

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        try {
            $resultado = $this->cteRs->buscarPorChave($cert, $cliente, $validated['chave_acesso']);

            return response()->json($resultado);
        } catch (\Throwable $e) {
            Log::error('[CT-e RS] buscarCtePorChave: Throwable inesperado', ['msg' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString()]);

            return response()->json(['error' => 'Erro inesperado: '.$e->getMessage()], 500);
        }
    }

    /**
     * Lê os documentos já sincronizados (tabela `documentos_fiscais`) para o
     * período informado — não consulta a Sefaz (ver sincronizarChunk).
     */
    public function buscar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'data_inicio' => 'required|date_format:Y-m-d',
            'data_fim' => 'required|date_format:Y-m-d|after_or_equal:data_inicio',
            'page' => 'sometimes|integer|min:1',
        ]);

        $page = (int) ($validated['page'] ?? 1);

        try {
            $resultado = DocumentoFiscal::doPeriodo(
                $validated['cliente_id'],
                ['nfe', 'cte'],
                $validated['data_inicio'],
                $validated['data_fim'],
                ['nacional'],
                $page,
                self::DOCUMENTOS_POR_PAGINA
            );

            $totalPaginas = max(1, (int) ceil($resultado['total'] / self::DOCUMENTOS_POR_PAGINA));

            Log::debug('[NF-e] buscar: página carregada', ['page' => $page, 'total_paginas' => $totalPaginas, 'total' => $resultado['total']]);

            $payload = [
                'success' => true,
                'total' => $resultado['total'],
                'documentos' => $resultado['documentos'],
                'pagina' => $page,
                'total_paginas' => $totalPaginas,
                'concluido' => $page >= $totalPaginas,
            ];
            $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($encoded === false) {
                Log::error('[NF-e] buscar: json_encode falhou', ['erro' => json_last_error_msg()]);

                return response()->json(['error' => 'Falha ao serializar resposta: '.json_last_error_msg()], 500);
            }

            return new JsonResponse($payload, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            Log::error('[NF-e] buscar: Throwable inesperado', ['msg' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString()]);

            return response()->json(['error' => 'Erro inesperado: '.$e->getMessage()], 500);
        }
    }

    /**
     * Gera o relatório fiscal de NF-e/NFC-e (Excel, uma linha por item) do período,
     * a partir dos documentos já sincronizados (tabela `documentos_fiscais`) — não
     * consulta a Sefaz. Inclui documentos de qualquer origem (nacional ou RS).
     *
     * O parâmetro `tipo` permite gerar o relatório apenas de NF-e, apenas de
     * NFC-e, ou ambos (padrão) — evitando processar o tipo que não interessa.
     * O parâmetro `direcao` (entrada/saída) segue a mesma lógica do filtro da
     * tela (ver DocumentoFiscal::direcao()).
     */
    public function exportarRelatorio(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'data_inicio' => 'required|date_format:Y-m-d',
            'data_fim' => 'required|date_format:Y-m-d|after_or_equal:data_inicio',
            'tipo' => 'nullable|in:nfe,nfce,ambos',
            'direcao' => 'nullable|in:entrada,saida',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);
        $tipo = $validated['tipo'] ?? 'ambos';
        $direcao = $validated['direcao'] ?? null;
        $clienteCnpj = preg_replace('/\D/', '', $cliente->cpfcnpj ?? '');

        try {
            $linhasNf = in_array($tipo, ['nfe', 'ambos'], true)
                ? $this->linhasRelatorio($cliente->id, 'nfe', $validated['data_inicio'], $validated['data_fim'], $direcao, $clienteCnpj)
                : null;

            $linhasNfc = in_array($tipo, ['nfce', 'ambos'], true)
                ? $this->linhasRelatorio($cliente->id, 'nfce', $validated['data_inicio'], $validated['data_fim'], $direcao, $clienteCnpj)
                : null;

            $sufixo = match ($tipo) {
                'nfe' => 'NFe',
                'nfce' => 'NFCe',
                default => 'NFe_NFCe',
            };

            $nomeArquivo = "Relatorio_{$sufixo}_".preg_replace('/[^A-Za-z0-9_-]+/', '_', $cliente->nome)
                ."_{$validated['data_inicio']}_a_{$validated['data_fim']}.xlsx";

            return (new NfeRelatorioExport($linhasNf, $linhasNfc))->download($nomeArquivo);
        } catch (\Throwable $e) {
            Log::error('[NF-e] exportarRelatorio: Throwable inesperado', ['msg' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString()]);

            return response()->json(['error' => 'Erro inesperado ao gerar o relatório: '.$e->getMessage()], 500);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function linhasRelatorio(int $clienteId, string $tipo, string $dataInicio, string $dataFim, ?string $direcao, string $clienteCnpj): array
    {
        $linhas = [];

        foreach (DocumentoFiscal::queryPeriodo($clienteId, $tipo, $dataInicio, $dataFim)->cursor() as $documento) {
            if ($direcao !== null && $documento->direcao($clienteCnpj) !== $direcao) {
                continue;
            }

            array_push($linhas, ...NfeXmlParser::paraRelatorio($documento));
        }

        return $linhas;
    }

    // ─── Certificado da contabilidade (webservice NFeIntegracao/RS) ───────────

    public function getCertificadoContabilidade(): JsonResponse
    {
        abort_if(! auth()->user()?->canConfigurarCertificadoContabilidade(), 403);

        $cert = CertificadoContabilidade::first();

        if (! $cert) {
            return response()->json(['configurado' => false]);
        }

        return response()->json([
            'configurado' => true,
            'arquivo_ok' => file_exists(storage_path('app/'.$cert->arquivo)),
            'ambiente' => $cert->ambiente,
            'vencimento' => $cert->vencimento?->format('d/m/Y'),
            'vencido' => $cert->vencido(),
            'alerta' => $cert->venceEm30Dias(),
        ]);
    }

    public function salvarCertificadoContabilidade(Request $request): JsonResponse
    {
        abort_if(! auth()->user()?->canConfigurarCertificadoContabilidade(), 403);

        $validated = $request->validate([
            'certificado' => 'required|file|max:10240',
            'senha' => 'required|string|min:1',
            'ambiente' => 'required|in:homologacao,producao',
        ]);

        $file = $request->file('certificado');

        try {
            $vencimento = $this->nfse->validarCertificado($file->getRealPath(), $validated['senha']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dir = 'nfe/certificados/contabilidade';
        $destPath = storage_path("app/{$dir}");

        if (! is_dir($destPath)) {
            mkdir($destPath, 0755, true);
        }

        $destFile = "{$destPath}/certificado.pfx";

        if (! copy($file->getRealPath(), $destFile)) {
            return response()->json(['error' => 'Falha ao salvar o arquivo do certificado no servidor.'], 500);
        }

        $cert = CertificadoContabilidade::first() ?? new CertificadoContabilidade;
        $cert->fill([
            'arquivo' => "{$dir}/certificado.pfx",
            'senha' => $validated['senha'],
            'ambiente' => $validated['ambiente'],
            'vencimento' => $vencimento,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Certificado da contabilidade salvo com sucesso!',
            'vencimento' => $vencimento ? Carbon::parse($vencimento)->format('d/m/Y') : null,
        ]);
    }

    // ─── Busca via webservice de contabilistas (SEFAZ-RS) ─────────────────────

    /**
     * Sincroniza uma fatia (chunk) de uma única fase (nfe|nfce|cte) via
     * contabilidade. O front-end percorre as três fases em sequência, uma de
     * cada vez até 'concluido'=true, antes de passar pra próxima — cada
     * chamada fica curta o suficiente para não esbarrar no timeout de
     * proxy/CDN, e evita rajada de requisições no mesmo certificado (que
     * compartilha "consumo indevido" entre todos os clientes).
     */
    public function sincronizarRsChunk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'fase' => 'required|in:nfe,nfce,cte',
            'nsu_inicio' => 'sometimes|integer|min:0',
        ]);

        $cert = CertificadoContabilidade::first();

        if (! $cert) {
            return response()->json(['error' => 'Certificado da contabilidade não configurado. Cadastre-o antes de buscar.'], 422);
        }

        $cliente = Cliente::findOrFail($validated['cliente_id']);
        $nsuInicio = array_key_exists('nsu_inicio', $validated) ? (int) $validated['nsu_inicio'] : null;

        try {
            $resultado = match ($validated['fase']) {
                'nfe' => $this->nfeRs->sincronizarChunk($cert, $cliente, NfeIntegracaoRsService::MOD_NFE, $nsuInicio),
                'nfce' => $this->nfeRs->sincronizarChunk($cert, $cliente, NfeIntegracaoRsService::MOD_NFCE, $nsuInicio),
                'cte' => $this->cteRs->sincronizarChunk($cert, $cliente, $nsuInicio),
            };

            return response()->json([
                'success' => true,
                'fase' => $validated['fase'],
                'concluido' => $resultado['concluido'],
                'proximo_nsu' => $resultado['proximoNsu'],
                'aviso' => $resultado['aviso'],
            ]);
        } catch (\Throwable $e) {
            Log::error('[NF-e RS] sincronizarRsChunk: Throwable inesperado', [
                'fase' => $validated['fase'], 'msg' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Erro inesperado: '.$e->getMessage()], 500);
        }
    }

    /**
     * Lê os documentos já sincronizados (tabela `documentos_fiscais`) para o
     * período informado — não consulta a Sefaz (ver sincronizarRsChunk).
     */
    public function buscarRs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'data_inicio' => 'required|date_format:Y-m-d',
            'data_fim' => 'required|date_format:Y-m-d|after_or_equal:data_inicio',
            'page' => 'sometimes|integer|min:1',
        ]);

        $page = (int) ($validated['page'] ?? 1);

        try {
            $resultado = DocumentoFiscal::doPeriodo(
                $validated['cliente_id'],
                ['nfe', 'nfce', 'cte'],
                $validated['data_inicio'],
                $validated['data_fim'],
                ['rs'],
                $page,
                self::DOCUMENTOS_POR_PAGINA
            );

            $totalPaginas = max(1, (int) ceil($resultado['total'] / self::DOCUMENTOS_POR_PAGINA));

            Log::debug('[NF-e RS] buscar: página carregada', ['page' => $page, 'total_paginas' => $totalPaginas, 'total' => $resultado['total']]);

            return new JsonResponse(
                [
                    'success' => true,
                    'total' => $resultado['total'],
                    'documentos' => $resultado['documentos'],
                    'pagina' => $page,
                    'total_paginas' => $totalPaginas,
                    'concluido' => $page >= $totalPaginas,
                ],
                200,
                [],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        } catch (\Throwable $e) {
            Log::error('[NF-e RS] buscar: Throwable inesperado', ['msg' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString()]);

            return response()->json(['error' => 'Erro inesperado: '.$e->getMessage()], 500);
        }
    }

    // Limite de chaves por requisição de zip — bem acima do que qualquer cliente tem de
    // notas num período, só como proteção contra payloads absurdos (ver CofreFiscalController::MAX_ZIP,
    // que tem um limite próprio menor por filtrar sem seleção manual do usuário).
    const MAX_ZIP = 20000;

    /**
     * Gera um .zip com os XMLs dos documentos selecionados, buscando o
     * xml_content direto do banco pela chave de acesso — o frontend manda só
     * as chaves, não o XML inteiro de cada nota. Antes o XML de cada documento
     * selecionado ia e voltava pela rede (frontend -> servidor), o que travava
     * a geração do zip com poucas centenas de notas por causa do tamanho do
     * payload (post_max_size) e de um limite artificial de 200 itens.
     *
     * Usa cursor() em vez de get(): com milhares de notas, carregar todo mundo
     * na memória de uma vez (uma Collection com o xml_content inteiro de cada
     * documento) estourava o memory_limit padrão do PHP e corrompia o zip pela metade.
     * Também eleva memory_limit/max_execution_time só para esta requisição — clientes
     * com 4000+ notas passam do limite padrão só de tempo de I/O do banco + zip.
     */
    public function downloadZipXmls(Request $request)
    {
        $request->validate([
            'chaves' => 'required|array|min:1|max:'.self::MAX_ZIP,
            'chaves.*' => 'required|string',
            'nome' => 'nullable|string',
        ]);

        @ini_set('memory_limit', '1024M');
        @set_time_limit(600);

        $nomeEmpresa = trim((string) $request->input('nome', ''));
        $nomeArquivo = ($nomeEmpresa !== '' ? $nomeEmpresa : 'NFe-CTe').'.zip';

        $zipPath = storage_path('app/temp/nfe_'.time().'_'.rand(1000, 9999).'.zip');

        if (! is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return response()->json(['error' => 'Não foi possível criar o arquivo ZIP.'], 500);
        }

        $total = 0;

        foreach (array_chunk($request->chaves, 500) as $chavesLote) {
            DocumentoFiscal::whereIn('chave_acesso', $chavesLote)
                ->whereNotNull('xml_content')
                ->select(['tipo', 'chave_acesso', 'xml_content'])
                ->cursor()
                ->each(function (DocumentoFiscal $documento) use ($zip, &$total) {
                    $zip->addFromString(
                        "{$documento->tipo}_{$documento->chave_acesso}.xml",
                        DocumentoFiscal::removerWrapperProc($documento->xml_content)
                    );
                    $total++;
                });
        }

        if ($total === 0) {
            $zip->close();
            @unlink($zipPath);

            return response()->json(['error' => 'Nenhum XML disponível para os documentos selecionados.'], 422);
        }

        if (! $zip->close()) {
            @unlink($zipPath);

            return response()->json(['error' => 'Falha ao finalizar o arquivo ZIP.'], 500);
        }

        return response()->download($zipPath, $nomeArquivo, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Gera o PDF (DANFE/DANFE-NFC-e/DACTE) a partir do XML já sincronizado em
     * `documentos_fiscais`, usando nfephp-org/sped-da. Não depende de nenhum
     * webservice adicional da Sefaz — DANFE/DACTE não são "gerados" por ela,
     * são montados localmente a partir do XML autorizado.
     */
    public function danfe(Request $request)
    {
        $validated = $request->validate([
            'chave_acesso' => 'required|string|size:44',
        ]);

        $documento = DocumentoFiscal::where('chave_acesso', $validated['chave_acesso'])->first();

        if (! $documento || empty($documento->xml_content)) {
            return response()->json(['error' => 'XML deste documento não está disponível para gerar o PDF.'], 422);
        }

        try {
            $gerador = match ($documento->tipo) {
                'nfe' => new Danfe($documento->xml_content),
                'nfce' => new Danfce($documento->xml_content),
                'cte' => new Dacte($documento->xml_content),
                default => throw new \RuntimeException("Tipo de documento '{$documento->tipo}' não suporta geração de PDF."),
            };

            $gerador->monta();
            $pdf = $gerador->render();
        } catch (\Throwable $e) {
            Log::warning('[NF-e] danfe: falha ao gerar PDF', [
                'chave_acesso' => $validated['chave_acesso'],
                'tipo' => $documento->tipo,
                'msg' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Não foi possível gerar o PDF deste documento — provavelmente o XML completo ainda '
                    .'não está disponível (documento ainda em formato resumido, aguardando manifestação do '
                    .'destinatário). Detalhe técnico: '.$e->getMessage(),
            ], 422);
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
