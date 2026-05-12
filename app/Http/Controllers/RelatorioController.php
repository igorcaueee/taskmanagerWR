<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Departamento;
use App\Models\Etapa;
use App\Models\Produto;
use App\Models\Tarefa;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class RelatorioController extends Controller
{
    public function index(Request $request): View
    {
        [$dataInicio, $dataFim] = $this->resolverPeriodo($request);

        $baseQuery = fn () => Tarefa::query()
            ->whereBetween('data_vencimento', [$dataInicio, $dataFim]);

        // ── KPI cards ──────────────────────────────────────────────────
        $totalTarefas = $baseQuery()->count();

        $totalConcluidas = $baseQuery()
            ->whereNotNull('data_conclusao')
            ->count();

        $totalVencidas = $baseQuery()
            ->whereNull('data_conclusao')
            ->where('data_vencimento', '<', now()->startOfDay())
            ->count();

        $concluidasEstaSemana = Tarefa::query()
            ->whereNotNull('data_conclusao')
            ->whereBetween('data_conclusao', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        // ── Tarefas concluídas por responsável ─────────────────────────
        $porResponsavel = $baseQuery()
            ->whereNotNull('data_conclusao')
            ->selectRaw('responsavel_id, count(*) as total')
            ->groupBy('responsavel_id')
            ->with('responsavel')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($t) => [
                'nome' => $t->responsavel->nome ?? 'Sem responsável',
                'total' => $t->total,
            ]);

        // ── Tarefas por etapa ──────────────────────────────────────────
        $porEtapa = $baseQuery()
            ->selectRaw('etapa_id, count(*) as total')
            ->groupBy('etapa_id')
            ->with('etapa')
            ->get()
            ->map(fn ($t) => [
                'nome' => $t->etapa->nome ?? 'Sem etapa',
                'cor' => $t->etapa->cor ?? '#94a3b8',
                'total' => $t->total,
            ]);

        // ── Tarefas por cliente (top 10) ───────────────────────────────
        $porCliente = $baseQuery()
            ->selectRaw('cliente_id, count(*) as total')
            ->groupBy('cliente_id')
            ->with('cliente')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($t) => [
                'nome' => $t->cliente->nome ?? 'Sem cliente',
                'total' => $t->total,
            ]);

        // ── Tarefas por departamento ───────────────────────────────────
        $porDepartamento = $baseQuery()
            ->selectRaw('departamento_id, count(*) as total')
            ->groupBy('departamento_id')
            ->with('departamento')
            ->get()
            ->map(fn ($t) => [
                'nome' => $t->departamento->nome ?? 'Sem departamento',
                'total' => $t->total,
            ]);

        // ── Vencidas vs no prazo ───────────────────────────────────────
        $vencidasVsPrazo = [
            ['label' => 'No prazo', 'total' => $totalTarefas - $totalVencidas],
            ['label' => 'Vencidas', 'total' => $totalVencidas],
        ];

        // ── Tarefas por recorrência ────────────────────────────────────
        $frequenciaLabels = [
            'nenhuma' => 'Não se repete',
            'semanal' => 'Semanal',
            'mensal' => 'Mensal',
            'trimestral' => 'Trimestral',
            'semestral' => 'Semestral',
            'anual' => 'Anual',
        ];

        $porRecorrencia = $baseQuery()
            ->selectRaw('COALESCE(frequencia, \'nenhuma\') as frequencia, count(*) as total')
            ->groupBy('frequencia')
            ->get()
            ->map(fn ($t) => [
                'nome' => $frequenciaLabels[$t->frequencia] ?? $t->frequencia,
                'total' => $t->total,
            ]);

        // ── Evolução mensal (últimos 12 meses, por data_vencimento) ────
        $evolucaoMensal = collect();
        for ($i = 11; $i >= 0; $i--) {
            $mes = now()->subMonths($i)->startOfMonth();
            $evolucaoMensal->push([
                'mes' => $mes->translatedFormat('M/Y'),
                'total' => Tarefa::query()
                    ->whereYear('data_vencimento', $mes->year)
                    ->whereMonth('data_vencimento', $mes->month)
                    ->count(),
                'concluidas' => Tarefa::query()
                    ->whereNotNull('data_conclusao')
                    ->whereYear('data_conclusao', $mes->year)
                    ->whereMonth('data_conclusao', $mes->month)
                    ->count(),
            ]);
        }

        $usuarios = Usuario::query()->where('status', true)->orderBy('nome')->get();

        return view('relatorios.index', compact(
            'dataInicio',
            'dataFim',
            'totalTarefas',
            'totalConcluidas',
            'totalVencidas',
            'concluidasEstaSemana',
            'porResponsavel',
            'porEtapa',
            'porCliente',
            'porDepartamento',
            'vencidasVsPrazo',
            'porRecorrencia',
            'evolucaoMensal',
            'usuarios',
        ));
    }

    public function clientes(Request $request): View
    {
        [$dataInicio, $dataFim] = $this->resolverPeriodo($request);

        $totalClientes = Cliente::query()->count();
        $totalAtivos = Cliente::query()->where('status', 'ativo')->count();
        $totalInativos = Cliente::query()->where('status', '!=', 'ativo')->count();

        $totalPJ = Cliente::query()->where('status', 'ativo')->where('tipo', '1')->count();
        $totalPF = Cliente::query()->where('status', 'ativo')->where('tipo', '0')->count();

        // Clientes com mais tarefas no período
        $clientesComMaisTarefas = Tarefa::query()
            ->whereBetween('data_vencimento', [$dataInicio, $dataFim])
            ->selectRaw('cliente_id, count(*) as total')
            ->groupBy('cliente_id')
            ->with('cliente')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($t) => [
                'nome' => $t->cliente->nome ?? 'Sem cliente',
                'total' => $t->total,
            ]);

        // Clientes com mais tarefas concluídas no período
        $clientesComMaisConcluidas = Tarefa::query()
            ->whereBetween('data_vencimento', [$dataInicio, $dataFim])
            ->whereNotNull('data_conclusao')
            ->selectRaw('cliente_id, count(*) as total')
            ->groupBy('cliente_id')
            ->with('cliente')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($t) => [
                'nome' => $t->cliente->nome ?? 'Sem cliente',
                'total' => $t->total,
            ]);

        // Clientes com tarefas vencidas
        $clientesComVencidas = Tarefa::query()
            ->whereNull('data_conclusao')
            ->where('data_vencimento', '<', now()->startOfDay())
            ->selectRaw('cliente_id, count(*) as total')
            ->groupBy('cliente_id')
            ->with('cliente')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($t) => [
                'nome' => $t->cliente->nome ?? 'Sem cliente',
                'total' => $t->total,
            ]);

        // Novos clientes por mês (últimos 12 meses)
        $novosPorMes = collect();
        for ($i = 11; $i >= 0; $i--) {
            $mes = now()->subMonths($i)->startOfMonth();
            $novosPorMes->push([
                'mes' => $mes->translatedFormat('M/Y'),
                'total' => Cliente::query()
                    ->whereYear('created_at', $mes->year)
                    ->whereMonth('created_at', $mes->month)
                    ->count(),
            ]);
        }

        // Certificados a vencer nos próximos 30 dias
        $certificadosAVencer = Cliente::query()
            ->whereNotNull('vencimento_certificado')
            ->whereDate('vencimento_certificado', '>=', now()->toDateString())
            ->whereDate('vencimento_certificado', '<=', now()->addDays(30)->toDateString())
            ->where('status', 'ativo')
            ->orderBy('vencimento_certificado')
            ->get(['id', 'nome', 'vencimento_certificado']);

        return view('relatorios.clientes', compact(
            'dataInicio',
            'dataFim',
            'totalClientes',
            'totalAtivos',
            'totalInativos',
            'totalPJ',
            'totalPF',
            'clientesComMaisTarefas',
            'clientesComMaisConcluidas',
            'clientesComVencidas',
            'novosPorMes',
            'certificadosAVencer',
        ));
    }

    public function colaboradores(Request $request): View
    {
        [$dataInicio, $dataFim] = $this->resolverPeriodo($request);

        $totalColaboradores = Usuario::query()->count();
        $totalAtivos = Usuario::query()->where('status', true)->count();
        $totalInativos = Usuario::query()->where('status', false)->count();

        // Tarefas concluídas por colaborador no período
        $concluidasPorColab = Tarefa::query()
            ->whereBetween('data_conclusao', [$dataInicio, $dataFim])
            ->whereNotNull('data_conclusao')
            ->selectRaw('responsavel_id, count(*) as total')
            ->groupBy('responsavel_id')
            ->with('responsavel')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($t) => [
                'nome' => $t->responsavel->nome ?? 'Sem responsável',
                'total' => $t->total,
            ]);

        // Total de tarefas abertas por colaborador
        $abertasPorColab = Tarefa::query()
            ->whereNull('data_conclusao')
            ->selectRaw('responsavel_id, count(*) as total')
            ->groupBy('responsavel_id')
            ->with('responsavel')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($t) => [
                'nome' => $t->responsavel->nome ?? 'Sem responsável',
                'total' => $t->total,
            ]);

        // Tarefas vencidas por colaborador
        $vencidasPorColab = Tarefa::query()
            ->whereNull('data_conclusao')
            ->where('data_vencimento', '<', now()->startOfDay())
            ->selectRaw('responsavel_id, count(*) as total')
            ->groupBy('responsavel_id')
            ->with('responsavel')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($t) => [
                'nome' => $t->responsavel->nome ?? 'Sem responsável',
                'total' => $t->total,
            ]);

        // Evolução de conclusões por colaborador (top 5) nos últimos 12 meses
        $topColabs = Tarefa::query()
            ->whereNotNull('data_conclusao')
            ->selectRaw('responsavel_id, count(*) as total')
            ->groupBy('responsavel_id')
            ->with('responsavel')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $evolucaoColabs = collect();
        for ($i = 11; $i >= 0; $i--) {
            $mes = now()->subMonths($i)->startOfMonth();
            $entry = ['mes' => $mes->translatedFormat('M/Y')];
            foreach ($topColabs as $colab) {
                $entry[$colab->responsavel->nome ?? 'N/A'] = Tarefa::query()
                    ->whereNotNull('data_conclusao')
                    ->where('responsavel_id', $colab->responsavel_id)
                    ->whereYear('data_conclusao', $mes->year)
                    ->whereMonth('data_conclusao', $mes->month)
                    ->count();
            }
            $evolucaoColabs->push($entry);
        }

        return view('relatorios.colaboradores', compact(
            'dataInicio',
            'dataFim',
            'totalColaboradores',
            'totalAtivos',
            'totalInativos',
            'concluidasPorColab',
            'abertasPorColab',
            'vencidasPorColab',
            'topColabs',
            'evolucaoColabs',
        ));
    }

    public function produtos(Request $request): View
    {
        $produtos = Produto::withCount('clientes')->orderBy('nome')->get();

        $produtoFiltro = $request->filled('produto_id')
            ? Produto::find($request->integer('produto_id'))
            : null;

        $clientesDoProduto = collect();
        if ($produtoFiltro) {
            $clientesDoProduto = $produtoFiltro->clientes()->orderBy('nome')->get();
        }

        $clientesSemProdutos = Cliente::query()
            ->whereDoesntHave('produtos')
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get();

        return view('relatorios.produtos', compact(
            'produtos',
            'produtoFiltro',
            'clientesDoProduto',
            'clientesSemProdutos',
        ));
    }

    public function geolocalizacao(Request $request): View
    {
        $estadoFiltro = $request->input('estado');
        $cidadeFiltro = $request->input('cidade');
        $statusFiltro = $request->input('status', 'ativo');

        // Totais gerais
        $totalClientes = Cliente::query()->count();
        $totalComLocalizacao = Cliente::query()
            ->whereNotNull('estado')
            ->where('estado', '!=', '')
            ->count();
        $totalSemLocalizacao = $totalClientes - $totalComLocalizacao;

        // Clientes por estado (para gráfico e tabela)
        $porEstado = Cliente::query()
            ->whereNotNull('estado')
            ->where('estado', '!=', '')
            ->when($statusFiltro, fn ($q) => $q->where('status', $statusFiltro))
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->orderByDesc('total')
            ->get();

        // Cidades do estado filtrado (para o select de cidade)
        $cidadesDoEstado = collect();
        if ($estadoFiltro) {
            $cidadesDoEstado = Cliente::query()
                ->where('estado', $estadoFiltro)
                ->whereNotNull('cidade')
                ->where('cidade', '!=', '')
                ->when($statusFiltro, fn ($q) => $q->where('status', $statusFiltro))
                ->selectRaw('cidade, count(*) as total')
                ->groupBy('cidade')
                ->orderBy('cidade')
                ->get();
        }

        // Clientes filtrados para a tabela
        $clientesFiltrados = Cliente::query()
            ->when($estadoFiltro, fn ($q) => $q->where('estado', $estadoFiltro))
            ->when($cidadeFiltro, fn ($q) => $q->where('cidade', $cidadeFiltro))
            ->when($statusFiltro, fn ($q) => $q->where('status', $statusFiltro))
            ->orderBy('estado')
            ->orderBy('cidade')
            ->orderBy('nome')
            ->get(['id', 'nome', 'cidade', 'estado', 'tipo', 'status', 'regime_tributario']);

        return view('relatorios.geolocalizacao', compact(
            'porEstado',
            'cidadesDoEstado',
            'clientesFiltrados',
            'totalClientes',
            'totalComLocalizacao',
            'totalSemLocalizacao',
            'estadoFiltro',
            'cidadeFiltro',
            'statusFiltro',
        ));
    }

    /** @return array{Carbon, Carbon} */
    private function resolverPeriodo(Request $request): array
    {
        $periodo = $request->input('periodo', 'mes');

        return match ($periodo) {
            'hoje' => [now()->startOfDay(), now()->endOfDay()],            'semana' => [now()->startOfWeek(), now()->endOfWeek()],
            'trimestre' => [now()->subMonths(3)->startOfDay(), now()->endOfDay()],
            'semestre' => [now()->subMonths(6)->startOfDay(), now()->endOfDay()],
            'ano' => [now()->startOfYear(), now()->endOfYear()],
            'personalizado' => [
                Carbon::parse($request->input('data_inicio', now()->startOfMonth()))->startOfDay(),
                Carbon::parse($request->input('data_fim', now()->endOfMonth()))->endOfDay(),
            ],
            default => [now()->startOfMonth(), now()->endOfMonth()], // mes
        };
    }
}
