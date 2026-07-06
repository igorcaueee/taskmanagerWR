<?php

namespace App\Http\Controllers;

use App\Models\CertificadoContabilidade;
use App\Models\Cliente;
use App\Models\ClienteCertificadoNfse;
use App\Services\NfeIntegracaoRsService;
use App\Services\NfeService;
use App\Services\NfseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class NfeController extends Controller
{
    public function __construct(
        private NfeService $nfe,
        private NfeIntegracaoRsService $nfeRs,
        private NfseService $nfse,
    ) {}

    public function index()
    {
        $clientes = Cliente::where('status', 'ativo')
            ->orderBy('nome')
            ->get(['id', 'nome', 'cpfcnpj']);

        return view('nfe.index', compact('clientes'));
    }

    public function buscar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id'  => 'required|exists:clientes,id',
            'data_inicio' => 'required|date_format:Y-m-d',
            'data_fim'    => 'required|date_format:Y-m-d|after_or_equal:data_inicio',
        ]);

        $cert = ClienteCertificadoNfse::with('cliente')->where('cliente_id', $validated['cliente_id'])->first();

        if (!$cert) {
            return response()->json(['error' => 'Certificado digital não configurado para este cliente. Configure-o na tela de NFS-e antes de buscar.'], 422);
        }

        Log::info('[NF-e] buscar: iniciando', [
            'cliente_id'  => $validated['cliente_id'],
            'data_inicio' => $validated['data_inicio'],
            'data_fim'    => $validated['data_fim'],
            'ambiente'    => $cert->ambiente,
        ]);

        try {
            $documentos = $this->nfe->buscarPorPeriodo($cert, $validated['data_inicio'], $validated['data_fim']);

            Log::info('[NF-e] buscar: concluído', ['total' => count($documentos)]);

            $payload = ['success' => true, 'total' => count($documentos), 'documentos' => $documentos];
            $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($encoded === false) {
                Log::error('[NF-e] buscar: json_encode falhou', ['erro' => json_last_error_msg()]);
                return response()->json(['error' => 'Falha ao serializar resposta: ' . json_last_error_msg()], 500);
            }

            return new JsonResponse($payload, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\RuntimeException $e) {
            Log::error('[NF-e] buscar: RuntimeException', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => $e->getMessage()], 500);
        } catch (\Throwable $e) {
            Log::error('[NF-e] buscar: Throwable inesperado', ['msg' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Erro inesperado: ' . $e->getMessage()], 500);
        }
    }

    // ─── Certificado da contabilidade (webservice NFeIntegracao/RS) ───────────

    public function getCertificadoContabilidade(): JsonResponse
    {
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

    public function buscarRs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id'  => 'required|exists:clientes,id',
            'data_inicio' => 'required|date_format:Y-m-d',
            'data_fim'    => 'required|date_format:Y-m-d|after_or_equal:data_inicio',
        ]);

        $cert = CertificadoContabilidade::first();

        if (!$cert) {
            return response()->json(['error' => 'Certificado da contabilidade não configurado. Cadastre-o antes de buscar.'], 422);
        }

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        Log::info('[NF-e RS] buscar: iniciando', [
            'cliente_id'  => $cliente->id,
            'data_inicio' => $validated['data_inicio'],
            'data_fim'    => $validated['data_fim'],
            'ambiente'    => $cert->ambiente,
        ]);

        try {
            $documentos = $this->nfeRs->buscarPorPeriodo($cert, $cliente, $validated['data_inicio'], $validated['data_fim']);

            Log::info('[NF-e RS] buscar: concluído', ['total' => count($documentos)]);

            return new JsonResponse(
                ['success' => true, 'total' => count($documentos), 'documentos' => $documentos],
                200,
                [],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        } catch (\RuntimeException $e) {
            Log::error('[NF-e RS] buscar: RuntimeException', ['msg' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => $e->getMessage()], 500);
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
}
