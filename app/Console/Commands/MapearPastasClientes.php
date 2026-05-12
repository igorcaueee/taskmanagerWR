<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MapearPastasClientes extends Command
{
    protected $signature = 'clientes:mapear-pastas
                            {--dry-run : Mostra o que seria feito sem salvar}
                            {--threshold=60 : Similaridade mínima (0-100) para aceitar um match automático}
                            {--fix-mei : Corrige registros salvos com o nome antigo incorreto da pasta MEI}
                            {--relatorio : Gera relatório CSV de todos os clientes com status do mapeamento}
                            {--output= : Caminho do arquivo de saída do relatório (padrão: relatorio_pastas.csv)}';

    protected $description = 'Tenta mapear automaticamente clientes às pastas existentes no servidor pelo nome';

    private const MEI_FOLDER       = 'MICROEMPRENDEDOR INDIVIDUAL';
    private const MEI_FOLDER_WRONG = 'MICROEMPREENDEDOR INDIVIDUAL';

    public function handle(): int
    {
        if ($this->option('fix-mei')) {
            return $this->fixMei();
        }

        if ($this->option('relatorio')) {
            return $this->gerarRelatorio();
        }

        $dryRun    = $this->option('dry-run');
        $threshold = (int) $this->option('threshold');

        $disk = Storage::disk('shared');

        $this->info('Lendo pastas do servidor...');

        try {
            $rootFolders = collect($disk->directories())->map(fn ($d) => basename($d));
            $meiFolders  = collect($disk->directories(self::MEI_FOLDER))->map(fn ($d) => basename($d));
        } catch (\Throwable $e) {
            $this->error('Não foi possível ler o disco "shared": ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Pastas na raiz: {$rootFolders->count()} | Pastas em " . self::MEI_FOLDER . ": {$meiFolders->count()}");

        $clientes = Cliente::whereNull('pasta_arquivos')->orWhere('pasta_arquivos', '')->get();

        if ($clientes->isEmpty()) {
            $this->info('Todos os clientes já têm pasta_arquivos configurada.');
            return self::SUCCESS;
        }

        $this->info("Clientes sem pasta configurada: {$clientes->count()}");
        $this->newLine();

        $matched   = 0;
        $auto      = 0;
        $ambiguous = 0;
        $noMatch   = 0;

        $rows = [];

        foreach ($clientes as $cliente) {
            $isMei   = strtoupper($cliente->regime_tributario ?? '') === 'MEI';
            $folders = $isMei ? $meiFolders : $rootFolders;

            $best      = null;
            $bestScore = 0;
            $ties      = 0;

            foreach ($folders as $folder) {
                $score = $this->similarity(
                    mb_strtolower($cliente->nome),
                    mb_strtolower($folder)
                );

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best      = $folder;
                    $ties      = 1;
                } elseif ($score === $bestScore && $score > 0) {
                    $ties++;
                }
            }

            if ($bestScore >= $threshold && $ties === 1) {
                $fullPath = $isMei ? self::MEI_FOLDER . '/' . $best : $best;
                $status   = $dryRun ? '[DRY-RUN] mapearia' : 'mapeado';

                if (! $dryRun) {
                    $cliente->pasta_arquivos = $fullPath;
                    $cliente->save();
                }

                $rows[] = [$cliente->nome, $fullPath, "{$bestScore}%", $status];
                $matched++;
            } elseif ($bestScore >= $threshold && $ties > 1) {
                $rows[] = [$cliente->nome, "ambíguo ({$ties} pastas com {$bestScore}%)", '', 'ignorado'];
                $ambiguous++;
            } else {
                if ($isMei) {
                    $fullPath = self::MEI_FOLDER . '/' . $cliente->nome;
                    $status   = $dryRun ? '[DRY-RUN] mapearia (fallback)' : 'mapeado (fallback)';

                    if (! $dryRun) {
                        $cliente->pasta_arquivos = $fullPath;
                        $cliente->save();
                    }

                    $rows[] = [$cliente->nome, $fullPath, '—', $status];
                    $matched++;
                } else {
                    $rows[] = [$cliente->nome, $best ?? '—', $best ? "{$bestScore}%" : '—', 'sem match'];
                    $noMatch++;
                }
            }
        }

        $this->table(
            ['Cliente', 'Pasta', 'Score', 'Status'],
            $rows
        );

        $this->newLine();
        $this->info("Mapeados: {$matched} | Automáticos MEI: {$auto} | Ambíguos: {$ambiguous} | Sem match: {$noMatch}");

        if ($noMatch > 0 || $ambiguous > 0) {
            $this->newLine();
            $this->warn('Para os clientes sem match ou ambíguos, edite manualmente em:');
            $this->line('  Detalhe do cliente → Editar → "Pasta de Arquivos no Servidor"');
        }

        return self::SUCCESS;
    }

    private function gerarRelatorio(): int
    {
        $output = $this->option('output') ?: 'relatorio_pastas.csv';

        $clientes = Cliente::orderBy('nome')->get(['id', 'nome', 'regime_tributario', 'pasta_arquivos', 'status']);

        $mapeados    = 0;
        $naoMapeados = 0;

        $file = fopen($output, 'w');
        fprintf($file, "\xEF\xBB\xBF"); // BOM UTF-8 para abrir corretamente no Excel
        fputcsv($file, ['ID', 'Nome', 'Regime', 'Status Cliente', 'Pasta Configurada', 'Status Mapeamento'], ';');

        foreach ($clientes as $cliente) {
            $isMei         = strtoupper($cliente->regime_tributario ?? '') === 'MEI';
            $temPasta      = ! empty($cliente->pasta_arquivos);
            $pastaEfetiva  = $cliente->pasta_arquivos
                ?: ($isMei ? self::MEI_FOLDER . '/' . $cliente->nome : '');

            if ($temPasta || $isMei) {
                $statusMap = $temPasta ? 'mapeado' : 'automático (MEI)';
                $mapeados++;
            } else {
                $statusMap = 'não mapeado';
                $naoMapeados++;
            }

            fputcsv($file, [
                $cliente->id,
                $cliente->nome,
                strtoupper($cliente->regime_tributario ?? ''),
                $cliente->status,
                $pastaEfetiva,
                $statusMap,
            ], ';');
        }

        fclose($file);

        $total = $mapeados + $naoMapeados;
        $this->info("Relatório gerado: {$output}");
        $this->info("Total: {$total} | Mapeados: {$mapeados} | Não mapeados: {$naoMapeados}");

        return self::SUCCESS;
    }

    private function fixMei(): int
    {
        $dryRun = $this->option('dry-run');
        $wrong  = self::MEI_FOLDER_WRONG . '/';
        $right  = self::MEI_FOLDER . '/';

        $clientes = Cliente::where('pasta_arquivos', 'like', $wrong . '%')->get();

        if ($clientes->isEmpty()) {
            $this->info('Nenhum registro com nome incorreto encontrado.');
            return self::SUCCESS;
        }

        $this->info("Registros com nome incorreto: {$clientes->count()}");
        $this->newLine();

        $rows = [];

        foreach ($clientes as $cliente) {
            $novo   = str_replace($wrong, $right, $cliente->pasta_arquivos);
            $status = $dryRun ? '[DRY-RUN] corrigiria' : 'corrigido';

            if (! $dryRun) {
                $cliente->pasta_arquivos = $novo;
                $cliente->save();
            }

            $rows[] = [$cliente->nome, $cliente->pasta_arquivos, $novo, $status];
        }

        $this->table(['Cliente', 'Antes', 'Depois', 'Status'], $rows);
        $this->newLine();
        $this->info("Total: {$clientes->count()} registros " . ($dryRun ? 'que seriam corrigidos.' : 'corrigidos.'));

        return self::SUCCESS;
    }

    private function similarity(string $a, string $b): int
    {
        if ($a === $b) {
            return 100;
        }

        $clean = fn (string $s) => preg_replace('/\s+/', ' ', trim(preg_replace('/[.\-_\/\\\\]/', ' ', $s)));

        $a = $clean($a);
        $b = $clean($b);

        if ($a === $b) {
            return 95;
        }

        if (str_contains($b, $a) || str_contains($a, $b)) {
            return 85;
        }

        similar_text($a, $b, $percent);

        return (int) round($percent);
    }
}
