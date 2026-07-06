<?php

namespace App\Services;

use App\Models\Etapa;
use App\Models\Tarefa;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TempoTrabalhoService
{
    /**
     * Soma, por colaborador, as horas trabalhadas nas tarefas concluídas dentro do período:
     * para cada tarefa, o tempo entre ela entrar numa etapa que conta como trabalho
     * (ex.: "Andamento") e ser concluída.
     *
     * @return Collection<int, array{nome: string, horas: float}>
     */
    public function horasPorColaborador(Carbon $inicio, Carbon $fim): Collection
    {
        $segundosPorResponsavel = [];

        foreach ($this->duracoesDasConcluidas($inicio, $fim) as $duracao) {
            $segundosPorResponsavel[$duracao['responsavel_id']] =
                ($segundosPorResponsavel[$duracao['responsavel_id']] ?? 0) + $duracao['segundos'];
        }

        $nomes = Usuario::query()
            ->whereIn('id', array_keys($segundosPorResponsavel))
            ->pluck('nome', 'id');

        return collect($segundosPorResponsavel)
            ->map(fn ($segundos, $responsavelId) => [
                'nome' => $nomes[$responsavelId] ?? 'Sem responsável',
                'horas' => round($segundos / 3600, 1),
            ])
            ->sortByDesc('horas')
            ->values();
    }

    /**
     * Mesma soma, mas particionada por mês de conclusão, para alimentar gráficos de evolução.
     *
     * @return Collection<int, array{responsavel_id: int, ano: int, mes: int, segundos: int}>
     */
    public function resumoMensalPorColaborador(Carbon $inicio, Carbon $fim): Collection
    {
        $totais = []; // "responsavel_id|ano|mes" => segundos

        foreach ($this->duracoesDasConcluidas($inicio, $fim) as $duracao) {
            $chave = "{$duracao['responsavel_id']}|{$duracao['ano']}|{$duracao['mes']}";
            $totais[$chave] = ($totais[$chave] ?? 0) + $duracao['segundos'];
        }

        return collect($totais)->map(function ($segundos, $chave) {
            [$responsavelId, $ano, $mes] = explode('|', $chave);

            return [
                'responsavel_id' => (int) $responsavelId,
                'ano' => (int) $ano,
                'mes' => (int) $mes,
                'segundos' => $segundos,
            ];
        })->values();
    }

    /**
     * Para cada tarefa concluída dentro do período, calcula quantos segundos se
     * passaram desde que ela entrou numa etapa que conta como trabalho (ex.: "Andamento")
     * até a data de conclusão.
     *
     * @return array<int, array{responsavel_id: int, segundos: int, ano: int, mes: int}>
     */
    private function duracoesDasConcluidas(Carbon $inicio, Carbon $fim): array
    {
        $etapasQueContam = Etapa::query()
            ->where('computa_tempo_trabalho', true)
            ->pluck('id')
            ->all();

        if (empty($etapasQueContam)) {
            return [];
        }

        $tarefas = Tarefa::query()
            ->where('ativo', true)
            ->whereNotNull('data_conclusao')
            ->whereNotNull('responsavel_id')
            ->whereBetween('data_conclusao', [$inicio, $fim])
            ->with(['historico' => fn ($q) => $q->orderBy('created_at')])
            ->get();

        $resultado = [];

        foreach ($tarefas as $tarefa) {
            $inicioTrabalho = $this->momentoEntradaEmAndamento($tarefa, $etapasQueContam);

            if (! $inicioTrabalho) {
                continue;
            }

            $resultado[] = [
                'responsavel_id' => $tarefa->responsavel_id,
                'segundos' => $inicioTrabalho->diffInSeconds($tarefa->data_conclusao, true),
                'ano' => $tarefa->data_conclusao->year,
                'mes' => $tarefa->data_conclusao->month,
            ];
        }

        return $resultado;
    }

    /**
     * Momento em que a tarefa entrou pela primeira vez numa etapa que conta como trabalho.
     * Se ela já nasceu nessa etapa (nenhuma transição registrada de entrada), usa o created_at da tarefa.
     * Retorna null se a tarefa nunca passou por uma etapa que conta como trabalho.
     */
    private function momentoEntradaEmAndamento(Tarefa $tarefa, array $etapasQueContam): ?Carbon
    {
        $primeiraEntrada = $tarefa->historico->first(
            fn ($registro) => in_array($registro->etapa_nova_id, $etapasQueContam, true)
        );

        if ($primeiraEntrada) {
            return $primeiraEntrada->created_at;
        }

        $primeiroRegistro = $tarefa->historico->first();
        $etapaInicial = $primeiroRegistro?->etapa_anterior_id ?? $tarefa->etapa_id;

        return in_array($etapaInicial, $etapasQueContam, true) ? $tarefa->created_at : null;
    }
}
