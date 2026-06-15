<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteCertificadoNfse;
use App\Services\NfseService;
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

    public function buscar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id'  => 'required|exists:clientes,id',
            'data_inicio' => 'required|date_format:Y-m-d',
            'data_fim'    => 'required|date_format:Y-m-d|after_or_equal:data_inicio',
        ]);

        $cert = ClienteCertificadoNfse::with('cliente')->where('cliente_id', $validated['cliente_id'])->first();

        if (!$cert) {
            return response()->json(['error' => 'Certificado digital não configurado para este cliente. Configure-o antes de buscar.'], 422);
        }

        Log::info('[NFS-e] buscar: iniciando', [
            'cliente_id'  => $validated['cliente_id'],
            'data_inicio' => $validated['data_inicio'],
            'data_fim'    => $validated['data_fim'],
            'ultimo_nsu'  => $cert->ultimo_nsu,
            'ambiente'    => $cert->ambiente,
        ]);

        try {
            $notas = $this->nfse->buscarPorPeriodo($cert, $validated['data_inicio'], $validated['data_fim']);

            Log::info('[NFS-e] buscar: concluído', ['total' => count($notas)]);

            // Verifica se json_encode consegue serializar — falha com UTF-8 inválido
            $payload = ['success' => true, 'total' => count($notas), 'notas' => $notas];
            $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($encoded === false) {
                Log::error('[NFS-e] buscar: json_encode falhou', [
                    'erro'    => json_last_error_msg(),
                    'amostra' => json_encode($notas[0] ?? []),
                ]);
                return response()->json(['error' => 'Falha ao serializar resposta: ' . json_last_error_msg()], 500);
            }

            Log::info('[NFS-e] buscar: json ok', ['bytes' => strlen($encoded)]);

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
     * Gera um .zip com os XMLs de múltiplas NFS-e selecionadas (por NSU).
     */
    public function downloadZip(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'nsus'       => 'required|array|min:1|max:100',
            'nsus.*'     => 'required|integer|min:1',
        ]);

        $cert    = ClienteCertificadoNfse::where('cliente_id', $validated['cliente_id'])->firstOrFail();
        $zipPath = storage_path('app/temp/nfse_' . time() . '.zip');

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

        return response()->download($zipPath, 'nfse_xmls.zip', [
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
     * Gera e retorna o DANFSE em PDF via API oficial do governo.
     * Endpoint da API: GET /danfse/{chaveAcesso}
     */
    public function danfse(Request $request)
    {
        $validated = $request->validate([
            'cliente_id'   => 'required|exists:clientes,id',
            'chave_acesso' => 'required|string',
        ]);

        $cert = ClienteCertificadoNfse::where('cliente_id', $validated['cliente_id'])->firstOrFail();

        try {
            $pdf      = $this->nfse->gerarDanfse($cert, $validated['chave_acesso']);
            $filename = 'danfse_' . substr($validated['chave_acesso'], -20) . '.pdf';

            return response($pdf, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"{$filename}\"",
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
