<?php

namespace App\Console\Commands;

use App\Models\Ciclo;
use App\Models\Etapa;
use App\Models\RelTarefa;
use App\Models\Tarefa;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ciclos:passar-afazer')]
#[Description('Passa todas as tarefas "A Fazer" de ciclos passados para o ciclo atual.')]
class PassarTarefasAFazerCommand extends Command
{
    public function handle(): int
    {
        $cicloAtual = Ciclo::current();
        $hoje = Carbon::today();

        $etapaAFazer = Etapa::where('nome', 'A Fazer')->first();
        $etapaTransferido = Etapa::where('nome', 'Transferido para o próximo ciclo')->first();

        if (! $etapaAFazer) {
            $this->error('Etapa "A Fazer" não encontrada.');

            return self::FAILURE;
        }

        $tarefas = Tarefa::whereHas('ciclo', fn ($q) => $q->where('data_fim', '<', $hoje))
            ->where('etapa_id', $etapaAFazer->id)
            ->where('ciclo_id', '!=', $cicloAtual->id)
            ->get();

        if ($tarefas->isEmpty()) {
            $this->info('Nenhuma tarefa "A Fazer" de ciclos passados encontrada.');

            return self::SUCCESS;
        }

        $this->info("Passando {$tarefas->count()} tarefa(s) para o ciclo atual ({$cicloAtual->nome})...");

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

        $this->info("Concluído! {$tarefas->count()} tarefa(s) movidas para o ciclo atual.");

        return self::SUCCESS;
    }
}
