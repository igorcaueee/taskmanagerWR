<?php

namespace App\Console\Commands;

use App\Models\Ciclo;
use App\Models\Cliente;
use App\Models\Departamento;
use App\Models\DocumentoFiscal;
use App\Models\Tarefa;
use App\Models\Usuario;
use App\Services\TarefaRecorrenciaService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

#[Signature('fiscal:alertas-nfe')]
#[Description('Roda as auditorias de NF-e (DIFAL/FCP, quebra de numeração, limite do Simples/MEI) sobre os XMLs já sincronizados de cada cliente e cria/atualiza uma tarefa quando encontra pendência.')]
class VerificarAlertasFiscaisNfe extends Command
{
    public function __construct(private readonly TarefaRecorrenciaService $tarefaRecorrenciaService)
    {
        parent::__construct();
    }

    /**
     * Mesma lógica de "quem recebe a tarefa automática" usada em
     * certificados:verificar — não existe (ainda) um campo de responsável
     * fiscal por cliente, então cai numa usuária fixa como criadora/responsável
     * padrão. Ajuste aqui se isso mudar.
     */
    public function handle(): int
    {
        $silvia = Usuario::where('email', 'silvia@assessoriawr.com')
            ->orWhere('email', 'contato@wrcontabilidade.com.br')
            ->orWhere('nome', 'like', '%Silvia%')
            ->orWhere('nome', 'like', '%Sílvia%')
            ->first();

        if (! $silvia) {
            $this->error('Usuária Silvia não encontrada.');

            return self::FAILURE;
        }

        $etapa = $this->tarefaRecorrenciaService->etapaAFazer();

        if (! $etapa) {
            $this->error('Nenhuma etapa cadastrada.');

            return self::FAILURE;
        }

        $departamentoId = Departamento::where('nome', 'Fiscal')->value('id')
            ?? $silvia->departamento_id
            ?? Departamento::where('nome', 'Recepção')->value('id')
            ?? Departamento::orderBy('id')->value('id');

        if (! $departamentoId) {
            $this->error('Nenhum departamento cadastrado.');

            return self::FAILURE;
        }

        $hoje = Carbon::today();
        $inicioMes = $hoje->copy()->startOfMonth()->toDateString();
        $fimMes = $hoje->toDateString();
        $mesRef = $hoje->format('m/Y');

        $clientes = Cliente::where('status', 'ativo')
            ->where('importar_notas_fiscais', true)
            ->whereNotNull('cpfcnpj')
            ->where('cpfcnpj', '!=', '')
            ->get();

        $criadas = 0;
        $atualizadas = 0;

        foreach ($clientes as $cliente) {
            $achados = $this->apurarAchados($cliente, $inicioMes, $fimMes);

            if ($achados === []) {
                continue;
            }

            // Título carrega o mês: assim os achados do mês se acumulam numa única
            // tarefa (atualiza a descrição a cada rodada) em vez de criar uma nova
            // por dia, e o mês seguinte já começa uma tarefa nova naturalmente.
            $titulo = "Alertas Fiscais NF-e — {$mesRef} — {$cliente->nome}";
            $descricao = "Auditoria automática dos XMLs de NF-e sincronizados (não substitui conferência manual):\n\n"
                .implode("\n", $achados)
                ."\n\nDetalhe na aba Dashboards da tela de NF-e.";

            $tarefaExistente = Tarefa::where('cliente_id', $cliente->id)
                ->where('titulo', $titulo)
                ->whereNull('data_conclusao')
                ->first();

            if ($tarefaExistente) {
                if ($tarefaExistente->descricao !== $descricao) {
                    $tarefaExistente->update(['descricao' => $descricao]);
                    $atualizadas++;
                }

                continue;
            }

            $ciclo = Ciclo::findOrCreateForDate($hoje->copy());

            $tarefa = Tarefa::create([
                'titulo' => $titulo,
                'descricao' => $descricao,
                'cliente_id' => $cliente->id,
                'departamento_id' => $departamentoId,
                'etapa_id' => $etapa->id,
                'responsavel_id' => $silvia->id,
                'criado_por' => $silvia->id,
                'data_vencimento' => $hoje->copy()->addDays(3),
                'prioridade' => 2,
                'recorrente' => false,
                'frequencia' => 'nenhuma',
                'ciclo_id' => $ciclo->id,
            ]);

            $tarefa->clientes()->sync([$cliente->id]);

            $criadas++;
            $this->line("  ⚠ {$cliente->nome}: ".count($achados).' achado(s).');
        }

        $this->info("Concluído: {$criadas} tarefa(s) criada(s), {$atualizadas} atualizada(s).");

        return self::SUCCESS;
    }

