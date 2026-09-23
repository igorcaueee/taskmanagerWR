<?php

namespace App\Http\Controllers;

use App\Models\StCalculo;
use App\Models\StGuia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Preparação de guia de recolhimento (GNRE/RS, DAE-SIARE/MG) por NF-e, e
 * upload do PDF da guia emitida / comprovante de pagamento. Fase 2 do
 * módulo ICMS-ST — ver plano do módulo. NUNCA emite a guia sozinho: só
 * prepara os dados para o usuário copiar/digitar no portal oficial.
 */
class IcmsStGuiaController extends Controller
{
    private const TIPOS_DOWNLOAD = ['guia' => 'pdf_guia_path', 'comprovante' => 'pdf_comprovante_path'];

    public function index(Request $request)
    {
        $query = StGuia::with('cliente:id,nome')->orderByDesc('created_at');

        if ($clienteId = $request->integer('cliente_id')) {
            $query->where('cliente_id', $clienteId);
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $guias = $query->paginate(50)->withQueryString();
        $clientes = \App\Models\Cliente::where('status', 'ativo')->orderBy('nome')->get(['id', 'nome']);

        return view('icms-st.guias-index', compact('guias', 'clientes'));
    }

    public function form(string $chaveAcesso)
    {
        [$calculo, $uf] = $this->resolverClienteUf($chaveAcesso);

        $preparado = $uf === 'RS'
            ? (new \App\Services\IcmsSt\PreparadorGnreRs())->preparar($chaveAcesso)
            : (new \App\Services\IcmsSt\PreparadorDaeMg())->preparar($chaveAcesso);

        $guia = StGuia::where('chave_acesso', $chaveAcesso)->first();

        return view('icms-st.guia-form', [
            'chaveAcesso' => $chaveAcesso,
            'uf' => $uf,
            'preparado' => $preparado,
            'guia' => $guia,
            'receitasGnre' => \App\Services\IcmsSt\PreparadorGnreRs::RECEITAS_GNRE,
            'receitaPadraoMg' => \App\Services\IcmsSt\PreparadorDaeMg::RECEITA_PADRAO,
        ]);
    }

    public function salvar(Request $request, string $chaveAcesso): RedirectResponse
    {
        [$calculo, $uf] = $this->resolverClienteUf($chaveAcesso);

        $validated = $request->validate([
            'codigo_receita' => 'required|string|max:20',
            'descricao_receita' => 'nullable|string|max:255',
            'valor_icms_st' => 'required|numeric|min:0',
            'valor_adicional' => 'required|numeric|min:0',
            'data_vencimento' => 'required|date_format:Y-m-d',
            'data_pagamento' => 'nullable|date_format:Y-m-d',
            'observacao' => 'nullable|string',
        ]);

        $dados = $validated + [
            'tipo' => $uf === 'RS' ? StGuia::TIPO_GNRE_RS : StGuia::TIPO_DAE_MG,
            'cliente_id' => $calculo->cliente_id,
            'chave_acesso' => $chaveAcesso,
            'valor_total' => $validated['valor_icms_st'] + $validated['valor_adicional'],
            'status' => StGuia::STATUS_PRONTA_PARA_EMISSAO,
        ];

        if ($uf === 'MG') {
            [$mes, $ano] = (new \App\Services\IcmsSt\PreparadorDaeMg())->periodoReferencia($validated['data_vencimento']);
            $dados['periodo_referencia_mes'] = $mes;
            $dados['periodo_referencia_ano'] = $ano;
        }

        StGuia::updateOrCreate(['chave_acesso' => $chaveAcesso], $dados);

        return redirect()->route('icms-st.guias.form', $chaveAcesso)
            ->with('status', 'Dados da guia salvos. Copie os valores no portal oficial para emitir.');
    }

    public function uploadPdfGuia(Request $request, StGuia $guia): RedirectResponse
    {
        $request->validate(['pdf' => 'required|file|mimes:pdf|max:10240']);

        $path = $request->file('pdf')->store('icms-st-guias');
        @chmod(dirname(Storage::disk('local')->path($path)), 0755);
        @chmod(Storage::disk('local')->path($path), 0644);

        $guia->update(['pdf_guia_path' => $path, 'status' => StGuia::STATUS_EMITIDA]);

        return redirect()->route('icms-st.guias.form', $guia->chave_acesso)
            ->with('status', 'PDF da guia anexado. Status: Emitida.');
    }

    public function uploadComprovante(Request $request, StGuia $guia): RedirectResponse
    {
        $validated = $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:10240',
            'data_pagamento' => 'required|date_format:Y-m-d',
        ]);

        $path = $request->file('pdf')->store('icms-st-guias');
        @chmod(dirname(Storage::disk('local')->path($path)), 0755);
        @chmod(Storage::disk('local')->path($path), 0644);

        $guia->update([
            'pdf_comprovante_path' => $path,
            'data_pagamento' => $validated['data_pagamento'],
            'status' => StGuia::STATUS_PAGA,
        ]);

        return redirect()->route('icms-st.guias.form', $guia->chave_acesso)
            ->with('status', 'Comprovante anexado. Status: Paga.');
    }

    public function download(StGuia $guia, string $tipo)
    {
        abort_unless(isset(self::TIPOS_DOWNLOAD[$tipo]), 404);

        $path = $guia->{self::TIPOS_DOWNLOAD[$tipo]};

        abort_if(empty($path), 404);

        return Storage::disk('local')->download($path);
    }

    /** @return array{0: StCalculo, 1: string} */
    private function resolverClienteUf(string $chaveAcesso): array
    {
        $calculo = StCalculo::where('chave_acesso', $chaveAcesso)->firstOrFail();
        $uf = strtoupper((string) $calculo->cliente?->estado);

        abort_unless(in_array($uf, ['RS', 'MG'], true), 422, "UF {$uf} sem motor de guia cadastrado.");

        return [$calculo, $uf];
    }
}
