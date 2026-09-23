<?php

namespace App\Http\Controllers;

use App\Exports\IcmsStRelatorioExport;
use App\Models\Cliente;
use App\Models\StCalculo;
use App\Models\StOverrideBase;
use App\Models\StOverrideCest;
use App\Services\IcmsSt\CalculadoraStMg;
use App\Services\IcmsSt\CalculadoraStRs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Módulo de ICMS-ST — antecipação tributária pelo destinatário (RS e MG),
 * calculada a partir do cofre de XMLs (App\Models\DocumentoFiscal). Ver
 * PROMPT_LARAVEL_ICMS_ST.md e o plano do módulo para o desenho completo.
 * Fase 1: motor + consulta + overrides + fila de pendências. Fase 2 (guia
 * GNRE/DAE, upload de PDF, tela de regras editável) ainda não implementada.
 */
class IcmsStController extends Controller
{
    /** UFs para as quais existe motor de cálculo cadastrado. */
    private const UFS_SUPORTADAS = ['RS', 'MG'];

    public function index(Request $request)
    {
        $clientes = Cliente::where('status', 'ativo')->orderBy('nome')->get(['id', 'nome', 'cpfcnpj', 'estado']);

        $clienteId = $request->integer('cliente_id') ?: null;
        $dataInicio = $request->string('data_inicio')->toString() ?: null;
        $dataFim = $request->string('data_fim')->toString() ?: null;
        $busca = trim($request->string('busca')->toString()) ?: null;
        $statusFiltro = $request->string('status_filtro')->toString() ?: null;

        // Sem filtro de data na URL (primeira visita, ou alguém limpou o campo) --
        // default pro mês passado inteiro, em vez de deixar os campos em branco.
        if (! $dataInicio && ! $dataFim) {
            $mesPassado = now()->subMonthNoOverflow();
            $dataInicio = $mesPassado->copy()->startOfMonth()->format('Y-m-d');
            $dataFim = $mesPassado->copy()->endOfMonth()->format('Y-m-d');
        }

        // Nunca deixa exibir/consultar um intervalo invertido (ex.: veio errado
        // de uma URL antiga/compartilhada) -- normaliza em vez de dar erro.
        if ($dataInicio && $dataFim && $dataFim < $dataInicio) {
            [$dataInicio, $dataFim] = [$dataFim, $dataInicio];
        }

        $notas = null;
        $totais = null;
        $clienteSelecionado = null;

        if ($clienteId && $dataInicio && $dataFim) {
            $clienteSelecionado = Cliente::find($clienteId);
            $notas = $this->listarNotasDoPeriodo((int) $clienteId, $dataInicio, $dataFim, $busca, $statusFiltro);
            $totais = [
                'icms_st' => $notas->sum('icms_st'),
                'adicional' => $notas->sum('adicional'),
                'total' => $notas->sum('total'),
                'pendentes' => $notas->sum('pendentes'),
            ];
        }

        return view('icms-st.index', compact('clientes', 'notas', 'totais', 'clienteId', 'dataInicio', 'dataFim', 'busca', 'statusFiltro', 'clienteSelecionado'));
    }

