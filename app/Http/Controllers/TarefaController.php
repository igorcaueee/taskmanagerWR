<?php

namespace App\Http\Controllers;

use App\Models\Ciclo;
use App\Models\Cliente;
use App\Models\Departamento;
use App\Models\Etapa;
use App\Models\RelTarefa;
use App\Models\Tarefa;
use App\Models\TarefaUpload;
use App\Models\TipoTarefa;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class TarefaController extends Controller
{
    public function showTarefas(Request $request): View
    {
        $usuario = Auth::user();
        $podeVerTodas = in_array($usuario->cargo, ['diretor', 'ti', 'supervisor']);

        $query = Tarefa::with(['cliente', 'clientes', 'departamento', 'etapa', 'responsavel'])
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
        $podeVerTodas = in_array($usuario->cargo, ['diretor', 'ti', 'supervisor']);

        $etapas = Etapa::where('visivel', true)->orderBy('ordem')->get();

        // Determina o ciclo selecionado (usa o ciclo atual se não informado)
        $cicloSelecionado = $request->filled('ciclo_id')
            ? Ciclo::findOrFail($request->integer('ciclo_id'))
            : Ciclo::current();

        // Ao abrir o ciclo atual, traz automaticamente tarefas "A Fazer" de ciclos passados
        if ($cicloSelecionado->status === 'atual') {
            $this->trazerAFazerPendentesParaCicloAtual($cicloSelecionado);
        }

        $cicloPrev = $cicloSelecionado->anterior();
        $cicloNext = $cicloSelecionado->proximo();

        $query = Tarefa::with(['cliente', 'clientes', 'departamento', 'etapa', 'responsavel', 'ciclo'])
            ->orderBy('passou_ciclo', 'desc')
            ->orderBy('data_vencimento');

        if (! $podeVerTodas) {
            $query->where('responsavel_id', $usuario->id);
        }

        // Filtro por data de vencimento (substitui o filtro de ciclo quando ativo)
        $filtroDataTipo = $request->input('filtro_data_tipo');
        $dataEspecifica = $request->input('data_especifica');
        $dataIniciofiltro = $request->input('data_inicio_filtro');
        $dataFimFiltro = $request->input('data_fim_filtro');
        $filtroDataAtivo = false;

        if ($filtroDataTipo === 'data_especifica' && $dataEspecifica) {
            $query->whereDate('data_vencimento', $dataEspecifica);
            $filtroDataAtivo = true;
        } elseif ($filtroDataTipo === 'periodo' && $dataIniciofiltro && $dataFimFiltro) {
            $query->whereBetween('data_vencimento', [$dataIniciofiltro, $dataFimFiltro]);
            $filtroDataAtivo = true;
        } else {
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
        }

        if ($request->filled('departamento_id')) {
            $query->where('departamento_id', $request->integer('departamento_id'));
        }

        if ($podeVerTodas && $request->filled('responsavel_id')) {
            $query->where('responsavel_id', $request->integer('responsavel_id'));
        }

        if ($request->filled('recorrencia')) {
            if ($request->input('recorrencia') === 'recorrente') {
                $query->where('recorrente', true);
            } elseif ($request->input('recorrencia') === 'nao_recorrente') {
                $query->where(function ($q) {
                    $q->where('recorrente', false)->orWhereNull('recorrente');
                });
            }
        }

        if ($request->filled('cliente_id')) {
            $clienteId = $request->integer('cliente_id');
            $query->where(function ($q) use ($clienteId) {
                $q->where('cliente_id', $clienteId)
                    ->orWhereHas('clientes', fn ($q2) => $q2->where('clientes.id', $clienteId));
            });
        }

        if ($request->filled('tipo_tarefa_id')) {
            $query->where('tipo_tarefa_id', $request->integer('tipo_tarefa_id'));
        }

        $tarefas = $query->get()->groupBy('etapa_id');

        $departamentos = Departamento::orderBy('nome')->get();
        $usuarios = $podeVerTodas ? Usuario::orderBy('nome')->get() : collect();
        $clientes = Cliente::orderBy('nome')->get();
        $tiposTarefa = TipoTarefa::orderBy('nome')->get();

        return view('tarefas.list', compact(
            'tarefas',
            'etapas',
            'departamentos',
            'usuarios',
            'clientes',
            'tiposTarefa',
            'podeVerTodas',
            'cicloSelecionado',
            'cicloPrev',
            'cicloNext',
            'filtroDataAtivo',
        ));
    }

    public function formCreate(): View
    {
        $clientes = Cliente::orderBy('nome')->get();
        $etapas = Etapa::where('visivel', true)->orderBy('ordem')->get();
        $usuarios = Usuario::with('departamento')->orderBy('nome')->get();
        $etapaDefault = $etapas->first(fn ($e) => strtolower(trim($e->nome)) === 'a fazer')?->id
            ?? $etapas->first()?->id;

        $usuariosDepartamentos = $usuarios->mapWithKeys(fn ($u) => [
            $u->id => ['id' => $u->departamento_id, 'nome' => $u->departamento?->nome ?? '—'],
        ]);

        $tiposTarefa = TipoTarefa::orderBy('nome')->get();

        return view('tarefas.partials.formTarefa', [
            'tarefa' => null,
            'clientes' => $clientes,
            'etapas' => $etapas,
            'usuarios' => $usuarios,
            'etapaDefault' => $etapaDefault,
            'usuariosDepartamentos' => $usuariosDepartamentos,
            'tiposTarefa' => $tiposTarefa,
        ]);
    }

    public function formEdit(int $id): View
    {
        $tarefa = Tarefa::with([
            'clientes',
            'historico.etapaAnterior',
            'historico.etapaNova',
            'historico.responsavelAnterior',
            'historico.responsavelNovo',
            'historico.alteradoPor',
        ])->findOrFail($id);

        $clientes = Cliente::orderBy('nome')->get();
        $etapas = Etapa::where('visivel', true)->orderBy('ordem')->get();
        $usuarios = Usuario::with('departamento')->orderBy('nome')->get();

        $usuariosDepartamentos = $usuarios->mapWithKeys(fn ($u) => [
            $u->id => ['id' => $u->departamento_id, 'nome' => $u->departamento?->nome ?? '—'],
        ]);

        $podeMudarResponsavel = (int) Auth::id() === (int) $tarefa->supervisor_id;

        $selectedClienteIds = $tarefa->clientes->pluck('id')->toArray();

        $tiposTarefa = TipoTarefa::orderBy('nome')->get();

        return view('tarefas.partials.formTarefa', compact('tarefa', 'clientes', 'etapas', 'usuarios', 'usuariosDepartamentos', 'podeMudarResponsavel', 'selectedClienteIds', 'tiposTarefa'));
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->only([
            'titulo', 'descricao', 'cliente_ids', 'tipo_tarefa_id',
            'etapa_id', 'responsavel_id', 'supervisor_id', 'data_vencimento', 'prioridade', 'frequencia',
            'requer_envio_arquivo',
        ]);

        $validator = Validator::make($data, [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'cliente_ids' => ['nullable', 'array'],
            'cliente_ids.*' => ['exists:clientes,id'],
            'tipo_tarefa_id' => ['nullable', 'exists:tipos_tarefa,id'],
            'etapa_id' => ['required', 'exists:etapas,id'],
            'responsavel_id' => ['required', 'exists:usuarios,id'],
            'supervisor_id' => ['required', 'exists:usuarios,id'],
            'data_vencimento' => ['required', 'date'],
            'prioridade' => ['required', 'integer', 'min:1', 'max:5'],
            'frequencia' => ['nullable', 'in:nenhuma,semanal,mensal,trimestral,semestral,anual'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $frequencia = $data['frequencia'] ?? 'nenhuma';

        $departamentoId = Usuario::find($data['responsavel_id'] ?? null)?->departamento_id
            ?? Departamento::orderBy('id')->value('id');

        $cicloId = Ciclo::findOrCreateForDate(Carbon::parse($data['data_vencimento']))->id;

        $clienteIds = ! empty($data['cliente_ids']) ? $data['cliente_ids'] : [null];

        $dataFimRecorrencia = $frequencia !== 'nenhuma'
            ? Carbon::parse($data['data_vencimento'])->addYear()->toDateString()
            : null;

        foreach ($clienteIds as $clienteId) {
            $tarefa = Tarefa::create([
                'titulo' => $data['titulo'],
                'descricao' => $data['descricao'] ?? null,
                'tipo_tarefa_id' => $data['tipo_tarefa_id'] ?? null,
                'cliente_id' => $clienteId,
                'departamento_id' => $departamentoId,
                'etapa_id' => $data['etapa_id'],
                'responsavel_id' => $data['responsavel_id'] ?? null,
                'supervisor_id' => $data['supervisor_id'] ?? null,
                'criado_por' => Auth::id(),
                'data_vencimento' => $data['data_vencimento'],
                'prioridade' => $data['prioridade'],
                'ciclo_id' => $cicloId,
                'frequencia' => $frequencia,
                'recorrente' => $frequencia !== 'nenhuma',
                'data_fim_recorrencia' => $dataFimRecorrencia,
                'requer_envio_arquivo' => ! empty($data['requer_envio_arquivo']),
            ]);

            if ($clienteId !== null) {
                $tarefa->clientes()->sync([$clienteId]);
            }

            if ($frequencia !== 'nenhuma') {
                $this->gerarOcorrenciasParaUmAno($tarefa, Carbon::parse($dataFimRecorrencia));
            }
        }

        $count = count($clienteIds);
        $mensagem = $count > 1
            ? "{$count} tarefas criadas com sucesso."
            : 'Tarefa criada com sucesso.';

        return Redirect::back()->with('success', $mensagem);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $tarefa = Tarefa::findOrFail($id);

        $usuario = Auth::user();
        if (! $usuario->canEditarQualquerTarefa() && (int) $tarefa->responsavel_id !== (int) $usuario->id) {
            abort(403);
        }

        $data = $request->only([
            'titulo', 'descricao', 'cliente_ids', 'tipo_tarefa_id',
            'etapa_id', 'responsavel_id', 'supervisor_id', 'data_vencimento', 'prioridade', 'frequencia',
            'requer_envio_arquivo',
        ]);

        $validator = Validator::make($data, [
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'cliente_ids' => ['nullable', 'array'],
            'cliente_ids.*' => ['exists:clientes,id'],
            'tipo_tarefa_id' => ['nullable', 'exists:tipos_tarefa,id'],
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
        $responsavelAnteriorId = $tarefa->responsavel_id;

        $podeMudarResponsavel = (int) Auth::id() === (int) $tarefa->supervisor_id;

        $novoResponsavelId = $podeMudarResponsavel
            ? ($data['responsavel_id'] ?? null)
            : $tarefa->responsavel_id;

        $departamentoId = Usuario::find($novoResponsavelId)?->departamento_id
            ?? $tarefa->departamento_id
            ?? Departamento::orderBy('id')->value('id');

        $clienteIds = ! empty($data['cliente_ids']) ? $data['cliente_ids'] : [];

        $tarefa->update([
            'titulo' => $data['titulo'],
            'descricao' => $data['descricao'] ?? null,
            'tipo_tarefa_id' => $data['tipo_tarefa_id'] ?? null,
            'cliente_id' => $clienteIds[0] ?? null,
            'departamento_id' => $departamentoId,
            'etapa_id' => $data['etapa_id'],
            'responsavel_id' => $novoResponsavelId,
            'supervisor_id' => $podeMudarResponsavel
                ? ($data['supervisor_id'] ?? null)
                : $tarefa->supervisor_id,
            'data_vencimento' => $data['data_vencimento'],
            'prioridade' => $data['prioridade'],
            'ciclo_id' => Ciclo::findOrCreateForDate(Carbon::parse($data['data_vencimento']))->id,
            'passou_ciclo' => false,
            'frequencia' => $frequencia,
            'recorrente' => $frequencia !== 'nenhuma',
            'data_conclusao' => $isFinalizadoForm
                ? ($tarefa->data_conclusao ?? now())
                : null,
            'requer_envio_arquivo' => ! empty($data['requer_envio_arquivo']),
        ]);

        $tarefa->clientes()->sync($clienteIds);

        $etapaMudou = (int) $etapaAnteriorId !== (int) $data['etapa_id'];
        $responsavelMudou = (int) ($responsavelAnteriorId ?? 0) !== (int) ($novoResponsavelId ?? 0);

        if ($etapaMudou || $responsavelMudou) {
            RelTarefa::create([
                'tarefa_id' => $tarefa->id,
                'etapa_anterior_id' => $etapaMudou ? $etapaAnteriorId : null,
                'etapa_nova_id' => $etapaMudou ? $data['etapa_id'] : null,
                'responsavel_anterior_id' => $responsavelMudou ? $responsavelAnteriorId : null,
                'responsavel_novo_id' => $responsavelMudou ? $novoResponsavelId : null,
                'alterado_por' => Auth::id(),
            ]);
        }

        return Redirect::back()->with('success', 'Tarefa atualizada com sucesso.');
    }

    public function delete(int $id): RedirectResponse
    {
        $tarefa = Tarefa::findOrFail($id);

        $usuario = Auth::user();
        if (! $usuario->canEditarQualquerTarefa() && (int) $tarefa->responsavel_id !== (int) $usuario->id) {
            abort(403);
        }

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
        $isImpedimento = strtolower(trim($novaEtapa->nome)) === 'impedimento';
        $observacao = $isImpedimento ? ($request->input('observacao') ?? null) : null;

        $tarefa->update([
            'etapa_id' => $novaEtapa->id,
            'data_conclusao' => $isFinalizado ? now() : null,
        ]);

        RelTarefa::create([
            'tarefa_id' => $tarefa->id,
            'etapa_anterior_id' => $etapaAnteriorId,
            'etapa_nova_id' => $novaEtapa->id,
            'alterado_por' => Auth::id(),
            'observacao' => $observacao,
        ]);

        $ultimaRecorrencia = false;
        if ($isFinalizado && $tarefa->recorrente && $tarefa->frequencia !== 'nenhuma') {
            $originalId = $tarefa->tarefa_original_id ?? $tarefa->id;
            $temProxima = Tarefa::where('tarefa_original_id', $originalId)
                ->where('data_vencimento', '>', $tarefa->data_vencimento)
                ->exists();
            $ultimaRecorrencia = ! $temProxima;
        }

        return response()->json([
            'success' => true,
            'finalizado' => $isFinalizado,
            'requer_envio_arquivo' => $isFinalizado && $tarefa->requer_envio_arquivo,
            'cliente_id' => $tarefa->cliente_id,
            'ultima_recorrencia' => $ultimaRecorrencia,
            'tarefa_id' => $tarefa->id,
        ]);
    }

    private function gerarOcorrenciasParaUmAno(Tarefa $tarefa, Carbon $dataFim): void
    {
        $etapaInicial = Etapa::orderBy('ordem')
            ->get()
            ->first(fn ($e) => strtolower(trim($e->nome)) === 'a fazer')
            ?? Etapa::orderBy('ordem')->first();

        $originalId = $tarefa->tarefa_original_id ?? $tarefa->id;
        $dataAtual = Carbon::parse($tarefa->data_vencimento);

        while (true) {
            $proximaData = match ($tarefa->frequencia) {
                'semanal' => $dataAtual->copy()->addWeek(),
                'mensal' => $dataAtual->copy()->addMonth(),
                'trimestral' => $dataAtual->copy()->addMonths(3),
                'semestral' => $dataAtual->copy()->addMonths(6),
                'anual' => $dataAtual->copy()->addYear(),
                default => null,
            };

            if (is_null($proximaData) || $proximaData->gt($dataFim)) {
                break;
            }

            Tarefa::create([
                'titulo' => $tarefa->titulo,
                'descricao' => $tarefa->descricao,
                'tipo_tarefa_id' => $tarefa->tipo_tarefa_id,
                'cliente_id' => $tarefa->cliente_id,
                'departamento_id' => $tarefa->departamento_id,
                'etapa_id' => $etapaInicial->id,
                'responsavel_id' => $tarefa->responsavel_id,
                'supervisor_id' => $tarefa->supervisor_id,
                'criado_por' => $tarefa->criado_por,
                'data_vencimento' => $proximaData->toDateString(),
                'prioridade' => $tarefa->prioridade,
                'frequencia' => $tarefa->frequencia,
                'recorrente' => true,
                'tarefa_original_id' => $originalId,
                'data_fim_recorrencia' => $dataFim->toDateString(),
                'requer_envio_arquivo' => $tarefa->requer_envio_arquivo,
                'ciclo_id' => Ciclo::findOrCreateForDate($proximaData)->id,
            ]);

            $dataAtual = $proximaData;
        }
    }

    public function renovarRecorrencia(int $id): JsonResponse
    {
        $tarefa = Tarefa::findOrFail($id);

        $originalId = $tarefa->tarefa_original_id ?? $tarefa->id;

        $ultimaTarefa = Tarefa::where(function ($q) use ($originalId) {
            $q->where('id', $originalId)->orWhere('tarefa_original_id', $originalId);
        })->orderByDesc('data_vencimento')->first();

        if (! $ultimaTarefa) {
            return response()->json(['error' => 'Tarefa não encontrada.'], 404);
        }

        $novaDataFim = Carbon::parse($ultimaTarefa->data_vencimento)->addYear();

        $this->gerarOcorrenciasParaUmAno($ultimaTarefa, $novaDataFim);

        return response()->json(['success' => true, 'message' => 'Recorrência renovada por mais 1 ano.']);
    }

    public function passarParaProximoCiclo(int $id): JsonResponse
    {
        $tarefa = Tarefa::findOrFail($id);

        $etapaAnteriorId = $tarefa->etapa_id;

        $etapaTransferido = Etapa::where('nome', 'Transferido para o próximo ciclo')->first();

        $proximoCiclo = Ciclo::findOrCreateForDate(
            Carbon::parse($tarefa->data_vencimento)->addMonth()->startOfMonth()
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

    private function trazerAFazerPendentesParaCicloAtual(Ciclo $cicloAtual): void
    {
        $hoje = Carbon::today();

        $etapaAFazer = Etapa::where('nome', 'A Fazer')->first();
        $etapaTransferido = Etapa::where('nome', 'Transferido para o próximo ciclo')->first();

        if (! $etapaAFazer) {
            return;
        }

        $tarefas = Tarefa::whereHas('ciclo', fn ($q) => $q->where('data_fim', '<', $hoje))
            ->where('etapa_id', $etapaAFazer->id)
            ->where('ciclo_id', '!=', $cicloAtual->id)
            ->get();

        foreach ($tarefas as $tarefa) {
            $tarefa->update([
                'ciclo_id'        => $cicloAtual->id,
                'data_vencimento' => $cicloAtual->data_inicio,
                'passou_ciclo'    => true,
            ]);

            if ($etapaTransferido) {
                RelTarefa::create([
                    'tarefa_id'         => $tarefa->id,
                    'etapa_anterior_id' => $tarefa->etapa_id,
                    'etapa_nova_id'     => $etapaTransferido->id,
                    'alterado_por'      => null,
                ]);
            }
        }
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
            'historico.responsavelAnterior',
            'historico.responsavelNovo',
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
                'etapa_anterior_id' => $r->etapaAnterior?->id,
                'etapa_anterior' => $r->etapaAnterior?->nome,
                'etapa_nova' => $r->etapaNova?->nome,
                'etapa_nova_cor' => $r->etapaNova?->cor ?? '#6b7280',
                'responsavel_anterior' => $r->responsavelAnterior?->nome,
                'responsavel_novo' => $r->responsavelNovo?->nome,
                'alterado_por' => $r->alteradoPor?->nome,
                'observacao' => $r->observacao,
                'data' => $r->created_at->format('d/m/Y H:i'),
            ])->values(),
        ]);
    }

    public function duplicar(Request $request, int $id): JsonResponse
    {
        $tarefa = Tarefa::findOrFail($id);

        $validator = Validator::make($request->only('cliente_ids'), [
            'cliente_ids' => ['required', 'array', 'min:1'],
            'cliente_ids.*' => ['exists:clientes,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $clienteIds = $request->input('cliente_ids');
        $count = 0;

        foreach ($clienteIds as $clienteId) {
            $nova = Tarefa::create([
                'titulo' => $tarefa->titulo,
                'descricao' => $tarefa->descricao,
                'tipo_tarefa_id' => $tarefa->tipo_tarefa_id,
                'cliente_id' => $clienteId,
                'departamento_id' => $tarefa->departamento_id,
                'etapa_id' => $tarefa->etapa_id,
                'responsavel_id' => $tarefa->responsavel_id,
                'supervisor_id' => $tarefa->supervisor_id,
                'criado_por' => Auth::id(),
                'data_vencimento' => $tarefa->data_vencimento,
                'prioridade' => $tarefa->prioridade,
                'ciclo_id' => $tarefa->ciclo_id,
                'frequencia' => $tarefa->frequencia,
                'recorrente' => $tarefa->recorrente,
                'requer_envio_arquivo' => $tarefa->requer_envio_arquivo,
            ]);

            $nova->clientes()->sync([$clienteId]);
            $count++;
        }

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    public function uploadArquivo(Request $request, int $id): JsonResponse
    {
        $tarefa = Tarefa::with('cliente')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'arquivo' => ['required', 'file', 'max:51200'], // 50 MB
            'pasta_categoria' => ['required', 'string', 'in:Contabilidade,Financeiro,Fiscal,Patrimônio,Pessoal'],
            'pasta_periodo' => ['required', 'string', 'max:50', 'regex:/^[\w\s\-\.]+$/u'],
            'tipo_arquivo' => ['nullable', 'string', 'in:pagamento,contrato_social,informacao'],
            'data_vencimento' => ['nullable', 'date'],
            'valor' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $cliente = $tarefa->cliente;

        if (! $cliente || ! $cliente->pasta_arquivos) {
            return response()->json(['error' => 'Este cliente não possui pasta configurada.'], 422);
        }

        $arquivo = $request->file('arquivo');
        $nomeOriginal = $arquivo->getClientOriginalName();
        $categoria = $request->input('pasta_categoria');
        $periodo = $request->input('pasta_periodo');

        $sharedRoot = rtrim(Storage::disk('shared')->path(''), '/');
        $pastaPortal = $sharedRoot.'/'.rtrim($cliente->pasta_arquivos, '/').'/Portal/'.$categoria.'/'.$periodo;

        if (! is_dir($pastaPortal)) {
            mkdir($pastaPortal, 0775, true);
        }

        // Previne sobrescrita: adiciona timestamp se já existir
        $nomeBase = pathinfo($nomeOriginal, PATHINFO_FILENAME);
        $extensao = pathinfo($nomeOriginal, PATHINFO_EXTENSION);
        $nomeArquivo = $nomeOriginal;

        if (file_exists($pastaPortal.'/'.$nomeArquivo)) {
            $nomeArquivo = $nomeBase.'_'.time().($extensao ? '.'.$extensao : '');
        }

        $destinoAbsoluto = $pastaPortal.'/'.$nomeArquivo;
        $arquivo->move($pastaPortal, $nomeArquivo);

        $caminhoDB = rtrim($cliente->pasta_arquivos, '/').'/Portal/'.$categoria.'/'.$periodo.'/'.$nomeArquivo;

        TarefaUpload::create([
            'tarefa_id' => $tarefa->id,
            'cliente_id' => $cliente->id,
            'enviado_por' => Auth::id(),
            'arquivo_nome' => $nomeArquivo,
            'arquivo_path' => $caminhoDB,
            'pasta_categoria' => $categoria,
            'pasta_periodo' => $periodo,
            'tipo_arquivo' => $request->input('tipo_arquivo'),
            'data_vencimento' => $request->input('data_vencimento'),
            'valor' => $request->input('valor'),
            'tamanho' => file_exists($destinoAbsoluto) ? filesize($destinoAbsoluto) : 0,
            'mime_type' => $arquivo->getClientMimeType(),
        ]);

        return response()->json(['success' => true, 'nome' => $nomeArquivo, 'arquivo_path' => $caminhoDB]);
    }

    public function destroyUpload(TarefaUpload $upload): JsonResponse
    {
        $upload->eventos()->delete();
        $upload->delete();

        return response()->json(['success' => true]);
    }

    public function uploadsHistorico(TarefaUpload $upload): JsonResponse
    {
        $upload->load(['tarefa', 'cliente', 'enviadoPor', 'eventos.portalUsuario']);

        $eventos = [];

        $eventos[] = [
            'tipo' => 'enviado',
            'label' => 'Arquivo enviado ao portal',
            'data' => $upload->created_at->format('d/m/Y H:i'),
            'por' => $upload->enviadoPor?->nome ?? 'Sistema',
        ];

        foreach ($upload->eventos as $evento) {
            $eventos[] = [
                'tipo' => $evento->tipo === 'visualizou' ? 'visualizado' : 'baixado',
                'label' => $evento->tipo === 'visualizou' ? 'Visualizado' : 'Baixado',
                'data' => $evento->created_at->format('d/m/Y H:i'),
                'por' => $evento->portalUsuario?->nome ?? 'Cliente',
            ];
        }

        $totalVisualizacoes = $upload->eventos->where('tipo', 'visualizou')->count();
        $totalDownloads = $upload->eventos->where('tipo', 'baixou')->count();

        $pendentes = [];
        if ($totalVisualizacoes === 0) {
            $pendentes[] = ['tipo' => 'visualizado', 'label' => 'Ainda não visualizado', 'data' => null, 'por' => null];
        }
        if ($totalDownloads === 0) {
            $pendentes[] = ['tipo' => 'baixado', 'label' => 'Ainda não baixado', 'data' => null, 'por' => null];
        }

        return response()->json([
            'arquivo' => $upload->arquivo_nome,
            'tamanho' => $upload->tamanhoFormatado(),
            'cliente' => $upload->cliente?->nome ?? '—',
            'tarefa' => $upload->tarefa?->titulo ?? '—',
            'eventos' => array_merge($eventos, $pendentes),
            'totalVisualizacoes' => $totalVisualizacoes,
            'totalDownloads' => $totalDownloads,
        ]);
    }

    public function uploadsPortal(Request $request): View
    {
        $usuario = Auth::user();
        $podeVerTodas = in_array($usuario->cargo, ['diretor', 'ti', 'supervisor']);

        $query = TarefaUpload::with(['tarefa', 'cliente', 'enviadoPor'])
            ->orderByDesc('created_at');

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->integer('cliente_id'));
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'baixado') {
                $query->whereNotNull('baixado_em');
            } elseif ($request->input('status') === 'visualizado') {
                $query->whereNull('baixado_em')->whereNotNull('visualizado_em');
            } elseif ($request->input('status') === 'nao_baixado') {
                $query->whereNull('baixado_em')->whereNull('visualizado_em');
            }
        }

        $uploads = $query->paginate(30)->withQueryString();
        $clientes = Cliente::orderBy('nome')->get();

        return view('tarefas.uploads-portal', compact('uploads', 'clientes', 'podeVerTodas'));
    }
}
