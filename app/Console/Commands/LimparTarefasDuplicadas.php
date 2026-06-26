<?php

namespace App\Console\Commands;

use App\Models\Tarefa;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('tarefas:limpar-duplicadas {--dry-run : Apenas lista as duplicatas sem inativar}')]
#[Description('Inativa tarefas duplicadas (mesmo título + cliente + responsável + data), mantendo a que tem tipo preenchido ou a mais recente.')]
class LimparTarefasDuplicadas extends Command
{
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Agrupa tarefas ativas com a mesma combinação título+cliente+responsável+data
        $grupos = Tarefa::where('ativo', true)
            ->select('titulo', 'cliente_id', 'responsavel_id', 'data_vencimento')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('titulo', 'cliente_id', 'responsavel_id', 'data_vencimento')
            ->having('total', '>', 1)
            ->get();

        if ($grupos->isEmpty()) {
            $this->info('Nenhuma tarefa duplicada encontrada.');
            return self::SUCCESS;
        }

        $this->info("Grupos duplicados encontrados: {$grupos->count()}");
        $this->newLine();

        $totalInativadas = 0;

        foreach ($grupos as $grupo) {
            $tarefas = Tarefa::where('titulo', $grupo->titulo)
                ->where('cliente_id', $grupo->cliente_id)
                ->where('responsavel_id', $grupo->responsavel_id)
                ->where('data_vencimento', $grupo->data_vencimento)
                ->where('ativo', true)
                ->orderByRaw('tipo_tarefa_id IS NULL ASC') // tarefas COM tipo primeiro
                ->orderBy('id', 'asc')
                ->get();

            // Mantém a primeira (com tipo preenchido ou a mais antiga com tipo)
            $manter = $tarefas->first();
            $inativar = $tarefas->slice(1);

            $this->line("  Mantendo  [ID {$manter->id}] \"{$manter->titulo}\" | tipo=" . ($manter->tipo_tarefa_id ?? 'null'));
            foreach ($inativar as $t) {
                $this->warn("  Inativando [ID {$t->id}] \"{$t->titulo}\" | tipo=" . ($t->tipo_tarefa_id ?? 'null'));
            }
            $this->newLine();

            if (! $dryRun) {
                Tarefa::whereIn('id', $inativar->pluck('id'))->update(['ativo' => false]);
            }

            $totalInativadas += $inativar->count();
        }

        if ($dryRun) {
            $this->warn("DRY RUN — nenhuma tarefa foi alterada. {$totalInativadas} seriam inativadas.");
        } else {
            $this->info("Concluído. {$totalInativadas} tarefa(s) duplicada(s) inativada(s).");
        }

        return self::SUCCESS;
    }
}
