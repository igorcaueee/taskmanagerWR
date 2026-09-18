<?php

namespace App\Http\Controllers;

use App\Models\Ciclo;
use App\Models\Cliente;
use App\Models\DocumentoFiscal;
use App\Models\SimplesDasProcessamento;
use App\Models\Tarefa;
use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function showDashboard(): View
    {
        $totalUsuariosAtivos = Usuario::query()->where('status', true)->count();

        $clientesAtivos = Cliente::query()->where('status', 'ativo');
        $totalClientesAtivos = (clone $clientesAtivos)->count();
        $totalClientesPJ = (clone $clientesAtivos)->where('tipo', '1')->count();
        $totalClientesPF = (clone $clientesAtivos)->where('tipo', '0')->count();

        $cicloAtual = Ciclo::current();

        $totalTarefasCiclo = Tarefa::query()
            ->where('ciclo_id', $cicloAtual->id)
            ->count();

        $tarefasUsuarioCiclo = Tarefa::query()
            ->where('ciclo_id', $cicloAtual->id)
            ->where('responsavel_id', Auth::id())
            ->count();

        $tarefasConcluidasHoje = Tarefa::query()
            ->whereDate('data_conclusao', now()->toDateString())
            ->count();

        $totalXmlsBaixadosMes = DocumentoFiscal::query()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        // PGDAS se refere sempre à receita do mês anterior (mesma convenção
        // usada em SimplesNacionalController::telaDas()).
        $periodoPgdas = now()->subMonthNoOverflow()->format('Ym');

        $clientesSnAtivos = Cliente::query()
            ->where('regime_tributario', 'Simples Nacional')
            ->where('status', 'ativo')
            ->get(['id', 'cpfcnpj']);

        // Agrupa por raiz de CNPJ (matriz + filiais): a transmissão do PGDAS
        // fica registrada só no cliente que é a matriz do grupo, então
        // contar cliente a cliente faria toda filial aparecer como pendente
        // mesmo já coberta pela transmissão da matriz. Mesmo critério de
        // agrupamento usado em PgdasdService::buscarClientesDoGrupoEconomico().
        $gruposSn = $clientesSnAtivos->groupBy(function (Cliente $cliente) {
            $digitos = preg_replace('/\D/', '', $cliente->cpfcnpj ?? '');

            return strlen($digitos) === 14 ? substr($digitos, 0, 8) : 'cliente-'.$cliente->id;
        });

        $totalGruposSn = $gruposSn->count();

        $clienteIdsComPgdasEnviado = SimplesDasProcessamento::query()
            ->where('periodo_apuracao', $periodoPgdas)
            ->whereIn('status', ['sucesso', 'ja_transmitido'])
            ->pluck('cliente_id')
            ->flip();

        $totalPgdasEnviados = $gruposSn
            ->filter(fn ($clientesDoGrupo) => $clientesDoGrupo->contains(
                fn (Cliente $cliente) => $clienteIdsComPgdasEnviado->has($cliente->id)
            ))
            ->count();

        $totalPgdasPendentes = $totalGruposSn - $totalPgdasEnviados;

        $aniversariantesHoje = Usuario::query()
            ->whereNotNull('data_nascimento')
            ->whereMonth('data_nascimento', now()->month)
            ->whereDay('data_nascimento', now()->day)
            ->orderBy('nome')
            ->get();

        $aniversariantesEmpresaHoje = Usuario::query()
            ->whereNotNull('data_registro')
            ->whereMonth('data_registro', now()->month)
            ->whereDay('data_registro', now()->day)
            ->where('data_registro', '<', now()->startOfDay())
            ->orderBy('data_registro')
            ->get()
            ->map(function ($usuario) {
                $usuario->anos_empresa = (int) $usuario->data_registro->diffInYears(now());

                return $usuario;
            });

        return view('dashboard', compact(
            'totalUsuariosAtivos',
            'totalClientesAtivos',
            'totalClientesPJ',
            'totalClientesPF',
            'cicloAtual',
            'totalTarefasCiclo',
            'tarefasUsuarioCiclo',
            'tarefasConcluidasHoje',
            'totalXmlsBaixadosMes',
            'aniversariantesHoje',
            'aniversariantesEmpresaHoje',
            'periodoPgdas',
            'totalGruposSn',
            'totalPgdasEnviados',
            'totalPgdasPendentes',
        ));
    }
}
