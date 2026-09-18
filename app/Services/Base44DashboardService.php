<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Consulta o dashboard fiscal do base44 (app externo hospedado em
 * dashboard-wr.base44.app) para exibir no Portal do Cliente, filtrando pelo
 * CNPJ do cliente.
 */
class Base44DashboardService
{
    /**
     * @return array{ok: bool, data: array<string, mixed>|null, error: ?string}
     */
    public function buscarDashboardFiscal(string $cnpj): array
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        $url = rtrim(config('services.base44.dashboard_url'), '/').'/functions/dashboardFiscal';
        $token = config('services.base44.dashboard_token');

        if (! $url || ! $token) {
            return ['ok' => false, 'data' => null, 'error' => 'Integração com o dashboard fiscal não configurada.'];
        }

        try {
            $resposta = Http::timeout(15)
                ->withHeaders(['x-api-key' => $token])
                ->get($url, ['cnpj' => $cnpj]);
        } catch (\Throwable $e) {
            Log::warning('[Base44Dashboard] buscarDashboardFiscal: falha de conexão', ['cnpj' => $cnpj, 'erro' => $e->getMessage()]);

            return ['ok' => false, 'data' => null, 'error' => 'Não foi possível conectar ao dashboard fiscal.'];
        }

        if ($resposta->failed()) {
            $mensagem = $resposta->json('error') ?? 'Dashboard fiscal indisponível para este CNPJ.';

            if ($resposta->status() !== 404) {
                Log::warning('[Base44Dashboard] buscarDashboardFiscal: resposta não OK', ['cnpj' => $cnpj, 'status' => $resposta->status()]);
            }

            return ['ok' => false, 'data' => null, 'error' => $mensagem];
        }

        return ['ok' => true, 'data' => $resposta->json(), 'error' => null];
    }

    /**
     * A resposta bruta do base44 muda de formato conforme o regime tributário
     * (Simples Nacional, Lucro Presumido, Lucro Real). Esta função normaliza o
     * necessário para o Portal do Cliente exibir um dashboard único e completo,
     * independente do regime.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function normalizar(array $data): array
    {
        $apuracoes = $data['apuracoes'] ?? [];
        $ultima = ! empty($apuracoes) ? end($apuracoes) : [];

        $valor = fn (array $ap, array $chaves) => collect($chaves)
            ->map(fn ($chave) => $ap[$chave] ?? null)
            ->first(fn ($v) => $v !== null);

        // ICMS: Lucro Presumido/Real trazem objeto "icms" no topo; monta a
        // partir das apurações quando não vier pronto.
        $icms = null;
        if (! empty($data['icms'])) {
            $icms = $data['icms'];
        } elseif (($ultima['icms_saldo'] ?? null) !== null) {
            $icms = [
                'debito' => $ultima['icms_debito'] ?? null,
                'credito' => $ultima['icms_credito'] ?? null,
                'saldo' => $ultima['icms_saldo'] ?? null,
                'evolucao' => collect($apuracoes)
                    ->map(fn ($ap) => ['mes' => $ap['periodo'] ?? '', 'valor' => $ap['icms_saldo'] ?? 0])
                    ->all(),
            ];
        }

        $evolucaoImposto = fn (array $chaves) => collect($apuracoes)
            ->map(fn ($ap) => ['mes' => $ap['periodo'] ?? '', 'valor' => $valor($ap, $chaves) ?? 0])
            ->all();

        $pis = ($valor($ultima, ['pis_saldo', 'valor_pis']) !== null) ? [
            'debito' => $valor($ultima, ['pis_debito']),
            'credito' => $valor($ultima, ['pis_credito']),
            'saldo' => $valor($ultima, ['pis_saldo', 'valor_pis']),
            'evolucao' => $evolucaoImposto(['pis_saldo', 'valor_pis']),
        ] : null;

        $cofins = ($valor($ultima, ['cofins_saldo', 'valor_cofins']) !== null) ? [
            'debito' => $valor($ultima, ['cofins_debito']),
            'credito' => $valor($ultima, ['cofins_credito']),
            'saldo' => $valor($ultima, ['cofins_saldo', 'valor_cofins']),
            'evolucao' => $evolucaoImposto(['cofins_saldo', 'valor_cofins']),
        ] : null;

        $ordenarPorValor = fn (array $itens) => collect($itens)
            ->filter(fn ($i) => ($i['valor'] ?? 0) > 0)
            ->sortByDesc('valor')
            ->take(10)
            ->values()
            ->all();

        $faturamentoMensal = $data['faturamento_mensal'] ?? [];

        // Snapshot por mês (usado pelo seletor de mês no portal): a API não
        // aceita filtro de período no servidor, então guardamos aqui os dados
        // de cada apuração para trocar de mês só no front-end.
        $meses = collect($apuracoes)->values()->map(function (array $ap, int $i) use ($valor, $ordenarPorValor, $faturamentoMensal) {
            return [
                'periodo' => $ap['periodo'] ?? null,
                'label' => $faturamentoMensal[$i]['mes'] ?? ($ap['periodo'] ?? ('Mês '.($i + 1))),
                'compras' => $valor($ap, ['total_compras']),
                'vendas' => $valor($ap, ['total_vendas', 'receita_bruta_periodo']),
                'servicos' => $valor($ap, ['total_servicos']),
                'icms' => ($valor($ap, ['icms_saldo']) !== null) ? [
                    'debito' => $valor($ap, ['icms_debito']),
                    'credito' => $valor($ap, ['icms_credito']),
                    'saldo' => $valor($ap, ['icms_saldo']),
                ] : null,
                'pis' => ($valor($ap, ['pis_saldo', 'valor_pis']) !== null) ? [
                    'debito' => $valor($ap, ['pis_debito']),
                    'credito' => $valor($ap, ['pis_credito']),
                    'saldo' => $valor($ap, ['pis_saldo', 'valor_pis']),
                ] : null,
                'cofins' => ($valor($ap, ['cofins_saldo', 'valor_cofins']) !== null) ? [
                    'debito' => $valor($ap, ['cofins_debito']),
                    'credito' => $valor($ap, ['cofins_credito']),
                    'saldo' => $valor($ap, ['cofins_saldo', 'valor_cofins']),
                ] : null,
                'cfopVendas' => $ordenarPorValor($ap['vendas_por_cfop'] ?? []),
                'cfopCompras' => $ordenarPorValor($ap['compras_por_cfop'] ?? []),
                'topClientes' => $ap['top_clientes'] ?? [],
                'topFornecedores' => $ap['top_fornecedores'] ?? [],
            ];
        })->values()->all();

        return [
            'regime' => $data['regime'] ?? null,
            'empresa' => $data['empresa'] ?? [],
            'kpis' => $data['kpis'] ?? [],
            'faturamentoMensal' => $faturamentoMensal,
            'rbt12' => $data['rbt12'] ?? null,
            'icms' => $icms,
            'pis' => $pis,
            'cofins' => $cofins,
            'cfopVendas' => $ordenarPorValor($ultima['vendas_por_cfop'] ?? []),
            'cfopCompras' => $ordenarPorValor($ultima['compras_por_cfop'] ?? []),
            'topClientes' => $ultima['top_clientes'] ?? [],
            'topFornecedores' => $ultima['top_fornecedores'] ?? [],
            'meses' => $meses,
        ];
    }
}