    public function calcular(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'data_inicio' => 'required|date_format:Y-m-d',
            'data_fim' => 'required|date_format:Y-m-d|after_or_equal:data_inicio',
        ]);

        $cliente = Cliente::findOrFail($validated['cliente_id']);
        $uf = strtoupper((string) $cliente->estado);

        $redirectParams = $validated;

        if (! in_array($uf, self::UFS_SUPORTADAS, true)) {
            return redirect()->route('icms-st.index', $redirectParams)->with('erro',
                "Esta ferramenta ainda não possui motor de cálculo para o Estado {$uf} (cliente "
                . "{$cliente->nome}). Nenhum cálculo foi realizado — não presuma que a mercadoria "
                . 'está isenta de ST. UFs suportadas hoje: '.implode(', ', self::UFS_SUPORTADAS).'.');
        }

        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        try {
            $motor = $uf === 'RS' ? new CalculadoraStRs() : new CalculadoraStMg();
            $resultado = $motor->calcularParaCliente($cliente, $validated['data_inicio'], $validated['data_fim']);

            $redirect = redirect()->route('icms-st.index', $redirectParams)->with('status',
                "Cálculo concluído: {$resultado['processadas']} de {$resultado['totalDocumentos']} NF-e do "
                . "período processadas, {$resultado['itens']} itens, {$resultado['pendentes']} pendentes de "
                . 'validação.');

            // Documento aparece no cofre (resumo já sincronizado) mas ainda não teve o
            // XML completo baixado (sem <det>/itens) -- nunca silenciamos isso: sem
            // avisar, o cálculo dá zero sem explicação nenhuma pro usuário.
            if ($resultado['semXmlCompleto'] > 0) {
                $redirect = $redirect->with('aviso',
                    "{$resultado['semXmlCompleto']} NF-e deste período ainda não têm o XML completo no "
                    . 'cofre (só o resumo da distribuição) -- não entraram no cálculo. Baixe o XML completo '
                    . 'dessas notas na tela de NF-e (Distribuição DFe) e calcule de novo.');
            }

            return $redirect;
        } catch (\Throwable $e) {
            Log::error('[ICMS-ST] calcular: Throwable inesperado', [
                'msg' => $e->getMessage(), 'class' => get_class($e), 'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('icms-st.index', $redirectParams)
                ->with('erro', 'Erro inesperado ao calcular: '.$e->getMessage());
        }
    }

    /**
     * Exportação Excel item a item do período — mesmas colunas dos CSVs que
     * os motores Python já geram (ver App\Exports\IcmsStRelatorioExport).
     */
    public function exportarExcel(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'data_inicio' => 'required|date_format:Y-m-d',
            'data_fim' => 'required|date_format:Y-m-d|after_or_equal:data_inicio',
            'busca' => 'nullable|string|max:50',
            'status_filtro' => 'nullable|in:pendentes,sem_pendentes',
        ]);

        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        $cliente = Cliente::findOrFail($validated['cliente_id']);
        $busca = $validated['busca'] ?? null;
        $statusFiltro = $validated['status_filtro'] ?? null;

        // Exportar sempre as MESMAS notas que estão sendo exibidas na tela --
        // reaproveita a mesma listagem/filtro usada em listarNotasDoPeriodo()
        // em vez de reimplementar a busca/filtro de novo aqui.
        $chavesFiltradas = ($busca || $statusFiltro)
            ? $this->listarNotasDoPeriodo($cliente->id, $validated['data_inicio'], $validated['data_fim'], $busca, $statusFiltro)
                ->pluck('chave_acesso')
            : null;

        $linhas = DB::table('st_calculos')
            ->join('documentos_fiscais', 'documentos_fiscais.chave_acesso', '=', 'st_calculos.chave_acesso')
            ->where('st_calculos.cliente_id', $cliente->id)
            ->whereBetween('documentos_fiscais.data_emissao', [$validated['data_inicio'], $validated['data_fim']])
            ->when($chavesFiltradas !== null, fn ($q) => $q->whereIn('st_calculos.chave_acesso', $chavesFiltradas))
            ->orderBy('documentos_fiscais.data_emissao')
            ->orderBy('st_calculos.chave_acesso')
            ->orderByRaw('CAST(st_calculos.nfe_item AS UNSIGNED)')
            ->select('st_calculos.*', 'documentos_fiscais.data_emissao as nfe_data_emissao')
            ->cursor()
            ->map(function ($linha) {
                // PDO devolve coluna DECIMAL como string -- sem o cast pra float aqui,
                // o OpenSpout grava a célula como TEXTO no xlsx (Excel/LibreOffice não
                // consegue somar, mostra "Contagem" em vez de "Soma" na barra de status).
                $num = fn ($v) => $v === null ? null : (float) $v;

                return [
                    'NF-e' => $linha->nfe_numero,
                    'Item' => $linha->nfe_item,
                    'Chave_Acesso' => $linha->chave_acesso,
                    'Data_Emissao' => $linha->nfe_data_emissao,
                    'Produto' => $linha->produto,
                    'NCM' => $linha->ncm,
                    'CEST_XML' => $linha->cest_xml,
                    'CEST_Usado' => $linha->cest_usado,
                    'CEST_Origem' => $linha->cest_origem,
                    'Segmento' => $linha->segmento,
                    'CFOP' => $linha->cfop,
                    'UF_Origem' => $linha->uf_origem,
                    'UF_Destino' => $linha->uf_destino,
                    'V_Prod' => $num($linha->vprod),
                    'V_BC_Origem' => $num($linha->vbc_origem),
                    'Base_Operacao_Usada' => $num($linha->base_operacao_usada),
                    'V_IPI' => $num($linha->vipi),
                    'Aliq_Interestadual' => $num($linha->aliq_interestadual_pct),
                    'MVA_Aplicada' => $num($linha->mva_aplicada_pct),
                    'Base_ST_Calculada' => $num($linha->base_st_calculada),
                    'Aliquota_Interna' => $num($linha->aliquota_interna_pct),
                    'ICMS_Proprio' => $num($linha->icms_proprio),
                    'ICMS_ST_Devido' => $num($linha->icms_st_devido),
                    'Adicional_Tipo' => $linha->adicional_tipo,
                    'Adicional_Valor' => $num($linha->adicional_valor),
                    'Total_a_Recolher' => $num($linha->total_a_recolher),
                    'Responsavel' => $linha->responsavel,
                    'Status' => $linha->status,
                    'Status_Detalhe' => $linha->status_detalhe,
                ];
            });

        $filename = 'icms-st_'.\Illuminate\Support\Str::slug($cliente->nome).'_'.$validated['data_inicio'].'_'.$validated['data_fim'].'.xlsx';

        return (new IcmsStRelatorioExport($linhas))->download($filename);
    }

    public function detalhe(string $chaveAcesso)
    {
        $itens = StCalculo::where('chave_acesso', $chaveAcesso)
            ->orderByRaw('CAST(nfe_item AS UNSIGNED)')
            ->get();

        abort_if($itens->isEmpty(), 404);

        $overridesCest = StOverrideCest::where('chave_acesso', $chaveAcesso)->get()->keyBy('nfe_item');
        $overridesBase = StOverrideBase::where('chave_acesso', $chaveAcesso)->get()->keyBy('nfe_item');

        return view('icms-st.detalhe', [
            'chaveAcesso' => $chaveAcesso,
            'itens' => $itens,
            'overridesCest' => $overridesCest,
            'overridesBase' => $overridesBase,
            'cliente' => $itens->first()->cliente,
        ]);
    }

    /**
     * Fila consolidada de todos os itens pendentes, de todos os clientes,
     * agrupados por tipo de pendência — a tela usada no dia a dia.
     */
    public function pendencias(Request $request)
    {
        $pendencias = StCalculo::with('cliente:id,nome')
            ->whereIn('status', StCalculo::STATUS_PENDENTES)
            ->orderByDesc('updated_at')
            ->limit(1000)
            ->get()
            ->groupBy('status');

        return view('icms-st.pendencias', compact('pendencias'));
    }

    public function salvarOverrideCest(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'chave_acesso' => 'required|string|size:44',
            'nfe_item' => 'required|string',
            'cest_atribuido' => 'nullable|string|max:9',
            'fundamento' => 'required|string',
            'observacao' => 'nullable|string',
        ]);

        StOverrideCest::updateOrCreate(
            ['chave_acesso' => $validated['chave_acesso'], 'nfe_item' => $validated['nfe_item']],
            [
                'cest_atribuido' => $validated['cest_atribuido'] ?: null,
                'fundamento' => $validated['fundamento'],
                'observacao' => $validated['observacao'] ?? null,
                'decidido_por' => auth()->user()?->name,
                'decidido_em' => now(),
            ]
        );

        $this->recalcularItem($validated['chave_acesso']);

        return redirect()->route('icms-st.detalhe', $validated['chave_acesso'])
            ->with('status', 'CEST atualizado e item recalculado.');
    }

    public function salvarOverrideBase(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'chave_acesso' => 'required|string|size:44',
            'nfe_item' => 'required|string',
            'percentual_reducao_pct' => 'required|numeric|min:0|max:100',
            'observacao' => 'nullable|string',
        ]);

        StOverrideBase::updateOrCreate(
            ['chave_acesso' => $validated['chave_acesso'], 'nfe_item' => $validated['nfe_item']],
            [
                'percentual_reducao_pct' => $validated['percentual_reducao_pct'],
                'observacao' => $validated['observacao'] ?? null,
                'decidido_por' => auth()->user()?->name,
                'decidido_em' => now(),
            ]
        );

        $this->recalcularItem($validated['chave_acesso']);

        return redirect()->route('icms-st.detalhe', $validated['chave_acesso'])
            ->with('status', 'Redução de base registrada e item recalculado.');
    }

    /**
     * Um override muda o resultado de um único item, mas o motor recalcula
     * por NF-e (mais simples e barato que recalcular item a item) — a
     * mesma NF-e raramente tem mais que algumas dezenas de itens.
     */
    private function recalcularItem(string $chaveAcesso): void
    {
        $calculo = StCalculo::where('chave_acesso', $chaveAcesso)->first();

        if (! $calculo) {
            return;
        }

        $cliente = $calculo->cliente;
        $uf = strtoupper((string) $cliente?->estado);

        if (! in_array($uf, self::UFS_SUPORTADAS, true)) {
            return;
        }

        $motor = $uf === 'RS' ? new CalculadoraStRs() : new CalculadoraStMg();
        // Janela de 1 dia em torno de hoje não serve -- precisamos só desta
        // NF-e; reaproveita o motor filtrando pela data de emissão real dela.
        $documento = \App\Models\DocumentoFiscal::where('chave_acesso', $chaveAcesso)->first();

        if (! $documento || ! $documento->data_emissao) {
            return;
        }

        $data = $documento->data_emissao->format('Y-m-d');
        $motor->calcularParaCliente($cliente, $data, $data);
    }

    /**
     * @param  ?string  $busca  filtra por número da NF-e (LIKE parcial)
     * @param  ?string  $statusFiltro  'pendentes' (só notas com >=1 item pendente) ou
     *                                 'sem_pendentes' (nenhum item pendente); null = todas
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function listarNotasDoPeriodo(
        int $clienteId,
        string $dataInicio,
        string $dataFim,
        ?string $busca = null,
        ?string $statusFiltro = null,
    ) {
        return DB::table('st_calculos')
            ->join('documentos_fiscais', 'documentos_fiscais.chave_acesso', '=', 'st_calculos.chave_acesso')
            ->where('st_calculos.cliente_id', $clienteId)
            ->whereBetween('documentos_fiscais.data_emissao', [$dataInicio, $dataFim])
            ->when($busca, fn ($q) => $q->where('st_calculos.nfe_numero', 'like', '%'.$busca.'%'))
            ->groupBy('st_calculos.chave_acesso', 'st_calculos.nfe_numero')
            ->orderBy('documentos_fiscais.data_emissao')
            ->select([
                'st_calculos.chave_acesso',
                'st_calculos.nfe_numero',
                DB::raw('MIN(documentos_fiscais.data_emissao) as data_emissao'),
                DB::raw('SUM(st_calculos.icms_st_devido) as icms_st'),
                DB::raw('SUM(st_calculos.adicional_valor) as adicional'),
                DB::raw('SUM(st_calculos.total_a_recolher) as total'),
                DB::raw("SUM(CASE WHEN st_calculos.status NOT IN ('calculado','st_ja_destacada_no_xml','nao_sujeito_st') THEN 1 ELSE 0 END) as pendentes"),
                DB::raw("SUM(CASE WHEN st_calculos.status = 'uf_nao_suportada' THEN 1 ELSE 0 END) as uf_nao_suportada"),
            ])
            ->when($statusFiltro === 'pendentes', fn ($q) => $q->havingRaw('pendentes > 0'))
            ->when($statusFiltro === 'sem_pendentes', fn ($q) => $q->havingRaw('pendentes = 0'))
            ->get();
    }
}
