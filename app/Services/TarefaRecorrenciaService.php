<?php

namespace App\Services;

use App\Models\Ciclo;
use App\Models\Etapa;
use App\Models\Tarefa;
use Illuminate\Support\Carbon;

class TarefaRecorrenciaService
{
    public function etapaAFazer(): ?Etapa
    {
        return Etapa::orderBy('ordem')
            ->get()
            ->first(fn ($e) => strtolower(trim($e->nome)) === 'a fazer')
            ?? Etapa::orderBy('ordem')->first();
    }

    /**
     * Aplica um dia do mês padrão sobre o mês/ano de uma data de referência,
     * evitando dias inválidos (ex: dia 31 em fevereiro).
     */
    public function aplicarDiaNoMes(int $dia, string $referencia): string
    {
        $base = Carbon::parse($referencia);
        $diaClamped = min($dia, $base->daysInMonth);

        return $base->copy()->day($diaClamped)->toDateString();
    }

    /**
     * Gera as ocorrências futuras de uma tarefa recorrente até a data limite informada.
     */
    public function gerarOcorrenciasParaUmAno(Tarefa $tarefa, Carbon $dataFim): void
    {
        $etapaInicial = $this->etapaAFazer();

        $originalId = $tarefa->tarefa_original_id ?? $tarefa->id;
        $dataAtual = Carbon::parse($tarefa->data_vencimento);

        while (true) {
            $proximaData = match ($tarefa->frequencia) {
                'diaria' => $dataAtual->copy()->addDay(),
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

            $novaOcorrencia = Tarefa::create([
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

            $novaOcorrencia->clientes()->sync($tarefa->clientes->pluck('id'));

            $dataAtual = $proximaData;
        }
    }
}
