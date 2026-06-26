<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ContaFinanceira;
use App\Models\LancamentoFinanceiro;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceiroController extends Controller
{
    public function dashboard(Request $request): View
    {
        abort_unless(auth()->user()?->canGerenciarFinanceiro(), 403);

        $clienteId = $request->integer('cliente_id') ?: null;

        $empresas = Cliente::where('status', 'ativo')
            ->orderBy('nome')
            ->get(['id', 'nome', 'conta_azul_conectada']);

        $query = LancamentoFinanceiro::query();

        if ($clienteId) {
            $query->where('cliente_id', $clienteId);
        }

        // Cards de totais
        $totalCreditos = (clone $query)->creditos()->pagos()->sum('valor');
        $totalDebitos  = (clone $query)->debitos()->pagos()->sum('valor');
        $saldoTotal    = $totalCreditos - $totalDebitos;

        $contasAReceber = (clone $query)->creditos()->pendentes()->sum('valor');
        $contasAPagar   = (clone $query)->debitos()->pendentes()->sum('valor');

        // Fluxo de caixa: últimos 6 meses (lançamentos pagos)
        $meses = collect();
        for ($i = 5; $i >= 0; $i--) {
            $mes  = now()->subMonths($i);
            $ini  = $mes->startOfMonth()->format('Y-m-d');
            $fim  = $mes->endOfMonth()->format('Y-m-d');

            $cr = (clone $query)->creditos()->pagos()
                ->whereBetween('data_pagamento', [$ini, $fim])
                ->sum('valor');

            $db = (clone $query)->debitos()->pagos()
                ->whereBetween('data_pagamento', [$ini, $fim])
                ->sum('valor');

            $meses->push([
                'label'    => $mes->locale('pt_BR')->isoFormat('MMM/YY'),
                'creditos' => (float) $cr,
                'debitos'  => (float) $db,
            ]);
        }

        // Últimos lançamentos
        $lancamentos = (clone $query)
            ->with(['cliente', 'contaFinanceira', 'categoria'])
            ->orderByDesc('data_vencimento')
            ->limit(10)
            ->get();

        $contas = ContaFinanceira::when($clienteId, fn ($q) => $q->where('cliente_id', $clienteId))
            ->where('ativa', true)
            ->get();

        return view('financeiro.dashboard', compact(
            'empresas',
            'clienteId',
            'saldoTotal',
            'totalCreditos',
            'totalDebitos',
            'contasAReceber',
            'contasAPagar',
            'meses',
            'lancamentos',
            'contas',
        ));
    }

    public function lancamentos(Request $request): View
    {
        abort_unless(auth()->user()?->canGerenciarFinanceiro(), 403);

        $empresas  = Cliente::where('status', 'ativo')->orderBy('nome')->get(['id', 'nome']);
        $clienteId = $request->integer('cliente_id') ?: null;
        $tipo      = $request->string('tipo')->toString() ?: null;
        $status    = $request->string('status')->toString() ?: null;
        $de        = $request->string('de')->toString() ?: null;
        $ate       = $request->string('ate')->toString() ?: null;

        $query = LancamentoFinanceiro::with(['cliente', 'contaFinanceira', 'categoria', 'centroCusto'])
            ->when($clienteId, fn ($q) => $q->where('cliente_id', $clienteId))
            ->when($tipo,      fn ($q) => $q->where('tipo', $tipo))
            ->when($status,    fn ($q) => $q->where('status', $status))
            ->when($de,        fn ($q) => $q->where('data_vencimento', '>=', $de))
            ->when($ate,       fn ($q) => $q->where('data_vencimento', '<=', $ate))
            ->orderByDesc('data_vencimento');

        $lancamentos = $query->paginate(50)->withQueryString();

        return view('financeiro.lancamentos', compact(
            'lancamentos',
            'empresas',
            'clienteId',
            'tipo',
            'status',
            'de',
            'ate',
        ));
    }

    public function contas(Request $request): View
    {
        abort_unless(auth()->user()?->canGerenciarFinanceiro(), 403);

        $clienteId = $request->integer('cliente_id') ?: null;

        $empresas = Cliente::where('status', 'ativo')->orderBy('nome')->get(['id', 'nome']);

        $contas = ContaFinanceira::with('cliente')
            ->when($clienteId, fn ($q) => $q->where('cliente_id', $clienteId))
            ->orderBy('nome')
            ->get();

        return view('financeiro.contas', compact('contas', 'empresas', 'clienteId'));
    }
}
