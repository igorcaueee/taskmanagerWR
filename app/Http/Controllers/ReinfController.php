<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ReinfFechamento;
use App\Services\ReinfEnvioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tela manual de envio/consulta dos eventos de fechamento do EFD-Reinf
 * (R-2099/R-4099) — API REST nativa da Receita, não SERPRO/Integra Contador.
 * Certificado usado é o e-CNPJ do próprio cliente (ClienteCertificadoNfse),
 * não um certificado único do escritório.
 */
class ReinfController extends Controller
{
    public function tela()
    {
        return view('reinf.tela', [
            'clientes' => Cliente::where('status', 'ativo')
                ->orderBy('nome')
                ->get(['id', 'nome', 'cpfcnpj']),
            'historico' => ReinfFechamento::with('cliente:id,nome')
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }

    public function enviar(Request $request, ReinfEnvioService $reinfEnvio): JsonResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'tipo_evento' => 'required|in:R-2099,R-4099',
            'periodo_apuracao' => 'required|regex:/^\d{4}-\d{2}$/',
            'responsavel_nome' => 'required|string|max:255',
            'responsavel_cpf' => 'required|string|max:14',
            'responsavel_telefone' => 'required|string|max:20',
            'responsavel_email' => 'required|email|max:255',
            'evt_serv_tm' => 'required_if:tipo_evento,R-2099|in:S,N',
            'evt_serv_pr' => 'required_if:tipo_evento,R-2099|in:S,N',
            'evt_ass_desp_rec' => 'required_if:tipo_evento,R-2099|in:S,N',
            'evt_ass_desp_rep' => 'required_if:tipo_evento,R-2099|in:S,N',
            'evt_com_prod' => 'required_if:tipo_evento,R-2099|in:S,N',
            'evt_cprb' => 'required_if:tipo_evento,R-2099|in:S,N',
            'evt_aquis' => 'required_if:tipo_evento,R-2099|in:S,N',
            'fech_ret' => 'required_if:tipo_evento,R-4099|in:0,1',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);

        $dados = $validated['tipo_evento'] === 'R-2099'
            ? [
                'evtServTm' => $validated['evt_serv_tm'],
                'evtServPr' => $validated['evt_serv_pr'],
                'evtAssDespRec' => $validated['evt_ass_desp_rec'],
                'evtAssDespRep' => $validated['evt_ass_desp_rep'],
                'evtComProd' => $validated['evt_com_prod'],
                'evtCPRB' => $validated['evt_cprb'],
                'evtAquis' => $validated['evt_aquis'],
            ]
            : ['fechRet' => $validated['fech_ret']];

        try {
            $fechamento = $reinfEnvio->enviarFechamento(
                $cliente,
                $validated['tipo_evento'],
                $validated['periodo_apuracao'],
                [
                    'nome' => $validated['responsavel_nome'],
                    'cpf' => $validated['responsavel_cpf'],
                    'telefone' => $validated['responsavel_telefone'],
                    'email' => $validated['responsavel_email'],
                ],
                $dados,
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'fechamento' => $fechamento->fresh()]);
    }

    public function consultar(ReinfFechamento $fechamento, ReinfEnvioService $reinfEnvio): JsonResponse
    {
        try {
            $fechamento = $reinfEnvio->consultarLote($fechamento);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'fechamento' => $fechamento->fresh()]);
    }
}
