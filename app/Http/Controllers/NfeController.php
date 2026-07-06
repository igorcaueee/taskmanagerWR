<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteCertificadoNfse;
use App\Services\NfeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class NfeController extends Controller
{
    public function __construct(private NfeService $nfe) {}

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
