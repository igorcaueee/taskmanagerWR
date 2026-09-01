<?php

namespace App\Http\Controllers;

use App\Exports\NfseExport;
use App\Models\Cliente;
use App\Models\ClienteCertificadoNfse;
use App\Services\Nfse\DanfseGeradorService;
use App\Services\NfseService;
use App\Services\NfseXmlParser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class NfseController extends Controller
{
    public function __construct(private NfseService $nfse) {}

    // ─── Tela principal ──────────────────────────────────────────────────────

    public function index()
    {
        $clientes = Cliente::where('status', 'ativo')
            ->orderBy('nome')
            ->get(['id', 'nome', 'cpfcnpj']);

        return view('nfse.index', compact('clientes'));
    }

    // ─── Certificado ─────────────────────────────────────────────────────────

    public function getCertificado(int $clienteId): JsonResponse
    {
        $cert = ClienteCertificadoNfse::where('cliente_id', $clienteId)->first();

        if (!$cert) {
            return response()->json(['configurado' => false]);
        }

        $arquivoExiste = file_exists(storage_path('app/' . $cert->arquivo));

        return response()->json([
            'configurado'   => true,
            'arquivo_ok'    => $arquivoExiste,
            'ambiente'      => $cert->ambiente,
            'vencimento'    => $cert->vencimento?->format('d/m/Y'),
            'ultimo_nsu'    => $cert->ultimo_nsu,
            'vencido'       => $cert->vencido(),
            'alerta'        => $cert->venceEm30Dias(),
        ]);
    }

    public function salvarCertificado(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id'  => 'required|exists:clientes,id',
            'certificado' => 'required|file|max:10240', // sem mimes: .pfx é detectado como octet-stream
            'senha'       => 'required|string|min:1',
            'ambiente'    => 'required|in:homologacao,producao',
        ]);

        $file = $request->file('certificado');

        // 1. Valida que o arquivo é realmente um PKCS#12 com a senha informada
        try {
            $vencimento = $this->nfse->validarCertificado($file->getRealPath(), $validated['senha']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        // 2. Garante que o diretório existe no storage
        $dir      = "nfse/certificados/{$validated['cliente_id']}";
        $destPath = storage_path("app/{$dir}");

        if (!is_dir($destPath)) {
            mkdir($destPath, 0755, true);
        }

        // 3. Copia o arquivo diretamente (evita problemas com MIME detection do storeAs)
        $destFile = "{$destPath}/certificado.pfx";

        if (!copy($file->getRealPath(), $destFile)) {
            return response()->json(['error' => 'Falha ao salvar o arquivo do certificado no servidor.'], 500);
        }

        $pathRelativo = "{$dir}/certificado.pfx";

        // 4. Atualiza ou cria o registro do certificado NFS-e
        ClienteCertificadoNfse::updateOrCreate(
            ['cliente_id' => $validated['cliente_id']],
            [
                'arquivo'    => $pathRelativo,
                'senha'      => $validated['senha'],
                'ambiente'   => $validated['ambiente'],
                'ultimo_nsu' => 0,
                'vencimento' => $vencimento,
            ]
        );

        // 5. Atualiza o vencimento_certificado no cadastro do cliente
        if ($vencimento) {
            Cliente::where('id', $validated['cliente_id'])
                ->update(['vencimento_certificado' => $vencimento]);
        }

        return response()->json([
            'success'    => true,
            'message'    => 'Certificado salvo com sucesso!',
            'vencimento' => $vencimento ? \Carbon\Carbon::parse($vencimento)->format('d/m/Y') : null,
        ]);
    }

    public function resetarNsu(int $clienteId): JsonResponse
    {
        $cert = ClienteCertificadoNfse::where('cliente_id', $clienteId)->firstOrFail();
        $cert->update(['ultimo_nsu' => 0]);

        return response()->json(['success' => true]);
    }

    // ─── Busca de NFS-e ──────────────────────────────────────────────────────

    /**
     * Busca uma fatia (chunk) de NFS-e. O front-end chama esta rota repetidas
     * vezes, passando 'nsu_inicio' = 'proximo_nsu' da resposta anterior, até
     * receber 'concluido' = true — cada chamada fica curta o suficiente para
     * não esbarrar no timeout de proxy/CDN (Cloudflare derruba com HTTP 524
     * conexões que o backend demora demais pra responder).
     */
    public function buscar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id'  => 'required|exists:clientes,id',
            'data_inicio' => 'required|date_format:Y-m-d',
            'data_fim'    => 'required|date_format:Y-m-d|after_or_equal:data_inicio',
            'nsu_inicio'  => 'sometimes|integer|min:0',
        ]);

        $nsuInicio = (int) ($validated['nsu_inicio'] ?? 0);

        $cert = ClienteCertificadoNfse::with('cliente')->where('cliente_id', $validated['cliente_id'])->first();

        if (!$cert) {
            return response()->json(['error' => 'Certificado digital não configurado para este cliente. Configure-o antes de buscar.'], 422);
        }

        Log::debug('[NFS-e] buscar: iniciando chunk', [
            'cliente_id'  => $validated['cliente_id'],
            'data_inicio' => $validated['data_inicio'],
            'data_fim'    => $validated['data_fim'],
            'nsu_inicio'  => $nsuInicio,
            'ambiente'    => $cert->ambiente,
        ]);

        try {
            $resultado = $this->nfse->buscarPorPeriodoChunk($cert, $validated['data_inicio'], $validated['data_fim'], $nsuInicio);

            Log::debug('[NFS-e] buscar: chunk concluído', [
                'total'       => count($resultado['notas']),
                'proximo_nsu' => $resultado['proximoNsu'],
                'concluido'   => $resultado['concluido'],
            ]);

            $payload = [
                'success'        => true,
                'total'          => count($resultado['notas']),
                'notas'          => $resultado['notas'],
                'proximo_nsu'    => $resultado['proximoNsu'],
                'concluido'      => $resultado['concluido'],
                'canceled_chaves' => $resultado['canceledChaves'],
            ];

            // Verifica se json_encode consegue serializar — falha com UTF-8 inválido
            $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($encoded === false) {
                Log::error('[NFS-e] buscar: json_encode falhou', [
                    'erro'    => json_last_error_msg(),
                    'amostra' => json_encode($resultado['notas'][0] ?? []),
                ]);
                return response()->json(['error' => 'Falha ao serializar resposta: ' . json_last_error_msg()], 500);
            }

            return new JsonResponse($payload, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\RuntimeException $e) {
            Log::error('[NFS-e] buscar: RuntimeException', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => $e->getMessage()], 500);
        } catch (\Throwable $e) {
            Log::error('[NFS-e] buscar: Throwable inesperado', ['msg' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Erro inesperado: ' . $e->getMessage()], 500);
        }
    }

    // ─── Download de XML ─────────────────────────────────────────────────────

    /**
     * Download do XML de uma NFS-e pelo NSU (endpoint GET /DFe/{NSU}?lote=false).
     */
    public function downloadXml(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'nsu'        => 'required|integer|min:1',
        ]);

        $cert = ClienteCertificadoNfse::where('cliente_id', $validated['cliente_id'])->firstOrFail();

        try {
            $xml      = $this->nfse->baixarXmlPorNsu($cert, (int) $validated['nsu']);
            $filename = "nfse_nsu{$validated['nsu']}.xml";

            return response($xml, 200, [
                'Content-Type'        => 'application/xml; charset=utf-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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
            'items.*.nsu' => 'required|integer|min:1',
            'items.*.xml' => 'required|string|min:1',
            'nome'        => 'nullable|string',
        ]);

        $nomeEmpresa   = trim((string) $request->input('nome', ''));
        $nomeArquivo   = ($nomeEmpresa !== '' ? $nomeEmpresa : 'NFS-e') . '.zip';
        $zipPath = storage_path('app/temp/nfse_' . time() . '_' . rand(1000, 9999) . '.zip');

        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return response()->json(['error' => 'Não foi possível criar o arquivo ZIP.'], 500);
        }

        foreach ($request->items as $item) {
            $zip->addFromString("nfse_nsu{$item['nsu']}.xml", $item['xml']);
        }

        $zip->close();

        return response()->download($zipPath, $nomeArquivo, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Gera um .zip com os XMLs de múltiplas NFS-e selecionadas (por NSU).
     * Mantido como fallback para notas sem xmlContent no frontend.
     */
    public function downloadZip(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'nsus'       => 'required|array|min:1|max:100',
            'nsus.*'     => 'required|integer|min:1',
        ]);

        $cert        = ClienteCertificadoNfse::where('cliente_id', $validated['cliente_id'])->firstOrFail();
        $nomeArquivo = ($cert->cliente->nome ?? 'NFS-e') . '.zip';
        $zipPath = storage_path('app/temp/nfse_' . time() . '_' . rand(1000, 9999) . '.zip');

        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return response()->json(['error' => 'Não foi possível criar o arquivo ZIP.'], 500);
        }

        foreach ($validated['nsus'] as $nsu) {
            try {
                $xml = $this->nfse->baixarXmlPorNsu($cert, (int) $nsu);
                $zip->addFromString("nfse_nsu{$nsu}.xml", $xml);
            } catch (\Exception) {
                // Ignora falhas individuais e continua
            }
        }

        $zip->close();

        return response()->download($zipPath, $nomeArquivo, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    // ─── Download do certificado .pfx ────────────────────────────────────────

    public function downloadCertificado(int $clienteId)
    {
        $cert = ClienteCertificadoNfse::where('cliente_id', $clienteId)->firstOrFail();
        $path = storage_path('app/' . $cert->arquivo);

        if (!file_exists($path)) {
            abort(404, 'Arquivo do certificado não encontrado no servidor.');
        }

        $nomeCliente = $cert->cliente->nome ?? "cliente_{$clienteId}";
        $nomeArquivo = \Illuminate\Support\Str::slug($nomeCliente) . '_certificado.pfx';

        return response()->download($path, $nomeArquivo, [
            'Content-Type' => 'application/x-pkcs12',
        ]);
    }

    // ─── Exportar Excel (por XMLs brutos) ────────────────────────────────────

    public function exportarExcel(Request $request)
    {
        $request->validate([
            'xmls'         => 'required|array|min:1',
            'xmls.*'       => 'required|string',
            'nome'         => 'nullable|string|max:100',
            'statuses'     => 'nullable|array',
            'cnpj_cliente' => 'nullable|string|max:20',
        ]);

        $statuses = $request->input('statuses', []);

        $notas = array_values(array_filter(array_map(function ($xml, $idx) use ($statuses) {
            $parsed = NfseXmlParser::parse($xml);
            if (!$parsed) return null;
            $statusPortal = $statuses[(string) $idx] ?? null;
            if ($statusPortal) {
                $parsed = NfseXmlParser::overrideStatus($parsed, $statusPortal);
            }
            return $parsed;
        }, $request->xmls, array_keys($request->xmls))));

        if (empty($notas)) {
            return response()->json(['error' => 'Nenhuma nota pôde ser parseada'], 422);
        }

        $nome         = $request->input('nome', 'NFS-e');
        $cnpjCliente  = $request->input('cnpj_cliente', '');

        return (new NfseExport($notas, $cnpjCliente))->download("{$nome} - NFS-e.xlsx");
    }

    // ─── Exportar Excel (por NSUs — baixa XMLs server-side) ──────────────────

    public function exportarExcelNsus(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'nsus'       => 'required|array|min:1|max:200',
            'nsus.*'     => 'required|integer|min:1',
            'nome'       => 'nullable|string|max:100',
            'statuses'   => 'nullable|array',
        ]);

        $cert     = ClienteCertificadoNfse::where('cliente_id', $request->cliente_id)->firstOrFail();
        $statuses = $request->input('statuses', []);

        $notas = [];
        foreach ($request->nsus as $nsu) {
            try {
                $xml    = $this->nfse->baixarXmlPorNsu($cert, (int) $nsu);
                $parsed = NfseXmlParser::parse($xml);
                if ($parsed) {
                    // Sobrescreve o status com o valor real do portal (o XML original pode ter cStat=100 mesmo cancelada)
                    $statusPortal = $statuses[(string) $nsu] ?? null;
                    if ($statusPortal) {
                        $parsed = NfseXmlParser::overrideStatus($parsed, $statusPortal);
                    }
                    $notas[] = $parsed;
                }
            } catch (\Exception $e) {
                Log::warning("[NFS-e] exportarExcelNsus: falha no NSU {$nsu}", ['msg' => $e->getMessage()]);
            }
        }

        if (empty($notas)) {
            return response()->json(['error' => 'Nenhuma nota pôde ser processada.'], 422);
        }

        $nome = $request->input('nome', 'NFS-e');

        return (new NfseExport($notas, $cert->cliente->cpfcnpj ?? ''))->download("{$nome} - NFS-e.xlsx");
    }

    // ─── DANFSE via Tecnos Municipal (Teutônia) ──────────────────────────────

    /**
     * Busca o link público do PDF no sistema Tecnos de Teutônia.
     * Usa o CNPJ e IM do emitente extraídos do XML do Portal Nacional.
     */
    public function danfseTecnos(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cnpj'   => 'required|string',
            'im'     => 'required|string',
            'numero' => 'required|string',
        ]);

        try {
            $url = $this->nfse->danfseTecnos($validated['cnpj'], $validated['im'], $validated['numero']);
            return response()->json(['url' => $url]);
        } catch (\RuntimeException $e) {
            Log::warning('[NFS-e] danfseTecnos erro', ['msg' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ─── DANFSE (PDF) ────────────────────────────────────────────────────────

    /**
     * Gera o DANFSe v2.0 (PDF) localmente a partir do XML da NFS-e, seguindo a
     * Nota Técnica SE/CGNFS-e nº 008/2026.
     *
     * A API oficial de geração (GET https://adn.nfse.gov.br/danfse/{chave}) foi
     * sobrestada em 1º de julho de 2026 — a montagem do documento passou a ser
     * responsabilidade dos sistemas emissores.
     *
     * Aceita o XML direto do frontend (`xml`) ou o par `cliente_id` + `nsu`
     * (baixa o XML server-side pelo certificado do cliente).
     */
    public function danfse(Request $request, DanfseGeradorService $gerador)
    {
        $validated = $request->validate([
            'xml'        => 'nullable|string',
            'cliente_id' => 'nullable|exists:clientes,id',
            'nsu'        => 'nullable|integer',
        ]);

        try {
            $xml = $validated['xml'] ?? null;

            if (! $xml) {
                if (empty($validated['cliente_id']) || empty($validated['nsu'])) {
                    return response()->json(['error' => 'Informe o XML da NFS-e ou o par cliente_id + nsu.'], 422);
                }
                $cert = ClienteCertificadoNfse::where('cliente_id', $validated['cliente_id'])->firstOrFail();
                $xml  = $this->nfse->baixarXmlPorNsu($cert, (int) $validated['nsu']);
            }

            // ?formato=html — pré-visualização rápida do layout no navegador (debug).
            if ($request->query('formato') === 'html') {
                return response($gerador->html($xml));
            }

            $pdf      = $gerador->gerar($xml);
            $filename = $gerador->nomeArquivo($xml);

            return response($pdf, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"{$filename}\"",
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('[NFS-e] Falha ao gerar DANFSe local', ['msg' => $e->getMessage()]);

            return response()->json(['error' => 'Não foi possível gerar o DANFSe: ' . $e->getMessage()], 500);
        }
    }
}