    /**
     * Roda as três auditorias já usadas na aba Dashboards da tela de NF-e
     * (DocumentoFiscal::auditoriaDifalFcp / quebrasNumeracaoNfe / monitorLimiteSimples)
     * pro mês corrente e traduz o que passou do limiar de alerta em texto.
     * Cada auditoria é isolada num try/catch — um cliente com XML problemático
     * não pode derrubar a rodada dos outros.
     *
     * @return array<int, string>
     */
    private function apurarAchados(Cliente $cliente, string $dataInicio, string $dataFim): array
    {
        $achados = [];

        try {
            $difal = DocumentoFiscal::auditoriaDifalFcp($cliente->id, $dataInicio, $dataFim);
            $pendentes = ($difal['contadores']['faltou'] ?? 0) + ($difal['contadores']['inconsistente'] ?? 0);

            if ($pendentes > 0) {
                $linha = sprintf(
                    '• DIFAL/FCP: %d nota(s) com pendência (%d sem destaque, %d inconsistente(s))',
                    $pendentes,
                    $difal['contadores']['faltou'] ?? 0,
                    $difal['contadores']['inconsistente'] ?? 0
                );

                if (($difal['totalDifalEstimadoFaltante'] ?? 0) > 0) {
                    $linha .= sprintf(', estimado R$ %s não recolhido', number_format($difal['totalDifalEstimadoFaltante'], 2, ',', '.'));
                }

                $achados[] = $linha.'.';
            }
        } catch (\Throwable $e) {
            Log::warning('[fiscal:alertas-nfe] falha na auditoria DIFAL/FCP', ['cliente_id' => $cliente->id, 'msg' => $e->getMessage()]);
        }

        try {
            $quebra = DocumentoFiscal::quebrasNumeracaoNfe($cliente->id, $dataInicio, $dataFim);

            if (($quebra['totalFaltando'] ?? 0) > 0) {
                $series = collect($quebra['series'])
                    ->where('qtdFaltando', '>', 0)
                    ->pluck('serie')
                    ->unique()
                    ->implode(', ');

                $achados[] = "• Quebra de numeração: {$quebra['totalFaltando']} número(s) sem nota (série(s) {$series}).";
            }
        } catch (\Throwable $e) {
            Log::warning('[fiscal:alertas-nfe] falha na quebra de numeração', ['cliente_id' => $cliente->id, 'msg' => $e->getMessage()]);
        }

        try {
            $limite = DocumentoFiscal::monitorLimiteSimples($cliente->id, $dataInicio, $dataFim);

            if (in_array($limite['status'], ['atencao', 'tolerancia', 'estouro'], true)) {
                $rotulos = [
                    'atencao' => 'atenção (≥80% do limite)',
                    'tolerancia' => 'ultrapassou o limite, dentro da tolerância de 20%',
                    'estouro' => 'limite estourado — risco de exclusão retroativa',
                ];

                $achados[] = sprintf(
                    '• Limite do %s: %s — RBT12 de R$ %s (%s%% do limite).',
                    $limite['regime'] ?? 'regime',
                    $rotulos[$limite['status']],
                    number_format($limite['rbt12'], 2, ',', '.'),
                    $limite['percentual']
                );
            }
        } catch (\Throwable $e) {
            Log::warning('[fiscal:alertas-nfe] falha no monitor de limite', ['cliente_id' => $cliente->id, 'msg' => $e->getMessage()]);
        }

        return $achados;
    }
}
