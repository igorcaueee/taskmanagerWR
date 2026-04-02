<?php

namespace App\Http\Controllers;

use App\Models\Compromisso;
use App\Models\Tarefa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AgendaController extends Controller
{
    public function showAgenda(Request $request): View
    {
        $mes = $request->integer('mes', now()->month);
        $ano = $request->integer('ano', now()->year);

        if ($mes < 1 || $mes > 12) {
            $mes = now()->month;
        }

        $primeiroDia = Carbon::create($ano, $mes, 1);
        $ultimoDia = $primeiroDia->copy()->endOfMonth();

        $usuario = Auth::user();
        $podeVerTodas = in_array($usuario->cargo, ['diretor', 'supervisor']);

        $tarefasQuery = Tarefa::with(['etapa', 'cliente', 'responsavel'])
            ->whereBetween('data_vencimento', [$primeiroDia->toDateString(), $ultimoDia->toDateString()]);

        if (! $podeVerTodas) {
            $tarefasQuery->where('responsavel_id', $usuario->id);
        }

        $tarefasPorDia = $tarefasQuery
            ->orderBy('data_vencimento')
            ->get()
            ->groupBy(fn (Tarefa $t) => $t->data_vencimento->format('Y-m-d'));

        $compromissosPorDia = Compromisso::whereBetween('data', [$primeiroDia->toDateString(), $ultimoDia->toDateString()])
            ->orderBy('hora')
            ->get()
            ->groupBy(fn (Compromisso $c) => $c->data->format('Y-m-d'));

        // Build the calendar weeks (Sun–Sat)
        $semanas = [];
        $diaInicio = $primeiroDia->copy()->startOfWeek(Carbon::SUNDAY);
        $diaFim = $ultimoDia->copy()->endOfWeek(Carbon::SATURDAY);
        $dia = $diaInicio->copy();

        while ($dia <= $diaFim) {
            $semana = [];
            for ($i = 0; $i < 7; $i++) {
                $semana[] = $dia->copy();
                $dia->addDay();
            }
            $semanas[] = $semana;
        }

        $mesAnterior = $primeiroDia->copy()->subMonth();
        $proximoMes = $primeiroDia->copy()->addMonth();

        return view('agenda.home', compact(
            'primeiroDia',
            'semanas',
            'tarefasPorDia',
            'compromissosPorDia',
            'mesAnterior',
            'proximoMes',
        ));
    }

    public function formCompromisso(Request $request): View
    {
        $compromisso = null;

        if ($request->filled('id')) {
            $compromisso = Compromisso::findOrFail($request->integer('id'));
        }

        $dataInicial = $request->string('data')->toString();

        return view('agenda.partials.form-compromisso', compact('compromisso', 'dataInicial'));
    }

    public function storeCompromisso(Request $request): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'data' => ['required', 'date'],
            'hora' => ['nullable', 'date_format:H:i'],
            'cor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ])->validate();

        $validated['criado_por'] = Auth::id();

        Compromisso::create($validated);

        $data = Carbon::parse($validated['data']);

        return redirect()->route('agenda', ['mes' => $data->month, 'ano' => $data->year]);
    }

    public function updateCompromisso(Request $request, int $id): RedirectResponse
    {
        $compromisso = Compromisso::findOrFail($id);

        $validated = Validator::make($request->all(), [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'data' => ['required', 'date'],
            'hora' => ['nullable', 'date_format:H:i'],
            'cor' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ])->validate();

        $compromisso->update($validated);

        $data = Carbon::parse($validated['data']);

        return redirect()->route('agenda', ['mes' => $data->month, 'ano' => $data->year]);
    }

    public function destroyCompromisso(int $id): RedirectResponse
    {
        $compromisso = Compromisso::findOrFail($id);
        $mes = $compromisso->data->month;
        $ano = $compromisso->data->year;

        $compromisso->delete();

        return redirect()->route('agenda', ['mes' => $mes, 'ano' => $ano]);
    }

    public function detalheCompromisso(int $id): View
    {
        $compromisso = Compromisso::with('criador')->findOrFail($id);

        return view('agenda.partials.detalhe-compromisso', compact('compromisso'));
    }

    public function detalheTarefa(int $id): View
    {
        $tarefa = Tarefa::with([
            'etapa',
            'cliente',
            'responsavel',
            'supervisor',
            'departamento',
            'historico.etapaAnterior',
            'historico.etapaNova',
            'historico.alteradoPor',
        ])->findOrFail($id);

        return view('agenda.partials.detalhe-tarefa', compact('tarefa'));
    }
}
