<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\DocumentoFiscal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use NFePHP\DA\CTe\Dacte;
use NFePHP\DA\NFe\Danfce;
use NFePHP\DA\NFe\Danfe;

/**
 * API de leitura (server-to-server) do cofre fiscal para o Nexus consumir.
 * Não dispara nenhuma busca na Sefaz — só lê o que a distribuição
 * nacional/RS já deixou salvo em `documentos_fiscais`. Autenticação via
 * EnsureNexusFiscalApiKey (header X-Api-Key), sem sessão/CSRF.
 */
class FiscalController extends Controller
{
    private const LIMIT_PADRAO = 50;

    private const LIMIT_MAXIMO = 200;

    public function index(Request $request): JsonResponse
    {
        $cnpj = preg_replace('/\D/', '', (string) $request->query('cnpj', ''));

        if ($cnpj === '') {
            return response()->json(['error' => 'cnpj é obrigatório'], 422);
        }

        $cliente = Cliente::whereRaw(
            "REPLACE(REPLACE(REPLACE(cpfcnpj, '.', ''), '-', ''), '/', '') = ?",
            [$cnpj]
        )->first();

        if (! $cliente) {
            return response()->json(['documentos' => [], 'proximoCursor' => null]);
        }

        $limit = (int) $request->query('limit', self::LIMIT_PADRAO);
        $limit = max(1, min($limit, self::LIMIT_MAXIMO));

        $query = DocumentoFiscal::where('cliente_id', $cliente->id)
            ->orderByDesc('id');

        if ($tipo = $request->query('tipo')) {
            if (! in_array($tipo, ['nfe', 'nfce', 'cte'], true)) {
                return response()->json(['error' => 'tipo inválido'], 422);
            }
            $query->where('tipo', $tipo);
        }

        if ($situacao = $request->query('situacao')) {
            if (! in_array($situacao, ['autorizada', 'cancelada'], true)) {
                return response()->json(['error' => 'situacao inválida'], 422);
            }
            $query->where('situacao', $situacao);
        }

        if ($dataInicio = $request->query('data_inicio')) {
            $query->whereDate('data_emissao', '>=', $dataInicio);
        }

        if ($dataFim = $request->query('data_fim')) {
            $query->whereDate('data_emissao', '<=', $dataFim);
        }

        if ($cursor = $request->query('cursor')) {
            $ultimoId = (int) $cursor;
            if ($ultimoId > 0) {
                $query->where('id', '<', $ultimoId);
            }
        }

        // Busca um registro a mais só para saber se existe próxima página,
        // sem precisar de um segundo COUNT(*).
        $registros = $query->limit($limit + 1)->get();

        $proximoCursor = null;
        if ($registros->count() > $limit) {
            $registros = $registros->slice(0, $limit);
            $proximoCursor = (string) $registros->last()->id;
        }

        $documentos = $registros->map(fn (DocumentoFiscal $doc) => [
            'chaveAcesso' => $doc->chave_acesso,
            'tipo' => $doc->tipo,
            'origem' => $doc->origem,
            'numero' => $doc->numero,
            'dataEmissao' => optional($doc->data_emissao)->format('Y-m-d'),
            'dataSaidaEntrada' => optional($doc->data_saida_entrada)->format('Y-m-d'),
            'emitenteNome' => $doc->emitente_nome,
            'emitenteDoc' => $doc->emitente_doc,
            'emitenteCrt' => $doc->emitente_crt,
            'destinatarioDoc' => $doc->destinatario_doc,
            'valor' => $doc->valor !== null ? (float) $doc->valor : null,
            'situacao' => $doc->situacao,
            'tpNf' => $doc->tp_nf,
            'papelCte' => $doc->papel_cte,
            'temPdf' => ! empty($doc->xml_content),
        ])->values();

        return response()->json([
            'documentos' => $documentos,
            'proximoCursor' => $proximoCursor,
        ]);
    }

    public function xml(Request $request, string $chaveAcesso): Response|JsonResponse
    {
        $documento = $this->localizarDocumentoDoCliente($request, $chaveAcesso);

        if ($documento instanceof JsonResponse) {
            return $documento;
        }

        if (empty($documento->xml_content)) {
            return response()->json(['error' => 'documento não encontrado'], 404);
        }

        return response($documento->xml_content, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'inline; filename="'.$chaveAcesso.'.xml"',
        ]);
    }

    public function pdf(Request $request, string $chaveAcesso): Response|JsonResponse
    {
        $documento = $this->localizarDocumentoDoCliente($request, $chaveAcesso);

        if ($documento instanceof JsonResponse) {
            return $documento;
        }

        if (empty($documento->xml_content)) {
            return response()->json(['error' => 'PDF não disponível'], 404);
        }

        try {
            $gerador = match ($documento->tipo) {
                'nfe' => new Danfe(DocumentoFiscal::adicionarCestNaDescricao($documento->xml_content)),
                'nfce' => new Danfce(DocumentoFiscal::adicionarCestNaDescricao($documento->xml_content)),
                'cte' => new Dacte($documento->xml_content),
                default => throw new \RuntimeException("Tipo de documento '{$documento->tipo}' não suporta geração de PDF."),
            };

            $gerador->monta();
            $pdf = $gerador->render();
        } catch (\Throwable $e) {
            Log::warning('[API Fiscal] pdf: falha ao gerar PDF', [
                'chave_acesso' => $chaveAcesso,
                'tipo' => $documento->tipo,
                'msg' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'PDF não disponível'], 404);
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$chaveAcesso.'.pdf"',
        ]);
    }

    private function localizarDocumentoDoCliente(Request $request, string $chaveAcesso): DocumentoFiscal|JsonResponse
    {
        if (! preg_match('/^\d{44}$/', $chaveAcesso)) {
            return response()->json(['error' => 'documento não encontrado'], 404);
        }

        $cnpj = preg_replace('/\D/', '', (string) $request->query('cnpj', ''));

        if ($cnpj === '') {
            return response()->json(['error' => 'cnpj é obrigatório'], 422);
        }

        $cliente = Cliente::whereRaw(
            "REPLACE(REPLACE(REPLACE(cpfcnpj, '.', ''), '-', ''), '/', '') = ?",
            [$cnpj]
        )->first();

        if (! $cliente) {
            return response()->json(['error' => 'documento não encontrado'], 404);
        }

        $documento = DocumentoFiscal::where('chave_acesso', $chaveAcesso)
            ->where('cliente_id', $cliente->id)
            ->first();

        if (! $documento) {
            return response()->json(['error' => 'documento não encontrado'], 404);
        }

        return $documento;
    }
}
