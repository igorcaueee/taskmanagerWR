<?php

namespace App\Http\Controllers;

use App\Models\Ciclo;
use App\Models\Cliente;
use App\Models\Departamento;
use App\Models\Etapa;
use App\Models\RelTarefa;
use App\Models\Tarefa;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class TarefaController extends Controller
{
    public function showTarefas(Request $request): View
    {
        $usuario = Auth::user();
        $podeVerTodas = in_array($usuario->cargo, ['diretor', 'supervisor']);

        $query = Tarefa::with(['cliente', 'departamento', 'etapa', 'responsavel'])
            ->orderBy('data_vencimento');

        if (! $podeVerTodas) {
            $query->where('responsavel_id', $usuario->id);
        }

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->integer('cliente_id'));
        }

        if ($request->filled('etapa_id')) {
            $query->where('etapa_id', $request->integer('etapa_id'));
        }

        if ($podeVerTodas && $request->filled('responsavel_id')) {
            $query->where('responsavel_id', $request->integer('responsavel_id'));
        }

        if ($request->filled('busca')) {
            $busca = '%'.$request->string('busca').'%';
            $query->where('titulo', 'like', $busca);
        }

        if ($request->filled('frequencia')) {
            if ($request->input('frequencia') === 'nenhuma') {
                $query->where(function ($q) {
                    $q->where('frequencia', 'nenhuma')->orWhereNull('frequencia');
                });
            } else {
                $query->where('frequencia', $request->input('frequencia'));
            }
        }

        $tarefas = $query->get();

        $clientes = Cliente::orderBy('nome')->get();
        $etapas = Etapa::where('visivel', true)->orderBy('ordem')->get();
        $usuarios = $podeVerTodas ? Usuario::orderBy('nome')->get() : collect();

        return view('tarefas.home', compact('tarefas', 'clientes', 'etapas', 'usuarios', 'podeVerTodas'));
    }

    public function showTarefasList(Request $request): View
    {
        $usuario = Auth::user();
        $podeVerTodas = in_array($usuario->cargo, ['diretor', 'supervisor']);

        $etapas = Etapa::where('visivel', true)->orderBy('ordem')->get();

        // Determina o ciclo selecionado (usa o ciclo atual se não informado)
        $cicloSelecionado = $request->filled('ciclo_id')
            ? Ciclo::findOrFail($request->integer('ciclo_id'))
            : Ciclo::current();

        $cicloPrev = $cicloSelecionado->anterior();
        $cicloNext = $cicloSelecionado->proximo();

        $query = Tarefa::with(['cliente', 'departamento', 'etapa', 'responsavel', 'ciclo'])
            ->orderBy('passou_ciclo', 'desc')
            ->orderBy('data_vencimento');

        if (! $podeVerTodas) {
            $query->where('responsavel_id', $usuario->id);
        }

        // Filtra pelo ciclo selecionado (inclui tarefas sem ciclo_id que caem no intervalo)
        $query->where(function ($q) use ($cicloSelecionado) {
            $q->where('ciclo_id', $cicloSelecionado->id)
                ->orWhere(function ($q2) use ($cicloSelecionado) {
                    $q2->whereNull('ciclo_id')
                        ->whereBetween('data_vencimento', [
                            $cicloSelecionado->data_inicio,
                            $cicloSelecionado->data_fim,
                        ]);
                });
        });

        if ($request->filled('departamento_id')) {
            $query->where('departamento_id', $request->integer('departamento_id'));
        }

        if ($podeVerTodas && $request->filled('responsavel_id')) {
            $query->where('responsavel_id', $request->integer('responsavel_id'));
        }

        $tarefas = $query->get()->groupBy('etapa_id');

        $departamentos = Departamento::orderBy('nome')->get();
        $usuarios = $podeVerTodas ? Usuario::orderBy('nome')->get() : collect();

        return view('tarefas.list', compact(
            'tarefas',
            'etapas',
            'departamentos',
            'usuarios',
            'podeVerTodas',
            'cicloSelecionado',
            'cicloPrev',
            'cicloNext',
        ));
    }

    public function formCreate(): View
    {
        $clientes = Cliente::orderBy('nome')->get();
        $departamentos = Departamento::orderBy('nome')->get();
        $etapas = Etapa::where('visivel', true)->orderBy('ordem')->get();
        $usuarios = Usuario::orderBy('nome')->get();
        $etapaDefault = $etapas->first(fn ($e) => strtolower(trim($e->nome)) === 'a fazer')?->id
            ?? $etapas->first()?->id;

        return view('tarefas.partials.formTarefa', [
            'tarefa' => null,
            'clientes' => $clientes,
            'departamentos' => $departamentos,
            'etapas' => $etapas,
            'usuarios' => $usuarios,
            'etapaDefault' => $etapaDefault,
        ]);
    }

    public function formEdit(int $id): View
    {
        $tarefa = Tarefa::with([
            'historico.etapaAnterior',
            'historico.etapaNova',
            'historico.alteradoPor',
        ])->findOrFail($id);

        $clientes = Cliente::orderBy('nome')->get();
        $departamentos = Departamento::orderBy('nome')->get();
        $etapas = Etapa::where('visivel', true)->orderBy('ordem')->get();
        $usuarios = Usuario::orderBy('nome')->get();

        return view('tarefas.partials.formTarefa', compact('tarefa', 'clientes', 'departamentos', 'etapas', 'usuarios'));
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->only([
            'titulo', 'descricao', 'cliente_id', 'departamento_id',
            'etapa_id', 'responsavel_id', 'supervisor_id', 'data_vencimento', 'prioridade', 'frequencia',
        ]);

        $validator = Validator::make($data, [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'cliente_id' => ['required', 'exists:clientes,id'],
            'departamento_id' => ['required', 'exists:departamentos,id'],
            'etapa_id' => ['required', 'exists:etapas,id'],
            'responsavel_id' => ['nullable', 'exists:usuarios,id'],
            'supervisor_id' => ['nullable', 'exists:usuarios,id'],
            'data_vencimento' => ['required', 'date'],
            'prioridade' => ['required', 'integer', 'min:1', 'max:5'],
            'frequencia' => ['nullable', 'in:nenhuma,semanal,mensal,trimestral,semestral,anual'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $frequencia = $data['frequencia'] ?? 'nenhuma';

        Tarefa::create([
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'cliente_id' => $data['cliente_id'],
            'departamento_id' => $data['departamento_id'],
            'etapa_id' => $data['etapa_id'],
            'responsavel_id' => $data['responsavel_id'] ?? null,
            'supervisor_id' => $data['supervisor_id'] ?? null,
            'criado_por' => Auth::id(),
            'data_vencimento' => $data['data_vencimento'],
            'prioridade' => $data['prioridade'],
            'ciclo_id' => Ciclo::findOrCreateForDate(Carbon::parse($data['data_vencimento']))->id,
            'frequencia' => $frequencia,
            'recorrente' => $frequencia !== 'nenhuma',
        ]);

        return Redirect::back()->with('success', 'Tarefa criada com sucesso.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $tarefa = Tarefa::findOrFail($id);

        $data = $request->only([
            'titulo', 'descricao', 'cliente_id', 'departamento_id',
            'etapa_id', 'responsavel_id', 'supervisor_id', 'data_vencimento', 'prioridade', 'frequencia',
        ]);

        $validator = Validator::make($data, [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'cliente_id' => ['required', 'exists:clientes,id'],
            'departamento_id' => ['required', 'exists:departamentos,id'],
            'etapa_id' => ['required', 'exists:etapas,id'],
            'responsavel_id' => ['nullable', 'exists:usuarios,id'],
            'supervisor_id' => ['nullable', 'exists:usuarios,id'],
            'data_vencimento' => ['required', 'date'],
            'prioridade' => ['required', 'integer', 'min:1', 'max:5'],
            'frequencia' => ['nullable', 'in:nenhuma,semanal,mensal,trimestral,semestral,anual'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $frequencia = $data['frequencia'] ?? 'nenhuma';
        $novaEtapaForm = Etapa::findOrFail((int) $data['etapa_id']);
        $isFinalizadoForm = strtolower(trim($novaEtapaForm->nome)) === 'finalizado';

        $etapaAnteriorId = $tarefa->etapa_id;

        $tarefa->update([
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'cliente_id' => $data['cliente_id'],
            'departamento_id' => $data['departamento_id'],
            'etapa_id' => $data['etapa_id'],
            'responsavel_id' => $data['responsavel_id'] ?? null,
            'supervisor_id' => $data['supervisor_id'] ?? null,
            'data_vencimento' => $data['data_vencimento'],
            'prioridade' => $data['prioridade'],
            'ciclo_id' => Ciclo::findOrCreateForDate(Carbon::parse($data['data_vencimento']))->id,
            'passou_ciclo' => false,
            'frequencia' => $frequencia,
            'recorrente' => $frequencia !== 'nenhuma',
            'data_conclusao' => $isFinalizadoForm
                ? ($tarefa->data_conclusao ?? now())
                : null,
        ]);

        if ((int) $etapaAnteriorId !== (int) $data['etapa_id']) {
            RelTarefa::create([
                'tarefa_id' => $tarefa->id,
                'etapa_anterior_id' => $etapaAnteriorId,
                'etapa_nova_id' => $data['etapa_id'],
                'alterado_por' => Auth::id(),
            ]);
        }

        if ($isFinalizadoForm && $frequencia !== 'nenhuma') {
            $this->criarProximaOcorrencia($tarefa);
        }

        return Redirect::back()->with('success', 'Tarefa atualizada com sucesso.');
    }

    public function delete(int $id): RedirectResponse
    {
        $tarefa = Tarefa::findOrFail($id);
        $tarefa->delete();

        return Redirect::back()->with('success', 'Tarefa excluída com sucesso.');
    }

    public function updateEtapa(Request $request, int $id): JsonResponse
    {
        $tarefa = Tarefa::findOrFail($id);

        $validator = Validator::make($request->only('etapa_id'), [
            'etapa_id' => ['required', 'exists:etapas,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $etapaAnteriorId = $tarefa->etapa_id;
        $novaEtapa = Etapa::findOrFail($request->integer('etapa_id'));
        $isFinalizado = strtolower(trim($novaEtapa->nome)) === 'finalizado';

        $tarefa->update([
            'etapa_id' => $novaEtapa->id,
            'data_conclusao' => $isFinalizado ? now() : null,
        ]);

        RelTarefa::create([
            'tarefa_id' => $tarefa->id,
            'etapa_anterior_id' => $etapaAnteriorId,
            'etapa_nova_id' => $novaEtapa->id,
            'alterado_por' => Auth::id(),
        ]);

        if ($isFinalizado && $tarefa->recorrente && $tarefa->frequencia !== 'nenhuma') {
            $this->criarProximaOcorrencia($tarefa);
        }

        return response()->json(['success' => true, 'finalizado' => $isFinalizado]);
    }

    private function criarProximaOcorrencia(Tarefa $tarefa): void
    {
        $novaData = match ($tarefa->frequencia) {
            'semanal' => Carbon::parse($tarefa->data_vencimento)->addWeek(),
            'mensal' => Carbon::parse($tarefa->data_vencimento)->addMonth(),
            'trimestral' => Carbon::parse($tarefa->data_vencimento)->addMonths(3),
            'semestral' => Carbon::parse($tarefa->data_vencimento)->addMonths(6),
            'anual' => Carbon::parse($tarefa->data_vencimento)->addYear(),
            default => null,
        };

        if (is_null($novaData)) {
            return;
        }

        $etapaInicial = Etapa::orderBy('ordem')
            ->get()
            ->first(fn ($e) => strtolower(trim($e->nome)) === 'a fazer')
            ?? Etapa::orderBy('ordem')->first();

        Tarefa::create([
            'titulo' => $tarefa->titulo,
            'descricao' => $tarefa->descricao,
            'cliente_id' => $tarefa->cliente_id,
            'departamento_id' => $tarefa->departamento_id,
            'etapa_id' => $etapaInicial->id,
            'responsavel_id' => $tarefa->responsavel_id,
            'supervisor_id' => $tarefa->supervisor_id,
            'criado_por' => $tarefa->criado_por,
            'data_vencimento' => $novaData->toDateString(),
            'prioridade' => $tarefa->prioridade,
            'frequencia' => $tarefa->frequencia,
            'recorrente' => true,
            'tarefa_original_id' => $tarefa->tarefa_original_id ?? $tarefa->id,
            'ciclo_id' => Ciclo::findOrCreateForDate($novaData)->id,
        ]);
    }

    public function passarParaProximoCiclo(int $id): JsonResponse
    {
        $tarefa = Tarefa::findOrFail($id);

        $etapaAnteriorId = $tarefa->etapa_id;

        $etapaTransferido = Etapa::where('nome', 'Transferido para o próximo ciclo')->first();

        $proximoCiclo = Ciclo::findOrCreateForDate(
            Carbon::parse($tarefa->data_vencimento)->addWeek()->startOfWeek(Carbon::MONDAY)
        );

        $tarefa->update([
            'ciclo_id' => $proximoCiclo->id,
            'data_vencimento' => $proximoCiclo->data_inicio,
            'passou_ciclo' => true,            'prioridade' => 5,        ]);

        if ($etapaTransferido) {
            RelTarefa::create([
                'tarefa_id' => $tarefa->id,
                'etapa_anterior_id' => $etapaAnteriorId,
                'etapa_nova_id' => $etapaTransferido->id,
                'alterado_por' => Auth::id(),
            ]);
        }

        return response()->json([
            'success' => true,
            'ciclo_nome' => $proximoCiclo->nome,
        ]);
    }

    public function detalhe(int $id): JsonResponse
    {
        $tarefa = Tarefa::with([
            'cliente',
            'departamento',
            'etapa',
            'responsavel',
            'supervisor',
            'historico.etapaAnterior',
            'historico.etapaNova',
            'historico.alteradoPor',
        ])->findOrFail($id);

        $prioridadeLabels = [1 => 'Baixa', 2 => 'Normal', 3 => 'Média', 4 => 'Alta', 5 => 'Urgente'];

        return response()->json([
            'id' => $tarefa->id,
            'titulo' => $tarefa->titulo,
            'descricao' => $tarefa->descricao,
            'cliente' => $tarefa->cliente?->nome,
            'departamento' => $tarefa->departamento?->nome,
            'etapa' => ['nome' => $tarefa->etapa?->nome, 'cor' => $tarefa->etapa?->cor ?? '#6b7280'],
            'responsavel' => $tarefa->responsavel?->nome,
            'supervisor' => $tarefa->supervisor?->nome,
            'data_vencimento' => $tarefa->data_vencimento?->format('d/m/Y'),
            'atrasada' => (bool) $tarefa->atrasada,
            'prioridade' => $prioridadeLabels[$tarefa->prioridade] ?? $tarefa->prioridade,
            'recorrente' => $tarefa->recorrente,
            'frequencia' => $tarefa->frequencia,
            'criado_em' => $tarefa->created_at?->format('d/m/Y H:i'),
            'historico' => $tarefa->historico->sortByDesc('created_at')->map(fn ($r) => [
                'etapa_anterior' => $r->etapaAnterior?->nome,
                'etapa_nova' => $r->etapaNova?->nome,
                'etapa_nova_cor' => $r->etapaNova?->cor ?? '#6b7280',
                'alterado_por' => $r->alteradoPor?->nome,
                'data' => $r->created_at->format('d/m/Y H:i'),
            ])->values(),
        ]);
    }
}
