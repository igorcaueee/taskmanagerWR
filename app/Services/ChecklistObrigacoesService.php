<?php

namespace App\Services;

use App\Models\Ciclo;
use App\Models\Cliente;
use App\Models\Departamento;
use App\Models\RelTarefa;
use App\Models\Tarefa;
use App\Models\TipoTarefa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ChecklistObrigacoesService
{
    public function __construct(private readonly TarefaRecorrenciaService $tarefaRecorrenciaService)
    {
    }

    /**
     * Monta as sugestões de obrigações para um cliente a partir das regras
     * cadastradas nos tipos de tarefa que casam com o regime/atividade dele.
     *
     * Cada item traz os valores padrão da regra (editáveis na tela de revisão)
     * e a flag `ja_existe` indicando que o cliente já tem uma tarefa ativa desse tipo.
     *
     * @return Collection<int, array{tipo_tarefa_id:int, nome:string, titulo:string, regime:?string, frequencia:string, dia_vencimento:?int, departamento_id:?int, responsavel_id:?int, ja_existe:bool}>
     */
    public function sugerirParaCliente(Cliente $cliente): Collection
    {
        $tipos = TipoTarefa::with(['regras' => fn ($q) => $q->where('ativo', true)])->get();

        return $tipos
            ->map(function (TipoTarefa $tipo) use ($cliente) {
                $regra = $tipo->regras->first(fn ($r) => $r->aplicaAoCliente($cliente));

                if (! $regra) {
                    return null;
                }

                return [
                    'tipo_tarefa_id' => $tipo->id,
                    'nome' => $tipo->nome,
                    'titulo' => $tipo->titulo_padrao ?: $tipo->nome,
                    'regime' => $regra->regime_tributario,
                    'frequencia' => $regra->frequencia ?? 'mensal',
                    'dia_vencimento' => $regra->dia_vencimento,
                    'departamento_id' => $regra->departamento_id,
                    'responsavel_id' => $regra->responsavel_id,
                    'ja_existe' => $this->clienteJaTemTipoAtivo($cliente, $tipo->id),
                ];
            })
            ->filter()
            ->sortBy('nome')
            ->values();
    }

    /**
     * Gera as tarefas escolhidas na tela de revisão do checklist.
     *
     * @param  array<int, array{frequencia?:string, dia_vencimento?:int|string|null, departamento_id?:int|string|null, responsavel_id?:int|string|null}>  $selecoes  keyed by tipo_tarefa_id
     * @return int quantidade de tarefas criadas
     */
    public function gerarSelecionadas(Cliente $cliente, array $selecoes): int
    {
        $criadas = 0;

        foreach ($selecoes as $tipoTarefaId => $config) {
            $tipo = TipoTarefa::find($tipoTarefaId);

            if (! $tipo || $this->clienteJaTemTipoAtivo($cliente, $tipo->id)) {
                continue;
            }

            $this->criarTarefa($cliente, $tipo, $config);
            $criadas++;
        }

        return $criadas;
    }

    private function clienteJaTemTipoAtivo(Cliente $cliente, int $tipoTarefaId): bool
    {
        return Tarefa::where('tipo_tarefa_id', $tipoTarefaId)
            ->where('ativo', true)
            ->where(function ($q) use ($cliente) {
                $q->where('cliente_id', $cliente->id)
                    ->orWhereHas('clientes', fn ($q2) => $q2->where('clientes.id', $cliente->id));
            })
            ->exists();
    }

    /**
     * @param  array{frequencia?:string, dia_vencimento?:int|string|null, departamento_id?:int|string|null, responsavel_id?:int|string|null}  $config
     */
    private function criarTarefa(Cliente $cliente, TipoTarefa $tipo, array $config): void
    {
        $etapaInicial = $this->tarefaRecorrenciaService->etapaAFazer();

        if (! $etapaInicial) {
            return;
        }

        $frequencia = $config['frequencia'] ?? 'mensal';
        $diaVencimento = ! empty($config['dia_vencimento']) ? (int) $config['dia_vencimento'] : null;
        $responsavelId = ! empty($config['responsavel_id']) ? (int) $config['responsavel_id'] : null;

        $referencia = now()->toDateString();
        $dataVencimento = $diaVencimento
            ? $this->tarefaRecorrenciaService->aplicarDiaNoMes($diaVencimento, $referencia)
            : $referencia;

        $dataFimRecorrencia = $frequencia !== 'nenhuma'
            ? Carbon::parse($dataVencimento)->addYear()->toDateString()
            : null;

        $departamentoId = (! empty($config['departamento_id']) ? (int) $config['departamento_id'] : null)
            ?? Departamento::orderBy('id')->value('id');

        $tarefa = Tarefa::create([
            'titulo' => $tipo->titulo_padrao ?: $tipo->nome,
            'tipo_tarefa_id' => $tipo->id,
            'cliente_id' => $cliente->id,
            'departamento_id' => $departamentoId,
            'etapa_id' => $etapaInicial->id,
            'responsavel_id' => $responsavelId,
            'supervisor_id' => $responsavelId,
            'criado_por' => Auth::id(),
            'data_vencimento' => $dataVencimento,
            'prioridade' => 1,
            'ciclo_id' => Ciclo::findOrCreateForDate(Carbon::parse($dataVencimento))->id,
            'frequencia' => $frequencia,
            'recorrente' => $frequencia !== 'nenhuma',
            'data_fim_recorrencia' => $dataFimRecorrencia,
            'requer_envio_arquivo' => false,
        ]);

        $tarefa->clientes()->sync([$cliente->id]);

        RelTarefa::create([
            'tarefa_id' => $tarefa->id,
            'etapa_anterior_id' => null,
            'etapa_nova_id' => $etapaInicial->id,
            'responsavel_anterior_id' => null,
            'responsavel_novo_id' => $responsavelId,
            'alterado_por' => Auth::id(),
        ]);

        if ($frequencia !== 'nenhuma' && $dataFimRecorrencia) {
            $this->tarefaRecorrenciaService->gerarOcorrenciasParaUmAno($tarefa, Carbon::parse($dataFimRecorrencia));
        }
    }
}
