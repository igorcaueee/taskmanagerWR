<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MapearPastasClientes extends Command
{
    protected $signature = 'clientes:mapear-pastas
                            {--dry-run : Mostra o que seria feito sem salvar}
                            {--threshold=60 : Similaridade mínima (0-100) para aceitar um match automático}';

    protected $description = 'Tenta mapear automaticamente clientes às pastas existentes no servidor pelo nome';

    public function handle(): int
    {
        $dryRun    = $this->option('dry-run');
        $threshold = (int) $this->option('threshold');

        $disk = Storage::disk('shared');

        $this->info('Lendo pastas do servidor...');

        try {
            $folders = collect($disk->directories())->map(fn ($d) => basename($d));
        } catch (\Throwable $e) {
            $this->error('Não foi possível ler o disco "shared": ' . $e->getMessage());
            return self::FAILURE;
        }

        if ($folders->isEmpty()) {
            $this->warn('Nenhuma pasta encontrada na raiz do disco compartilhado.');
            return self::SUCCESS;
        }

        $this->info("Pastas encontradas: {$folders->count()}");

        $clientes = Cliente::whereNull('pasta_arquivos')->orWhere('pasta_arquivos', '')->get();

        if ($clientes->isEmpty()) {
            $this->info('Todos os clientes já têm pasta_arquivos configurada.');
            return self::SUCCESS;
        }

        $this->info("Clientes sem pasta configurada: {$clientes->count()}");
        $this->newLine();

        $matched   = 0;
        $ambiguous = 0;
        $noMatch   = 0;

        $rows = [];

        foreach ($clientes as $cliente) {
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
                $status = $dryRun ? '[DRY-RUN] mapearia' : 'mapeado';

                if (! $dryRun) {
                    $cliente->pasta_arquivos = $best;
                    $cliente->save();
                }

                $rows[] = [$cliente->nome, $best, "{$bestScore}%", $status];
                $matched++;
            } elseif ($bestScore >= $threshold && $ties > 1) {
                $rows[] = [$cliente->nome, "ambíguo ({$ties} pastas com {$bestScore}%)", '', 'ignorado'];
                $ambiguous++;
            } else {
                $rows[] = [$cliente->nome, $best ?? '—', $best ? "{$bestScore}%" : '—', 'sem match'];
                $noMatch++;
            }
        }

        $this->table(
            ['Cliente', 'Pasta', 'Score', 'Status'],
            $rows
        );

        $this->newLine();
        $this->info("Mapeados: {$matched} | Ambíguos: {$ambiguous} | Sem match: {$noMatch}");

        if ($noMatch > 0 || $ambiguous > 0) {
            $this->newLine();
            $this->warn('Para os clientes sem match ou ambíguos, edite manualmente em:');
            $this->line('  Detalhe do cliente → Editar → "Pasta de Arquivos no Servidor"');
            $this->newLine();
            $this->warn('Ou gere um CSV para importação em lote com o comando abaixo e ajuste manualmente:');
            $this->line('  php artisan clientes:mapear-pastas --dry-run > resultado.txt');
        }

        return self::SUCCESS;
    }

    private function similarity(string $a, string $b): int
    {
        // Exact match
        if ($a === $b) {
            return 100;
        }

        // Remove common noise: dots, dashes, extra spaces, special chars
        $clean = fn (string $s) => preg_replace('/\s+/', ' ', trim(preg_replace('/[.\-_\/\\\\]/', ' ', $s)));

        $a = $clean($a);
        $b = $clean($b);

        if ($a === $b) {
            return 95;
        }

        // Check if one contains the other
        if (str_contains($b, $a) || str_contains($a, $b)) {
            return 85;
        }

        // similar_text percentage
        similar_text($a, $b, $percent);

        return (int) round($percent);
    }
}
