<?php

namespace App\Console\Commands;

use App\Models\Ciclo;
use App\Models\Cliente;
use App\Models\Departamento;
use App\Models\Etapa;
use App\Models\Tarefa;
use App\Models\Usuario;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('certificados:verificar')]
#[Description('Cria tarefas automáticas para certificados que vencem em 30 dias.')]
class VerificarCertificados extends Command
{
    public function handle(): int
    {
        $silvia = Usuario::where('email', 'silvia@assessoriawr.com')->first();

        if (! $silvia) {
            $this->error('Usuária Silvia não encontrada. Execute: php artisan db:seed --class=SilviaUserSeeder');

            return self::FAILURE;
        }

        $etapa = Etapa::orderBy('ordem')->first();

        if (! $etapa) {
            $this->error('Nenhuma etapa cadastrada.');

            return self::FAILURE;
        }

        $departamentoId = $silvia->departamento_id
            ?? Departamento::where('nome', 'Recepção')->value('id')
            ?? Departamento::orderBy('nome')->value('id');

        if (! $departamentoId) {
            $this->error('Nenhum departamento cadastrado.');

            return self::FAILURE;
        }

        $dataAlvo = Carbon::today()->addDays(30)->toDateString();

        $clientes = Cliente::whereDate('vencimento_certificado', $dataAlvo)
            ->where('status', 'ativo')
            ->get();

        if ($clientes->isEmpty()) {
            $this->info("Nenhum certificado vencendo em {$dataAlvo}.");

            return self::SUCCESS;
        }

        $criadas = 0;

        foreach ($clientes as $cliente) {
            $titulo = "Renovação de Certificado — {$cliente->nome}";

            $jaExiste = Tarefa::where('cliente_id', $cliente->id)
                ->where('titulo', $titulo)
                ->whereNull('data_conclusao')
                ->exists();

            if ($jaExiste) {
                continue;
            }

            $vencimento = Carbon::parse($cliente->vencimento_certificado);
            $dataTarefa = $vencimento->copy()->subDays(30);
            $ciclo = Ciclo::findOrCreateForDate($dataTarefa->copy());

            Tarefa::create([
                'titulo'          => $titulo,
                'descricao'       => "Certificado digital do cliente {$cliente->nome} vence em {$vencimento->format('d/m/Y')}. Providenciar renovação.",
                'cliente_id'      => $cliente->id,
                'departamento_id' => $departamentoId,
                'etapa_id'        => $etapa->id,
                'responsavel_id'  => $silvia->id,
                'criado_por'      => $silvia->id,
                'data_vencimento' => $vencimento->copy()->subDays(30),
                'prioridade'      => 4,
                'recorrente'      => false,
                'frequencia'      => 'nenhuma',
                'ciclo_id'        => $ciclo->id,
            ]);

            $criadas++;
            $this->line("  ✓ Tarefa criada para: {$cliente->nome}");
        }

        $this->info("Concluído: {$criadas} tarefa(s) criada(s).");

        return self::SUCCESS;
    }
}
