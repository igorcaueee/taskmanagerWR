<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ConsultaCndConfiguracao;
use App\Services\ConsultaCnd\ConsultaCndAuthService;
use App\Services\ConsultaCnd\ConsultaCndService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ConsultaCndController extends Controller
{
    public function index()
    {
        $clientes = Cliente::where('status', 'ativo')
            ->orderBy('nome')
            ->get(['id', 'nome', 'cpfcnpj']);

        return view('cnd.index', compact('clientes'));
    }

    // ─── Configuração ────────────────────────────────────────────────────────

    public function getConfiguracao(): JsonResponse
    {
        $config = ConsultaCndConfiguracao::first();

        if (! $config) {
            return response()->json(['configurado' => false]);
        }

        return response()->json([
            'configurado' => true,
            'ambiente' => $config->ambiente,
        ]);
    }

    public function salvarConfiguracao(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'consumer_key' => 'required|string',
            'consumer_secret' => 'required|string',
            'ambiente' => 'required|in:trial,producao',
        ]);

        $config = ConsultaCndConfiguracao::first() ?? new ConsultaCndConfiguracao;
        $config->fill($validated);
        $config->save();

        return response()->json(['success' => true, 'message' => 'Configuração da API Consulta CND salva com sucesso.']);
    }

    public function testarConexao(ConsultaCndAuthService $auth): JsonResponse
    {
        $auth->invalidarToken();

        try {
            $auth->obterToken();
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Autenticação na API Consulta CND funcionou — token obtido com sucesso.',
        ]);
    }

    // ─── Consulta ────────────────────────────────────────────────────────────

    /**
     * Consulta/emite a certidão. É billed pela SERPRO conforme o Status
     * retornado (ver docblock de ConsultaCndService) — cada clique aqui pode
     * gerar cobrança real.
     */
    public function consultar(Request $request, ConsultaCndService $cnd): JsonResponse
    {
        $validated = $request->validate([
            'tipo_contribuinte' => ['required', 'integer', Rule::in([1, 2, 3])],
            'numero_inscricao' => 'required|string',
            'gerar_pdf' => 'boolean',
            'carimbo_tempo' => 'boolean',
            'chave' => 'nullable|string',
        ]);

        try {
            $resultado = $cnd->consultar(
                $validated['tipo_contribuinte'],
                $validated['numero_inscricao'],
                $validated['gerar_pdf'] ?? true,
                $validated['carimbo_tempo'] ?? false,
                $validated['chave'] ?? null,
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $status = $resultado['Status'] ?? null;
        $arquivo = null;

        $pdfBase64 = $resultado['Certidao']['DocumentoPdf'] ?? null;

        if ($pdfBase64) {
            $nomeArquivo = 'CND-'.$validated['numero_inscricao'].'-'.time().'.pdf';
            $arquivo = $this->salvarPdfBase64($pdfBase64, $nomeArquivo);
        }

        return response()->json([
            'success' => true,
            'status' => $status,
            'mensagem' => $resultado['Mensagem'] ?? null,
            'certidao' => $resultado['Certidao'] ?? null,
            'chave' => $resultado['Chave'] ?? null,
            'arquivo' => $arquivo,
        ]);
    }

    private function salvarPdfBase64(string $pdfBase64, string $nomeArquivo): array
    {
        $dir = 'cnd/certidoes';
        $destPath = storage_path("app/{$dir}");

        if (! is_dir($destPath)) {
            mkdir($destPath, 0755, true);
        }

        $nomeSeguro = time().'-'.preg_replace('/[^A-Za-z0-9._-]/', '_', $nomeArquivo);
        file_put_contents("{$destPath}/{$nomeSeguro}", base64_decode($pdfBase64));

        return [
            'nomeArquivo' => $nomeArquivo,
            'url' => route('cnd.download', ['arquivo' => $nomeSeguro]),
        ];
    }

    public function download(string $arquivo)
    {
        $path = 'cnd/certidoes/'.basename($arquivo);

        if (! Storage::exists($path)) {
            abort(404);
        }

        return Storage::download($path);
    }
}
