<?php

namespace App\Http\Controllers;

use App\Models\Ciclo;
use App\Models\Cliente;
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
                $usuario->anos_empresa = now()->diffInYears($usuario->data_registro);

                return $usuario;
            });

        return view('dashboard', compact(
            'totalUsuariosAtivos',
            'totalClientesPJ',
            'totalClientesPF',
            'cicloAtual',
            'totalTarefasCiclo',
            'tarefasUsuarioCiclo',
            'tarefasConcluidasHoje',
            'aniversariantesHoje',
            'aniversariantesEmpresaHoje',
        ));
    }
}
