<?php

namespace App\Console\Commands;

use App\Models\Ciclo;
use App\Models\Tarefa;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ciclos:backfill')]
#[Description('Atribui ciclos a todas as tarefas que ainda não possuem ciclo.')]
class BackfillCiclos extends Command
{
    public function handle(): int
    {
        $tarefas = Tarefa::whereNull('ciclo_id')->get();

        if ($tarefas->isEmpty()) {
            $this->info('Nenhuma tarefa sem ciclo encontrada.');

            return self::SUCCESS;
        }

        $this->info("Atribuindo ciclos a {$tarefas->count()} tarefas...");
        $bar = $this->output->createProgressBar($tarefas->count());
        $bar->start();

        foreach ($tarefas as $tarefa) {
            $ciclo = Ciclo::findOrCreateForDate($tarefa->data_vencimento->copy());
            $tarefa->update(['ciclo_id' => $ciclo->id]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Backfill concluído com sucesso.');

        return self::SUCCESS;
    }
}
