<?php

namespace App\Http\Controllers;

use App\Models\Ciclo;
use App\Models\Cliente;
use App\Models\Departamento;
use App\Models\Etapa;
use App\Models\Notificacao;
use App\Models\RelTarefa;
use App\Models\Tarefa;
use App\Models\TarefaUpload;
use App\Models\TipoTarefa;
use App\Models\Usuario;
use App\Services\TarefaRecorrenciaService;
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
    public function __construct(private readonly TarefaRecorrenciaService $tarefaRecorrenciaService)
    {
    }

    public function showTarefas(Request $request): View
    {
        $usuario = Auth::user();
        $podeVerTodas = $usuario->canVerTodasTarefas();

        $mostrarInativas = $request->boolean('mostrar_inativas');

        $query = Tarefa::with(['cliente', 'clientes', 'departamento', 'etapa', 'responsavel'])
            ->orderBy('data_vencimento');

        if (! $mostrarInativas) {
            $query->where('ativo', true);
        }

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

        $tarefas = $query->paginate(20)->withQueryString();

        $clientes = Cliente::orderBy('nome')->get();
        $etapas = Etapa::where('visivel', true)->orderBy('ordem')->get();
        $usuarios = $podeVerTodas ? Usuario::orderBy('nome')->get() : collect();

        $tiposTarefa = TipoTarefa::orderBy('nome')->get();

        return view('tarefas.home', compact('tarefas', 'clientes', 'etapas', 'usuarios', 'podeVerTodas', 'mostrarInativas', 'tiposTarefa'));
    }

    public function showTarefasList(Request $request): View
    {
        $usuario = Auth::user();
        $podeVerTodas = in_array($usuario->cargo, ['diretor', 'ti', 'supervisor_geral']);
        $isSupervisor = $usuario->cargo === 'supervisor';

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
            ->where('ativo', true)
            ->orderBy('passou_ciclo', 'desc')
            ->orderBy('data_vencimento');

        if ($isSupervisor) {
            $query->whereHas('responsavel', fn ($q) => $q->whereNotIn('cargo', ['diretor', 'ti']));
        } elseif (! $podeVerTodas) {
            $query->where('responsavel_id', $usuario->id);
        }

        // Para supervisor e diretor/ti: pré-seleciona ele mesmo na primeira visita (sem parâmetro na URL).
        // Se o parâmetro existe mas vazio (""), o usuário escolheu "Todos" explicitamente.
        $responsavelFiltroId = null;
        if ($isSupervisor || $podeVerTodas) {
            if ($request->has('responsavel_id')) {
                $responsavelFiltroId = $request->filled('responsavel_id')
                    ? $request->integer('responsavel_id')
                    : null;
            } else {
                $responsavelFiltroId = $usuario->id;
            }
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

        if ($podeVerTodas && $request->filled('departamento_id')) {
            $query->where('departamento_id', $request->integer('departamento_id'));
        }

        if (($podeVerTodas || $isSupervisor) && $responsavelFiltroId) {
            $query->where('responsavel_id', $responsavelFiltroId);
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
        $usuarios = $podeVerTodas
            ? Usuario::orderBy('nome')->get()
            : ($isSupervisor
                ? Usuario::whereNotIn('cargo', ['diretor', 'ti'])->orderBy('nome')->get()
                : collect());
        $clientes = Cliente::orderBy('nome')->get();
        $tiposTarefa = TipoTarefa::orderBy('nome')->get();

        $usuariosTransferencia = $podeVerTodas
            ? Usuario::orderBy('nome')->get()
            : Usuario::where('departamento_id', $usuario->departamento_id)->orderBy('nome')->get();

        return view('tarefas.list', compact(
            'tarefas',
            'etapas',
            'departamentos',
            'usuarios',
            'clientes',
            'tiposTarefa',
            'podeVerTodas',
            'isSupervisor',
            'responsavelFiltroId',
            'cicloSelecionado',
            'cicloPrev',
            'cicloNext',
            'filtroDataAtivo',
            'usuariosTransferencia',
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

        $authUsuario = Auth::user();
        $podeMudarResponsavel = (int) $authUsuario->id === (int) $tarefa->supervisor_id;

        $podeTransferirEntreSetores = ! $podeMudarResponsavel && $authUsuario->canTransferirEntreSetores();

        $podeTransferirNoDepartamento = ! $podeMudarResponsavel
            && ! $podeTransferirEntreSetores
            && $authUsuario->departamento_id !== null
            && (int) $authUsuario->departamento_id === (int) $tarefa->departamento_id;

        $responsaveisDepartamento = $usuarios->where('departamento_id', $authUsuario->departamento_id)->values();

        $selectedClienteIds = $tarefa->clientes->pluck('id')->toArray();

        $tiposTarefa = TipoTarefa::orderBy('nome')->get();

        return view('tarefas.partials.formTarefa', compact('tarefa', 'clientes', 'etapas', 'usuarios', 'usuariosDepartamentos', 'podeMudarResponsavel', 'podeTransferirNoDepartamento', 'podeTransferirEntreSetores', 'responsaveisDepartamento', 'selectedClienteIds', 'tiposTarefa'));
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->only([
            'titulo', 'titulo_manual', 'descricao', 'cliente_ids', 'tipo_tarefa_ids',
            'etapa_id', 'responsavel_id', 'supervisor_id', 'data_vencimento', 'prioridade', 'frequencia',
            'requer_envio_arquivo', 'primeira_execucao',
        ]);

        // Se o usuário editou manualmente o título (mesmo com um tipo selecionado),
        // esse título vale só para esta(s) tarefa(s) e não sobrescreve o título
        // padrão cadastrado no tipo.
        $tituloManual = ! empty($data['titulo']) && filter_var($data['titulo_manual'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $temTipos = ! empty($data['tipo_tarefa_ids']);

        $tiposComData = $temTipos
            ? TipoTarefa::whereIn('id', $data['tipo_tarefa_ids'])->whereNotNull('data_vencimento')->count() === count($data['tipo_tarefa_ids'])
            : false;

        $validator = Validator::make($data, [
            'titulo' => [$temTipos ? 'nullable' : 'required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'cliente_ids' => ['nullable', 'array'],
            'cliente_ids.*' => ['exists:clientes,id'],
            'tipo_tarefa_ids' => ['nullable', 'array'],
            'tipo_tarefa_ids.*' => ['exists:tipos_tarefa,id'],
            'etapa_id' => ['required', 'exists:etapas,id'],
            'responsavel_id' => ['required', 'exists:usuarios,id'],
            'supervisor_id' => ['required', 'exists:usuarios,id'],
            'data_vencimento' => [$tiposComData ? 'nullable' : 'required', 'date'],
            'prioridade' => ['required', 'integer', 'min:1', 'max:5'],
            'frequencia' => ['nullable', 'in:nenhuma,semanal,mensal,trimestral,semestral,anual'],
            'primeira_execucao' => ['nullable', 'in:este_mes,proximo_mes'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $frequencia = $data['frequencia'] ?? 'nenhuma';
        $primeiraExecucao = $data['primeira_execucao'] ?? 'este_mes';

        $departamentoId = Usuario::find($data['responsavel_id'] ?? null)?->departamento_id
            ?? Departamento::orderBy('id')->value('id');

        $clienteIds = ! empty($data['cliente_ids']) ? $data['cliente_ids'] : [null];
        $tipoIds    = ! empty($data['tipo_tarefa_ids']) ? $data['tipo_tarefa_ids'] : [null];

        // Carrega os tipos selecionados para usar suas datas individuais
        $tiposMap = ! empty($data['tipo_tarefa_ids'])
            ? TipoTarefa::whereIn('id', $data['tipo_tarefa_ids'])->get()->keyBy('id')
            : collect();

        // Data de referência: usa a do form; se não houver, usa hoje.
        $dataReferencia = $data['data_vencimento'] ?? now()->toDateString();

        // O tipo guarda apenas o "dia de vencimento padrão"; aplicamos esse dia sobre o
        // mês/ano de referência em vez de usar a data absoluta cadastrada no tipo, que
        // fica desatualizada com o passar dos meses.
        $dataComDiaDoTipo = function (?TipoTarefa $tipo, string $referencia): string {
            if (! $tipo || ! $tipo->data_vencimento) {
                return $referencia;
            }

            return $this->tarefaRecorrenciaService->aplicarDiaNoMes($tipo->data_vencimento->day, $referencia);
        };

        $duplicatas = [];
        $novasTarefasIds = [];
        $primeiroCicloId = null;
        foreach ($clienteIds as $clienteId) {
            foreach ($tipoIds as $tipoId) {
                $dataParaTipo = $dataComDiaDoTipo($tipoId ? $tiposMap->get($tipoId) : null, $dataReferencia);
                if ($frequencia !== 'nenhuma' && $primeiraExecucao === 'proximo_mes') {
                    $dataParaTipo = Carbon::parse($dataParaTipo)->addMonthNoOverflow()->toDateString();
                }
                $tipoCheck = $tipoId ? $tiposMap->get($tipoId) : null;
                $tituloCheck = $tituloManual
                    ? $data['titulo']
                    : ($tipoCheck->titulo_padrao ?? $tipoCheck?->nome ?? $data['titulo'] ?? '');
                $existe = Tarefa::where('titulo', $tituloCheck)
                    ->where('responsavel_id', $data['responsavel_id'])
                    ->where('data_vencimento', $dataParaTipo)
                    ->where('cliente_id', $clienteId)
                    ->where('ativo', true)
                    ->exists();
                if ($existe) {
                    $duplicatas[] = ['cliente_id' => $clienteId, 'tipo_id' => $tipoId];
                }
            }
        }

        if (! empty($duplicatas)) {
            return Redirect::back()
                ->withInput()
                ->withErrors(['titulo' => 'Já existe uma tarefa com esse título, responsável e data de vencimento para o(s) cliente(s) e tipo(s) selecionado(s). Verifique se a tarefa já foi criada.']);
        }

        foreach ($clienteIds as $clienteId) {
            foreach ($tipoIds as $tipoId) {
                $dataParaTipo = $dataComDiaDoTipo($tipoId ? $tiposMap->get($tipoId) : null, $dataReferencia);
                if ($frequencia !== 'nenhuma' && $primeiraExecucao === 'proximo_mes') {
                    $dataParaTipo = Carbon::parse($dataParaTipo)->addMonthNoOverflow()->toDateString();
                }
                $cicloParaTipo = Ciclo::findOrCreateForDate(Carbon::parse($dataParaTipo))->id;
                $primeiroCicloId ??= $cicloParaTipo;
                $dataFimParaTipo = $frequencia !== 'nenhuma'
                    ? Carbon::parse($dataParaTipo)->addYear()->toDateString()
                    : null;

                $tipo = $tipoId ? $tiposMap->get($tipoId) : null;
                $tituloFinal = $tituloManual
                    ? $data['titulo']
                    : ($tipo->titulo_padrao ?? $tipo?->nome ?? $data['titulo'] ?? '');
                $tarefa = Tarefa::create([
                    'titulo' => $tituloFinal,
                    'descricao' => $data['descricao'] ?? null,
                    'tipo_tarefa_id' => $tipoId,
                    'cliente_id' => $clienteId,
                    'departamento_id' => $departamentoId,
                    'etapa_id' => $data['etapa_id'],
                    'responsavel_id' => $data['responsavel_id'] ?? null,
                    'supervisor_id' => $data['supervisor_id'] ?? null,
                    'criado_por' => Auth::id(),
                    'data_vencimento' => $dataParaTipo,
                    'prioridade' => $data['prioridade'],
                    'ciclo_id' => $cicloParaTipo,
                    'frequencia' => $frequencia,
                    'recorrente' => $frequencia !== 'nenhuma',
                    'data_fim_recorrencia' => $dataFimParaTipo,
                    'requer_envio_arquivo' => ! empty($data['requer_envio_arquivo']),
                ]);

                $novasTarefasIds[] = $tarefa->id;

                RelTarefa::create([
                    'tarefa_id' => $tarefa->id,
                    'etapa_anterior_id' => null,
                    'etapa_nova_id' => $data['etapa_id'],
                    'responsavel_anterior_id' => null,
                    'responsavel_novo_id' => $data['responsavel_id'] ?? null,
                    'alterado_por' => Auth::id(),
                ]);

                if ($clienteId !== null) {
                    $tarefa->clientes()->sync([$clienteId]);
                }

                if ($frequencia !== 'nenhuma') {
                    $this->tarefaRecorrenciaService->gerarOcorrenciasParaUmAno($tarefa, Carbon::parse($dataFimParaTipo));
                }

                // Notifica o colaborador quando recebe uma tarefa não-recorrente de outro usuário
                $responsavelId = $data['responsavel_id'] ?? null;
                if ($frequencia === 'nenhuma' && $responsavelId && (int) $responsavelId !== (int) Auth::id()) {
                    try {
                        $criador = Auth::user();
                        Notificacao::create([
                            'usuario_id' => $responsavelId,
                            'tipo'       => 'tarefa_atribuida',
                            'mensagem'   => "{$criador->nome} atribuiu a tarefa \"{$data['titulo']}\" a você.",
                            'tarefa_id'  => $tarefa->id,
                        ]);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('Falha ao criar notificação: ' . $e->getMessage());
                    }
                }
            }
        }

        $count = count($clienteIds) * count($tipoIds);
        $mensagem = $count > 1
            ? "{$count} tarefas criadas com sucesso."
            : 'Tarefa criada com sucesso.';

        $redirectUrl = $primeiroCicloId
            ? route('tarefas.list', [
                'ciclo_id' => $primeiroCicloId,
                'responsavel_id' => $data['responsavel_id'],
            ])
            : ($request->headers->get('referer') ?? route('tarefas.list'));

        return redirect($redirectUrl)
            ->with('success', $mensagem)
            ->with('novas_tarefas_ids', $novasTarefasIds);
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

        $podeMudarResponsavel = (int) $usuario->id === (int) $tarefa->supervisor_id;

        $podeTransferirEntreSetores = ! $podeMudarResponsavel && $usuario->canTransferirEntreSetores();

        $podeTransferirNoDepartamento = ! $podeMudarResponsavel
            && ! $podeTransferirEntreSetores
            && $usuario->departamento_id !== null
            && (int) $usuario->departamento_id === (int) $tarefa->departamento_id;

        if ($podeMudarResponsavel || $podeTransferirEntreSetores) {
            $novoResponsavelId = $data['responsavel_id'] ?? null;
        } elseif ($podeTransferirNoDepartamento) {
            $candidato = Usuario::find($data['responsavel_id'] ?? null);
            $novoResponsavelId = ($candidato && (int) $candidato->departamento_id === (int) $usuario->departamento_id)
                ? $candidato->id
                : $tarefa->responsavel_id;
        } else {
            $novoResponsavelId = $tarefa->responsavel_id;
        }

        $departamentoId = Usuario::find($novoResponsavelId)?->departamento_id
            ?? $tarefa->departamento_id
            ?? Departamento::orderBy('id')->value('id');

        $clienteIds = ! empty($data['cliente_ids']) ? $data['cliente_ids'] : [];
        $clienteIdAnterior = $tarefa->cliente_id;

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

        $clienteMudou = (int) ($clienteIdAnterior ?? 0) !== (int) ($clienteIds[0] ?? 0);

        if ($clienteMudou && $tarefa->recorrente) {
            $originalId = $tarefa->tarefa_original_id ?? $tarefa->id;

            $futuras = Tarefa::where(function ($q) use ($originalId) {
                    $q->where('id', $originalId)->orWhere('tarefa_original_id', $originalId);
                })
                ->where('id', '!=', $tarefa->id)
                ->where('data_vencimento', '>', $tarefa->data_vencimento)
                ->where('ativo', true)
                ->get();

            foreach ($futuras as $futura) {
                $futura->update(['cliente_id' => $clienteIds[0] ?? null]);
                $futura->clientes()->sync($clienteIds);
            }
        }

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

        // Notifica o novo responsável quando a tarefa é transferida para ele por outro usuário
        if ($responsavelMudou && $novoResponsavelId && (int) $novoResponsavelId !== (int) Auth::id()) {
            try {
                $criador = Auth::user();
                Notificacao::create([
                    'usuario_id' => $novoResponsavelId,
                    'tipo'       => 'tarefa_atribuida',
                    'mensagem'   => "{$criador->nome} transferiu a tarefa \"{$data['titulo']}\" para você.",
                    'tarefa_id'  => $tarefa->id,
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Falha ao criar notificação: ' . $e->getMessage());
            }
        }

        return Redirect::back()->with('success', 'Tarefa atualizada com sucesso.');
    }

    public function bulkTransferirResponsavel(Request $request): JsonResponse
    {
        $usuario = Auth::user();

        $data = $request->validate([
            'tarefa_ids' => ['required', 'array', 'min:1'],
            'tarefa_ids.*' => ['integer', 'exists:tarefas,id'],
            'responsavel_id' => ['required', 'exists:usuarios,id'],
            'aplicar_futuras' => ['nullable', 'boolean'],
        ]);

        $aplicarFuturas = (bool) ($data['aplicar_futuras'] ?? false);
        $tarefas = Tarefa::whereIn('id', $data['tarefa_ids'])->where('ativo', true)->get();

        $transferidas = 0;
        $puladas = 0;

        foreach ($tarefas as $tarefa) {
            if (! $usuario->canEditarQualquerTarefa() && (int) $tarefa->responsavel_id !== (int) $usuario->id) {
                $puladas++;

                continue;
            }

            $podeMudarResponsavel = (int) $usuario->id === (int) $tarefa->supervisor_id;

            $podeTransferirNoDepartamento = ! $podeMudarResponsavel
                && $usuario->departamento_id !== null
                && (int) $usuario->departamento_id === (int) $tarefa->departamento_id;

            if ($podeMudarResponsavel) {
                $novoResponsavelId = $data['responsavel_id'];
            } elseif ($podeTransferirNoDepartamento) {
                $candidato = Usuario::find($data['responsavel_id']);
                $novoResponsavelId = ($candidato && (int) $candidato->departamento_id === (int) $usuario->departamento_id)
                    ? $candidato->id
                    : $tarefa->responsavel_id;
            } else {
                $novoResponsavelId = $tarefa->responsavel_id;
            }

            $responsavelAnteriorId = $tarefa->responsavel_id;
            $responsavelMudou = (int) ($responsavelAnteriorId ?? 0) !== (int) $novoResponsavelId;

            if (! $responsavelMudou) {
                $puladas++;

                continue;
            }

            $departamentoId = Usuario::find($novoResponsavelId)?->departamento_id
                ?? $tarefa->departamento_id
                ?? Departamento::orderBy('id')->value('id');

            $tarefa->update([
                'responsavel_id' => $novoResponsavelId,
                'departamento_id' => $departamentoId,
            ]);

            RelTarefa::create([
                'tarefa_id' => $tarefa->id,
                'etapa_anterior_id' => null,
                'etapa_nova_id' => null,
                'responsavel_anterior_id' => $responsavelAnteriorId,
                'responsavel_novo_id' => $novoResponsavelId,
                'alterado_por' => Auth::id(),
            ]);

            if ($aplicarFuturas && $tarefa->recorrente) {
                $originalId = $tarefa->tarefa_original_id ?? $tarefa->id;

                $futuras = Tarefa::where(function ($q) use ($originalId) {
                        $q->where('id', $originalId)->orWhere('tarefa_original_id', $originalId);
                    })
                    ->where('id', '!=', $tarefa->id)
                    ->where('data_vencimento', '>', $tarefa->data_vencimento)
                    ->where('ativo', true)
                    ->get();

                foreach ($futuras as $futura) {
                    $futuraPodeMudar = (int) $usuario->id === (int) $futura->supervisor_id;

                    $futuraPodeTransferirNoDepartamento = ! $futuraPodeMudar
                        && $usuario->departamento_id !== null
                        && (int) $usuario->departamento_id === (int) $futura->departamento_id;

                    if ($futuraPodeMudar) {
                        $futuroResponsavelId = $data['responsavel_id'];
                    } elseif ($futuraPodeTransferirNoDepartamento) {
                        $candidatoFutura = Usuario::find($data['responsavel_id']);
                        $futuroResponsavelId = ($candidatoFutura && (int) $candidatoFutura->departamento_id === (int) $usuario->departamento_id)
                            ? $candidatoFutura->id
                            : $futura->responsavel_id;
                    } else {
                        $futuroResponsavelId = $futura->responsavel_id;
                    }

                    if ((int) $futuroResponsavelId === (int) $futura->responsavel_id) {
                        continue;
                    }

                    $futura->update([
                        'responsavel_id' => $futuroResponsavelId,
                        'departamento_id' => Usuario::find($futuroResponsavelId)?->departamento_id
                            ?? $futura->departamento_id
                            ?? Departamento::orderBy('id')->value('id'),
                    ]);
                }
            }

            if ($novoResponsavelId && (int) $novoResponsavelId !== (int) Auth::id()) {
                try {
                    Notificacao::create([
                        'usuario_id' => $novoResponsavelId,
                        'tipo'       => 'tarefa_atribuida',
                        'mensagem'   => "{$usuario->nome} transferiu a tarefa \"{$tarefa->titulo}\" para você.",
                        'tarefa_id'  => $tarefa->id,
                    ]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Falha ao criar notificação: ' . $e->getMessage());
                }
            }

            $transferidas++;
        }

        return response()->json([
            'transferidas' => $transferidas,
            'puladas' => $puladas,
            'total' => $tarefas->count(),
        ]);
    }

    public function delete(int $id): RedirectResponse
    {
        $tarefa = Tarefa::findOrFail($id);

        $usuario = Auth::user();
        if (! $usuario->canExcluirTarefa()) {
            abort(403);
        }

        $tarefa->delete();

        return Redirect::back()->with('success', 'Tarefa excluída com sucesso.');
    }

    public function deleteAllInativas(): RedirectResponse
    {
        $usuario = Auth::user();
        if (! $usuario->canExcluirTarefa()) {
            abort(403);
        }

        $count = Tarefa::where('ativo', false)->count();
        Tarefa::where('ativo', false)->delete();

        return Redirect::back()->with('success', "{$count} tarefa(s) inativa(s) excluída(s) com sucesso.");
    }

    public function checkDuplicata(Request $request): JsonResponse
    {
        $clienteIds  = $request->input('cliente_ids', []);
        $tipoIds     = $request->input('tipo_tarefa_ids', []);
        $responsavelId = $request->input('responsavel_id');
        $titulo      = trim($request->input('titulo', ''));

        if (! $responsavelId) {
            return response()->json(['duplicatas' => []]);
        }

        $tiposMap = ! empty($tipoIds)
            ? TipoTarefa::whereIn('id', $tipoIds)->get()->keyBy('id')
            : collect();

        $clienteIdsToCheck = ! empty($clienteIds) ? $clienteIds : [null];
        $tipoIdsToCheck    = ! empty($tipoIds)    ? $tipoIds    : [null];

        $duplicatas = [];

        foreach ($clienteIdsToCheck as $clienteId) {
            foreach ($tipoIdsToCheck as $tipoId) {
                $tipo        = $tipoId ? $tiposMap->get($tipoId) : null;
                $tituloCheck = $tipo && $tipo->titulo_padrao
                    ? $tipo->titulo_padrao
                    : ($titulo ?: ($tipo?->nome ?? ''));

                if (! $tituloCheck) {
                    continue;
                }

                $existentes = Tarefa::with(['cliente', 'etapa'])
                    ->where('titulo', $tituloCheck)
                    ->where('responsavel_id', $responsavelId)
                    ->where('cliente_id', $clienteId)
                    ->where('ativo', true)
                    ->get();

                foreach ($existentes as $tarefa) {
                    $duplicatas[] = [
                        'titulo'          => $tarefa->titulo,
                        'cliente'         => $tarefa->cliente?->nome ?? '—',
                        'etapa'           => $tarefa->etapa?->nome ?? '—',
                        'data_vencimento' => $tarefa->data_vencimento?->format('d/m/Y') ?? '—',
                    ];
                }
            }
        }

        return response()->json(['duplicatas' => $duplicatas]);
    }

    public function contarDuplicatas(): \Illuminate\Http\JsonResponse
    {
        $usuario = Auth::user();
        if (! $usuario->canExcluirTarefa()) {
            abort(403);
        }

        $total = Tarefa::selectRaw('COUNT(*) - COUNT(DISTINCT CONCAT(titulo, "|", COALESCE(responsavel_id,""), "|", data_vencimento, "|", COALESCE(cliente_id,""))) as duplicadas')
            ->value('duplicadas');

        return response()->json(['total' => (int) $total]);
    }

    public function deletarDuplicatas(): RedirectResponse
    {
        $usuario = Auth::user();
        if (! $usuario->canExcluirTarefa()) {
            abort(403);
        }

        // Agrupa por título + responsável + data_vencimento + cliente_id, mantém o mais antigo
        $grupos = Tarefa::selectRaw('titulo, responsavel_id, data_vencimento, cliente_id, MIN(id) as id_manter, COUNT(*) as total')
            ->groupBy('titulo', 'responsavel_id', 'data_vencimento', 'cliente_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $totalExcluidas = 0;
        foreach ($grupos as $grupo) {
            $excluidas = Tarefa::where('titulo', $grupo->titulo)
                ->where('responsavel_id', $grupo->responsavel_id)
                ->where('data_vencimento', $grupo->data_vencimento)
                ->where('cliente_id', $grupo->cliente_id)
                ->where('id', '!=', $grupo->id_manter)
                ->delete();
            $totalExcluidas += $excluidas;
        }

        return Redirect::back()->with('success', "{$totalExcluidas} tarefa(s) duplicada(s) excluída(s) com sucesso.");
    }

    public function inativar(Request $request, int $id): RedirectResponse
    {
        $tarefa = Tarefa::findOrFail($id);
        $usuario = Auth::user();

        if (! $usuario->canInativarTarefa($tarefa)) {
            abort(403);
        }

        $scope = $request->input('scope', 'unica');

        $tarefasParaInativar = collect([$tarefa]);

        if ($scope === 'futuras' && $tarefa->recorrente) {
            $originalId = $tarefa->tarefa_original_id ?? $tarefa->id;
            $futuras = Tarefa::where(function ($q) use ($originalId) {
                $q->where('id', $originalId)->orWhere('tarefa_original_id', $originalId);
            })
                ->where('data_vencimento', '>=', $tarefa->data_vencimento)
                ->where('ativo', true)
                ->get();

            $tarefasParaInativar = $futuras;
        }

        $now = now();
        foreach ($tarefasParaInativar as $t) {
            $t->update([
                'ativo' => false,
                'inativado_por' => $usuario->id,
                'inativado_em' => $now,
            ]);
        }

        $count = $tarefasParaInativar->count();
        $mensagem = $count > 1
            ? "{$count} tarefas inativadas com sucesso."
            : 'Tarefa inativada com sucesso.';

        return Redirect::back()->with('success', $mensagem);
    }

    public function ativar(int $id): RedirectResponse
    {
        $tarefa = Tarefa::findOrFail($id);
        $usuario = Auth::user();

        if (! $usuario->canExcluirTarefa()) {
            abort(403);
        }

        $tarefa->update([
            'ativo' => true,
            'inativado_por' => null,
            'inativado_em' => null,
        ]);

        return Redirect::back()->with('success', 'Tarefa reativada com sucesso.');
    }

    public function updateEtapa(Request $request, int $id): JsonResponse
    {
        $tarefa = Tarefa::with('tipoTarefa')->findOrFail($id);

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

        $isTipoCertificadoDigital = $tarefa->tipoTarefa
            && str_contains(strtolower($tarefa->tipoTarefa->nome), 'certificado digital');

        $isRenovacaoCertificado = $isFinalizado
            && $tarefa->cliente_id
            && (str_starts_with($tarefa->titulo, 'Renovação de Certificado') || $isTipoCertificadoDigital);

        return response()->json([
            'success' => true,
            'finalizado' => $isFinalizado,
            'requer_envio_arquivo' => $isFinalizado && $tarefa->requer_envio_arquivo,
            'renovacao_certificado' => $isRenovacaoCertificado,
            'cliente_id' => $tarefa->cliente_id,
            'ultima_recorrencia' => $ultimaRecorrencia,
            'tarefa_id' => $tarefa->id,
        ]);
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

        $this->tarefaRecorrenciaService->gerarOcorrenciasParaUmAno($ultimaTarefa, $novaDataFim);

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

        $etapasPendentesIds = Etapa::whereIn('nome', ['A Fazer', 'Andamento'])->pluck('id');
        $etapaTransferido = Etapa::where('nome', 'Transferido para o próximo ciclo')->first();

        if ($etapasPendentesIds->isEmpty()) {
            return;
        }

        $tarefas = Tarefa::whereHas('ciclo', fn ($q) => $q->where('data_fim', '<', $hoje))
            ->whereIn('etapa_id', $etapasPendentesIds)
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
            'cliente_cpfcnpj' => $tarefa->cliente?->cpfcnpj,
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
                'eh_criacao' => is_null($r->etapa_anterior_id) && is_null($r->responsavel_anterior_id),
                'alterado_por' => $r->alteradoPor?->nome,
                'observacao' => $r->observacao,
                'data' => $r->created_at->format('d/m/Y H:i'),
            ])->values(),
        ]);
    }

    public function duplicar(Request $request, int $id): JsonResponse
    {
        $tarefa = Tarefa::findOrFail($id);

        $validator = Validator::make($request->only('cliente_ids', 'responsavel_id', 'tipo_tarefa_id'), [
            'cliente_ids' => ['required', 'array', 'min:1'],
            'cliente_ids.*' => ['exists:clientes,id'],
            'responsavel_id' => ['nullable', 'exists:usuarios,id'],
            'tipo_tarefa_id' => ['nullable', 'exists:tipos_tarefa,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $clienteIds = $request->input('cliente_ids');
        $responsavelId = $request->input('responsavel_id') ?? $tarefa->responsavel_id;
        $tipoTarefaId = $request->input('tipo_tarefa_id') ?? $tarefa->tipo_tarefa_id;
        $count = 0;

        foreach ($clienteIds as $clienteId) {
            $nova = Tarefa::create([
                'titulo' => $tarefa->titulo,
                'descricao' => $tarefa->descricao,
                'tipo_tarefa_id' => $tipoTarefaId,
                'cliente_id' => $clienteId,
                'departamento_id' => $tarefa->departamento_id,
                'etapa_id' => $tarefa->etapa_id,
                'responsavel_id' => $responsavelId,
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
