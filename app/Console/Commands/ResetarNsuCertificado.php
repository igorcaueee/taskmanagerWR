<?php

namespace App\Console\Commands;

use App\Models\ClienteCertificadoNfse;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('fiscal:resetar-nsu-certificado {cnpj : CNPJ do cliente (com ou sem pontuação)} {--tipo=nfe : nfe, cte ou ambos} {--force : Pula a confirmação interativa}')]
#[Description('Zera o NSU nacional (NF-e e/ou CT-e) do certificado de um único cliente, para forçar a Sefaz a redistribuir o histórico desde o início nesse fluxo — sem afetar outros clientes.')]
class ResetarNsuCertificado extends Command
{
    public function handle(): int
    {
        $cnpj = preg_replace('/[.\-\/\s]/', '', (string) $this->argument('cnpj'));
        $tipo = $this->option('tipo');

        if (!in_array($tipo, ['nfe', 'cte', 'ambos'], true)) {
            $this->error('Opção --tipo inválida. Use nfe, cte ou ambos.');
            return self::FAILURE;
        }

        $certificados = ClienteCertificadoNfse::whereHas('cliente', function ($q) use ($cnpj) {
            $q->whereRaw("REPLACE(REPLACE(REPLACE(cpfcnpj, '.', ''), '-', ''), '/', '') = ?", [$cnpj]);
        })->with('cliente')->get();

        if ($certificados->isEmpty()) {
            $this->error("Nenhum certificado encontrado para o CNPJ {$cnpj}.");
            return self::FAILURE;
        }

        foreach ($certificados as $certificado) {
            $this->line("Certificado #{$certificado->id} — cliente: {$certificado->cliente?->razao_social} "
                . "— NSU NF-e atual: {$certificado->ultimo_nsu_nfe} — NSU CT-e atual: {$certificado->ultimo_nsu_cte}");
        }

        $this->warn("Isso vai zerar o NSU de {$tipo} do(s) certificado(s) acima, fazendo a próxima busca reconsultar "
            . "a Sefaz desde o início desse fluxo. Documentos já salvos em documentos_fiscais não são apagados nem "
            . "duplicados (são atualizados pela chave de acesso).");

        if (!$this->option('force') && !$this->confirm('Confirma o reset?')) {
            $this->info('Operação cancelada.');
            return self::SUCCESS;
        }

        $update = [];
        if ($tipo === 'nfe' || $tipo === 'ambos') {
            $update['ultimo_nsu_nfe'] = 0;
        }
        if ($tipo === 'cte' || $tipo === 'ambos') {
            $update['ultimo_nsu_cte'] = 0;
        }

        foreach ($certificados as $certificado) {
            $certificado->update($update);
        }

        Log::warning('[Cofre Fiscal] NSU resetado via comando fiscal:resetar-nsu-certificado', [
            'cnpj'            => $cnpj,
            'tipo'            => $tipo,
            'certificado_ids' => $certificados->pluck('id')->all(),
        ]);

        $this->info("Concluído: NSU ({$tipo}) zerado em " . $certificados->count() . ' certificado(s).');

        return self::SUCCESS;
    }
}
