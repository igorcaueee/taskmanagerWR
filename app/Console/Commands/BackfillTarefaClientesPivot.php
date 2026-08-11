<?php

namespace App\Console\Commands;

use App\Models\Tarefa;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tarefas:backfill-clientes-pivot')]
#[Description('Sincroniza a tabela pivot tarefa_cliente para tarefas que tem cliente_id mas nao tem clientes() associados (ex: ocorrencias de recorrencia geradas antes da correcao).')]
class BackfillTarefaClientesPivot extends Command
{
    public function handle(): int
    {
        $tarefas = Tarefa::whereNotNull('cliente_id')
            ->whereDoesntHave('clientes')
            ->get();

        if ($tarefas->isEmpty()) {
            $this->info('Nenhuma tarefa desincronizada encontrada.');

            return self::SUCCESS;
        }

        $this->info("Sincronizando clientes de {$tarefas->count()} tarefas...");
        $bar = $this->output->createProgressBar($tarefas->count());
        $bar->start();

        foreach ($tarefas as $tarefa) {
            $tarefa->clientes()->sync([$tarefa->cliente_id]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Backfill concluído com sucesso.');

        return self::SUCCESS;
    }
}
