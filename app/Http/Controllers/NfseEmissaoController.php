<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteDadosFiscaisNfse;
use App\Models\NfseEmissao;
use App\Models\NfseServicoNacional;
use App\Services\CnpjPublicoService;
use App\Services\NfseEmissaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NfseEmissaoController extends Controller
{
    public function __construct(
        private NfseEmissaoService $emissaoService,
        private CnpjPublicoService $cnpjPublico,
    ) {}

    public function consultarCnpjTomador(string $cnpj): JsonResponse
    {
        $dados = $this->cnpjPublico->buscarDadosCadastrais($cnpj);

        if (!$dados) {
            return response()->json(['error' => 'CNPJ não encontrado.'], 404);
        }

        return response()->json($dados);
    }

    public function form(Cliente $cliente)
    {
        $clientes = Cliente::where('status', 'ativo')->orderBy('nome')->get(['id', 'nome', 'cpfcnpj']);
        $servicosNacionais = NfseServicoNacional::orderBy('codigo_tributacao_nacional')->get();
        $dadosFiscais = $cliente->dadosFiscaisNfse;
        $certificado = $cliente->certificadoNfse;

        return view('nfse.emitir', compact('cliente', 'clientes', 'servicosNacionais', 'dadosFiscais', 'certificado'));
    }

    public function emitir(Request $request, Cliente $cliente): JsonResponse
    {
        $validated = $request->validate([
            'tomador_tipo_doc' => 'required|in:CPF,CNPJ',
            'tomador_cpf_cnpj' => 'required|string',
            'tomador_nome' => 'required|string|max:150',
            'tomador_email' => 'nullable|email',
            'tomador_cep' => 'nullable|string',
            'tomador_logradouro' => 'nullable|string',
            'tomador_numero' => 'nullable|string',
            'tomador_complemento' => 'nullable|string',
            'tomador_bairro' => 'nullable|string',
            'tomador_codigo_municipio_ibge' => 'nullable|string|size:7',
            'codigo_tributacao_nacional' => 'required|exists:nfse_servicos_nacionais,codigo_tributacao_nacional',
            'descricao_servico' => 'required|string|max:1000',
            'codigo_municipio_prestacao' => 'nullable|string|size:7',
            'valor_servico' => 'required|numeric|min:0.01',
            'aliquota' => 'nullable|numeric|min:0|max:100',
            'iss_retido' => 'boolean',
            'trib_issqn' => 'nullable|integer|in:1,2,3,4',
            'desconto_incondicional' => 'nullable|numeric|min:0',
            'dcompet' => 'required|date',
        ]);

        try {
            $emissao = $this->emissaoService->emitir(
                $cliente,
                [
                    'tipo_doc' => $validated['tomador_tipo_doc'],
                    'cpf_cnpj' => $validated['tomador_cpf_cnpj'],
                    'nome' => $validated['tomador_nome'],
                    'email' => $validated['tomador_email'] ?? null,
                    'cep' => $validated['tomador_cep'] ?? null,
                    'logradouro' => $validated['tomador_logradouro'] ?? null,
                    'numero' => $validated['tomador_numero'] ?? null,
                    'complemento' => $validated['tomador_complemento'] ?? null,
                    'bairro' => $validated['tomador_bairro'] ?? null,
                    'codigo_municipio_ibge' => $validated['tomador_codigo_municipio_ibge'] ?? null,
                ],
                [
                    'codigo_tributacao_nacional' => $validated['codigo_tributacao_nacional'],
                    'descricao' => $validated['descricao_servico'],
                    'codigo_municipio_prestacao' => $validated['codigo_municipio_prestacao'] ?? null,
                ],
                [
                    'valor_servico' => $validated['valor_servico'],
                    'aliquota' => $validated['aliquota'] ?? null,
                    'iss_retido' => $validated['iss_retido'] ?? false,
                    'trib_issqn' => $validated['trib_issqn'] ?? 1,
                    'desconto_incondicional' => $validated['desconto_incondicional'] ?? null,
                    'dcompet' => $validated['dcompet'],
                ]
            );

            return response()->json([
                'status' => $emissao->status,
                'chave_acesso' => $emissao->chave_acesso,
                'numero_nfse' => $emissao->numero_nfse,
                'erro' => $emissao->erro_mensagem,
                'emissao_id' => $emissao->id,
            ], $emissao->status === 'autorizada' ? 200 : 422);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Falha inesperada ao emitir NFS-e: ' . $e->getMessage()], 500);
        }
    }

    public function listar(Cliente $cliente)
    {
        $clientes = Cliente::where('status', 'ativo')->orderBy('nome')->get(['id', 'nome', 'cpfcnpj']);
        $emissoes = $cliente->nfseEmissoes()->paginate(20);

        return view('nfse.emissoes', compact('cliente', 'clientes', 'emissoes'));
    }

    public function cancelar(Request $request, NfseEmissao $emissao): JsonResponse
    {
        $validated = $request->validate([
            'motivo' => 'required|string|max:255',
        ]);

        try {
            $this->emissaoService->cancelar($emissao, $validated['motivo']);

            return response()->json(['status' => $emissao->fresh()->status]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Falha inesperada ao cancelar NFS-e: ' . $e->getMessage()], 500);
        }
    }

    public function substituir(Request $request, NfseEmissao $emissao): JsonResponse
    {
        $validated = $request->validate([
            'tomador_tipo_doc' => 'required|in:CPF,CNPJ',
            'tomador_cpf_cnpj' => 'required|string',
            'tomador_nome' => 'required|string|max:150',
            'tomador_email' => 'nullable|email',
            'tomador_cep' => 'nullable|string',
            'tomador_logradouro' => 'nullable|string',
            'tomador_numero' => 'nullable|string',
            'tomador_complemento' => 'nullable|string',
            'tomador_bairro' => 'nullable|string',
            'tomador_codigo_municipio_ibge' => 'nullable|string|size:7',
            'codigo_tributacao_nacional' => 'required|exists:nfse_servicos_nacionais,codigo_tributacao_nacional',
            'descricao_servico' => 'required|string|max:1000',
            'codigo_municipio_prestacao' => 'nullable|string|size:7',
            'valor_servico' => 'required|numeric|min:0.01',
            'aliquota' => 'nullable|numeric|min:0|max:100',
            'iss_retido' => 'boolean',
            'trib_issqn' => 'nullable|integer|in:1,2,3,4',
            'desconto_incondicional' => 'nullable|numeric|min:0',
            'dcompet' => 'required|date',
        ]);

        try {
            $novaEmissao = $this->emissaoService->substituir(
                $emissao,
                [
                    'tipo_doc' => $validated['tomador_tipo_doc'],
                    'cpf_cnpj' => $validated['tomador_cpf_cnpj'],
                    'nome' => $validated['tomador_nome'],
                    'email' => $validated['tomador_email'] ?? null,
                    'cep' => $validated['tomador_cep'] ?? null,
                    'logradouro' => $validated['tomador_logradouro'] ?? null,
                    'numero' => $validated['tomador_numero'] ?? null,
                    'complemento' => $validated['tomador_complemento'] ?? null,
                    'bairro' => $validated['tomador_bairro'] ?? null,
                    'codigo_municipio_ibge' => $validated['tomador_codigo_municipio_ibge'] ?? null,
                ],
                [
                    'codigo_tributacao_nacional' => $validated['codigo_tributacao_nacional'],
                    'descricao' => $validated['descricao_servico'],
                    'codigo_municipio_prestacao' => $validated['codigo_municipio_prestacao'] ?? null,
                ],
                [
                    'valor_servico' => $validated['valor_servico'],
                    'aliquota' => $validated['aliquota'] ?? null,
                    'iss_retido' => $validated['iss_retido'] ?? false,
                    'trib_issqn' => $validated['trib_issqn'] ?? 1,
                    'desconto_incondicional' => $validated['desconto_incondicional'] ?? null,
                    'dcompet' => $validated['dcompet'],
                ]
            );

            return response()->json([
                'status' => $novaEmissao->status,
                'chave_acesso' => $novaEmissao->chave_acesso,
                'emissao_id' => $novaEmissao->id,
                'erro' => $novaEmissao->erro_mensagem,
            ], $novaEmissao->status === 'autorizada' ? 200 : 422);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Falha inesperada ao substituir NFS-e: ' . $e->getMessage()], 500);
        }
    }

    public function salvarDadosFiscais(Request $request, Cliente $cliente): JsonResponse
    {
        $validated = $request->validate([
            'inscricao_municipal' => 'nullable|string|max:15',
            'codigo_municipio_ibge' => 'required|string|size:7',
            'cep' => 'required|string',
            'logradouro' => 'required|string',
            'numero' => 'required|string',
            'complemento' => 'nullable|string',
            'bairro' => 'required|string',
            'serie_dps' => 'nullable|string',
        ]);

        ClienteDadosFiscaisNfse::updateOrCreate(
            ['cliente_id' => $cliente->id],
            [
                'inscricao_municipal' => $validated['inscricao_municipal'] ?? null,
                'codigo_municipio_ibge' => $validated['codigo_municipio_ibge'],
                'cep' => preg_replace('/\D/', '', $validated['cep']),
                'logradouro' => $validated['logradouro'],
                'numero' => $validated['numero'],
                'complemento' => $validated['complemento'] ?? null,
                'bairro' => $validated['bairro'],
                'serie_dps' => $validated['serie_dps'] ?? '1',
            ]
        );

        return response()->json(['ok' => true]);
    }
}
