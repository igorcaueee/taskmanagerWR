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
     * (ex.: "Andamento") e ser concluída — recortado para não contar tempo anterior
     * ao início do período consultado.
     *
     * @return Collection<int, array{nome: string, horas: float}>
     */
    public function horasPorColaborador(Carbon $inicio, Carbon $fim): Collection
    {
        $segundosPorResponsavel = [];

        foreach ($this->intervalosTrabalhados($inicio, $fim) as $intervalo) {
            $segundos = $intervalo['inicio']->diffInSeconds($intervalo['fim'], true);
            $segundosPorResponsavel[$intervalo['responsavel_id']] =
                ($segundosPorResponsavel[$intervalo['responsavel_id']] ?? 0) + $segundos;
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
     * Mesma soma, mas com cada intervalo trabalhado particionado por mês (uma tarefa que
     * ficou meses em "Andamento" tem seu tempo dividido entre os meses que ela atravessou,
     * em vez de jogar tudo no mês da conclusão), para alimentar gráficos de evolução.
     *
     * @return Collection<int, array{responsavel_id: int, ano: int, mes: int, segundos: int}>
     */
    public function resumoMensalPorColaborador(Carbon $inicio, Carbon $fim): Collection
    {
        $totais = []; // "responsavel_id|ano|mes" => segundos

        foreach ($this->intervalosTrabalhados($inicio, $fim) as $intervalo) {
            foreach ($this->particionarPorMes($intervalo['inicio'], $intervalo['fim']) as $parte) {
                $chave = "{$intervalo['responsavel_id']}|{$parte['ano']}|{$parte['mes']}";
                $totais[$chave] = ($totais[$chave] ?? 0) + $parte['segundos'];
            }
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
     * Para cada tarefa concluída dentro do período, retorna o intervalo (responsavel_id,
     * início, fim) entre ela entrar numa etapa que conta como trabalho e ser concluída.
     * O início é recortado para nunca ser anterior ao início do período consultado —
     * assim, tarefas que já estavam em "Andamento" antes do período não têm esse tempo
     * anterior contabilizado.
     *
     * @return array<int, array{responsavel_id: int, inicio: Carbon, fim: Carbon}>
     */
    private function intervalosTrabalhados(Carbon $inicio, Carbon $fim): array
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

            $inicioEfetivo = $inicioTrabalho->max($inicio);

            if ($inicioEfetivo->greaterThanOrEqualTo($tarefa->data_conclusao)) {
                continue;
            }

            $resultado[] = [
                'responsavel_id' => $tarefa->responsavel_id,
                'inicio' => $inicioEfetivo,
                'fim' => $tarefa->data_conclusao,
            ];
        }

        return $resultado;
    }

    /**
     * Divide o intervalo [$inicio, $fim] nas fatias correspondentes a cada mês que ele atravessa.
     *
     * @return array<int, array{ano: int, mes: int, segundos: int}>
     */
    private function particionarPorMes(Carbon $inicio, Carbon $fim): array
    {
        $partes = [];
        $cursor = $inicio->copy();

        while ($cursor->lessThan($fim)) {
            $fimDoMes = $cursor->copy()->endOfMonth()->min($fim);

            $partes[] = [
                'ano' => $cursor->year,
                'mes' => $cursor->month,
                'segundos' => $cursor->diffInSeconds($fimDoMes, true),
            ];

            $cursor = $fimDoMes->equalTo($fim) ? $fim->copy() : $fimDoMes->copy()->addSecond();
        }

        return $partes;
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
