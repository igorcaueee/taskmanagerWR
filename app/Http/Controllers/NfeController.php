<?php

namespace App\Http\Controllers;

use App\Models\CertificadoContabilidade;
use App\Models\Cliente;
use App\Models\ClienteCertificadoNfse;
use App\Models\DocumentoFiscal;
use App\Services\CteIntegracaoRsService;
use App\Services\NfeIntegracaoRsService;
use App\Services\NfeService;
use App\Services\NfseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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

        if (!$cert) {
            return response()->json(['error' => 'Certificado digital não configurado para este cliente. Configure-o na tela de NFS-e antes de buscar.'], 422);
        }

        $nsuInicio = array_key_exists('nsu_inicio', $validated) ? (int) $validated['nsu_inicio'] : null;

        try {
            $resultado = $this->nfe->sincronizarChunk($cert, $nsuInicio);

            return response()->json([
                'success'     => true,
                'concluido'   => $resultado['concluido'],
                'proximo_nsu' => $resultado['proximoNsu'],
                'aviso'       => $resultado['aviso'],
            ]);
        } catch (\Throwable $e) {
            Log::error('[NF-e] sincronizarChunk: Throwable inesperado', ['msg' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Erro inesperado: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Lê os documentos já sincronizados (tabela `documentos_fiscais`) para o
     * período informado — não consulta a Sefaz (ver sincronizarChunk).
     */
    public function buscar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id'  => 'required|exists:clientes,id',
            'data_inicio' => 'required|date_format:Y-m-d',
            'data_fim'    => 'required|date_format:Y-m-d|after_or_equal:data_inicio',
            'page'        => 'sometimes|integer|min:1',
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

            Log::info('[NF-e] buscar: página carregada', ['page' => $page, 'total_paginas' => $totalPaginas, 'total' => $resultado['total']]);

            $payload = [
                'success'       => true,
                'total'         => $resultado['total'],
                'documentos'    => $resultado['documentos'],
                'pagina'        => $page,
                'total_paginas' => $totalPaginas,
                'concluido'     => $page >= $totalPaginas,
            ];
            $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($encoded === false) {
                Log::error('[NF-e] buscar: json_encode falhou', ['erro' => json_last_error_msg()]);
                return response()->json(['error' => 'Falha ao serializar resposta: ' . json_last_error_msg()], 500);
            }

            return new JsonResponse($payload, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            Log::error('[NF-e] buscar: Throwable inesperado', ['msg' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Erro inesperado: ' . $e->getMessage()], 500);
        }
    }

    // ─── Certificado da contabilidade (webservice NFeIntegracao/RS) ───────────

    public function getCertificadoContabilidade(): JsonResponse
    {
        abort_if(!auth()->user()?->canConfigurarCertificadoContabilidade(), 403);

        $cert = CertificadoContabilidade::first();

        if (!$cert) {
            return response()->json(['configurado' => false]);
        }

        return response()->json([
            'configurado' => true,
            'arquivo_ok'  => file_exists(storage_path('app/' . $cert->arquivo)),
            'ambiente'    => $cert->ambiente,
            'vencimento'  => $cert->vencimento?->format('d/m/Y'),
            'vencido'     => $cert->vencido(),
            'alerta'      => $cert->venceEm30Dias(),
        ]);
    }

    public function salvarCertificadoContabilidade(Request $request): JsonResponse
    {
        abort_if(!auth()->user()?->canConfigurarCertificadoContabilidade(), 403);

        $validated = $request->validate([
            'certificado' => 'required|file|max:10240',
            'senha'       => 'required|string|min:1',
            'ambiente'    => 'required|in:homologacao,producao',
        ]);

        $file = $request->file('certificado');

        try {
            $vencimento = $this->nfse->validarCertificado($file->getRealPath(), $validated['senha']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $dir      = 'nfe/certificados/contabilidade';
        $destPath = storage_path("app/{$dir}");

        if (!is_dir($destPath)) {
            mkdir($destPath, 0755, true);
        }

        $destFile = "{$destPath}/certificado.pfx";

        if (!copy($file->getRealPath(), $destFile)) {
            return response()->json(['error' => 'Falha ao salvar o arquivo do certificado no servidor.'], 500);
        }

        $cert = CertificadoContabilidade::first() ?? new CertificadoContabilidade();
        $cert->fill([
            'arquivo'    => "{$dir}/certificado.pfx",
            'senha'      => $validated['senha'],
            'ambiente'   => $validated['ambiente'],
            'vencimento' => $vencimento,
        ])->save();

        return response()->json([
            'success'    => true,
            'message'    => 'Certificado da contabilidade salvo com sucesso!',
            'vencimento' => $vencimento ? \Carbon\Carbon::parse($vencimento)->format('d/m/Y') : null,
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
            'fase'       => 'required|in:nfe,nfce,cte',
            'nsu_inicio' => 'sometimes|integer|min:0',
        ]);

        $cert = CertificadoContabilidade::first();

        if (!$cert) {
            return response()->json(['error' => 'Certificado da contabilidade não configurado. Cadastre-o antes de buscar.'], 422);
        }

        $cliente = Cliente::findOrFail($validated['cliente_id']);
        $nsuInicio = array_key_exists('nsu_inicio', $validated) ? (int) $validated['nsu_inicio'] : null;

        try {
            $resultado = match ($validated['fase']) {
                'nfe'   => $this->nfeRs->sincronizarChunk($cert, $cliente, NfeIntegracaoRsService::MOD_NFE, $nsuInicio),
                'nfce'  => $this->nfeRs->sincronizarChunk($cert, $cliente, NfeIntegracaoRsService::MOD_NFCE, $nsuInicio),
                'cte'   => $this->cteRs->sincronizarChunk($cert, $cliente, $nsuInicio),
            };

            return response()->json([
                'success'     => true,
                'fase'        => $validated['fase'],
                'concluido'   => $resultado['concluido'],
                'proximo_nsu' => $resultado['proximoNsu'],
                'aviso'       => $resultado['aviso'],
            ]);
        } catch (\Throwable $e) {
            Log::error('[NF-e RS] sincronizarRsChunk: Throwable inesperado', [
                'fase' => $validated['fase'], 'msg' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Erro inesperado: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Lê os documentos já sincronizados (tabela `documentos_fiscais`) para o
     * período informado — não consulta a Sefaz (ver sincronizarRsChunk).
     */
    public function buscarRs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id'  => 'required|exists:clientes,id',
            'data_inicio' => 'required|date_format:Y-m-d',
            'data_fim'    => 'required|date_format:Y-m-d|after_or_equal:data_inicio',
            'page'        => 'sometimes|integer|min:1',
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

            Log::info('[NF-e RS] buscar: página carregada', ['page' => $page, 'total_paginas' => $totalPaginas, 'total' => $resultado['total']]);

            return new JsonResponse(
                [
                    'success'       => true,
                    'total'         => $resultado['total'],
                    'documentos'    => $resultado['documentos'],
                    'pagina'        => $page,
                    'total_paginas' => $totalPaginas,
                    'concluido'     => $page >= $totalPaginas,
                ],
                200,
                [],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        } catch (\Throwable $e) {
            Log::error('[NF-e RS] buscar: Throwable inesperado', ['msg' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Erro inesperado: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Gera um .zip com os XMLs enviados diretamente pelo frontend (sem re-fetch da API).
     * Cada item: { nsu: int, xml: string }
     */
    public function downloadZipXmls(Request $request)
    {
        $request->validate([
            'items'       => 'required|array|min:1|max:200',
            'items.*.nsu' => 'required',
            'items.*.xml' => 'required|string|min:1',
            'nome'        => 'nullable|string',
        ]);

        $nomeEmpresa = trim((string) $request->input('nome', ''));
        $nomeArquivo = ($nomeEmpresa !== '' ? $nomeEmpresa : 'NFe-CTe') . '.zip';
        $zipPath = storage_path('app/temp/nfe_' . time() . '_' . rand(1000, 9999) . '.zip');

        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return response()->json(['error' => 'Não foi possível criar o arquivo ZIP.'], 500);
        }

        foreach ($request->items as $item) {
            $zip->addFromString("nfe_nsu{$item['nsu']}.xml", $item['xml']);
        }

        $zip->close();

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

        if (!$documento || empty($documento->xml_content)) {
            return response()->json(['error' => 'XML deste documento não está disponível para gerar o PDF.'], 422);
        }

        try {
            $gerador = match ($documento->tipo) {
                'nfe'   => new Danfe($documento->xml_content),
                'nfce'  => new Danfce($documento->xml_content),
                'cte'   => new Dacte($documento->xml_content),
                default => throw new \RuntimeException("Tipo de documento '{$documento->tipo}' não suporta geração de PDF."),
            };

            $gerador->monta();
            $pdf = $gerador->render();
        } catch (\Throwable $e) {
            Log::warning('[NF-e] danfe: falha ao gerar PDF', [
                'chave_acesso' => $validated['chave_acesso'],
                'tipo'         => $documento->tipo,
                'msg'          => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Não foi possível gerar o PDF deste documento — provavelmente o XML completo ainda '
                    . 'não está disponível (documento ainda em formato resumido, aguardando manifestação do '
                    . 'destinatário). Detalhe técnico: ' . $e->getMessage(),
            ], 422);
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
