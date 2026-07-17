<?php

namespace App\Services\Precificacao;

use App\Models\PrecificacaoAliquota;
use App\Models\PrecificacaoCenario;

class PrecificacaoCalculoService
{
    public function calcular(PrecificacaoCenario $cenario): PrecificacaoResultado
    {
        $produto = $cenario->produto;
        $aliquota = $produto?->aliquota();

        $valorCompraTotal = (float) $cenario->valor_compra_total;
        $quantidade = (float) $cenario->quantidade;
        $freteCompra = (float) $cenario->frete_compra;
        $ipiPct = (float) $cenario->ipi_pct;
        $markupPct = (float) $cenario->markup_pct;
        $comissaoPct = (float) $cenario->comissao_pct;
        $freteVendaPct = (float) $cenario->frete_venda_pct;

        $aplicaSt = $aliquota?->aplica_st ?? false;
        $aliquotaIcmsSt = $aliquota ? (float) $aliquota->aliquota_icms_st : 0.0;
        $aliquotaIcmsInterna = $aliquota ? (float) $aliquota->aliquota_icms_interna : 0.0;
        $icmsVendaRegra = $aliquota->icms_venda_regra ?? 'tributado';
        $regimePisCofins = $aliquota->regime_pis_cofins ?? 'tributado';
        $aliquotaPis = $aliquota ? (float) $aliquota->aliquota_pis : 0.0;
        $aliquotaCofins = $aliquota ? (float) $aliquota->aliquota_cofins : 0.0;

        // Compra
        $icmsSt = $aplicaSt ? $valorCompraTotal * $aliquotaIcmsSt / 100 : 0.0;
        $ipi = $valorCompraTotal * $ipiPct / 100;
        $creditoPis = $valorCompraTotal * $aliquotaPis / 100;
        $creditoCofins = $valorCompraTotal * $aliquotaCofins / 100;

        $custoTotal = $valorCompraTotal + $icmsSt + $ipi + $freteCompra - $creditoPis - $creditoCofins;
        $custoUnitario = $quantidade > 0 ? $custoTotal / $quantidade : 0.0;

        // Venda
        $precoVenda = $custoUnitario * (1 + $markupPct / 100);

        $icmsVenda = $icmsVendaRegra === 'st_ja_paga' ? 0.0 : $precoVenda * $aliquotaIcmsInterna / 100;

        $pisVenda = 0.0;
        $cofinsVenda = 0.0;
        if ($regimePisCofins !== 'monofasico') {
            $pisVenda = $precoVenda * $aliquotaPis / 100;
            $cofinsVenda = $precoVenda * $aliquotaCofins / 100;
        }

        $comissao = $precoVenda * $comissaoPct / 100;
        $freteVenda = $precoVenda * $freteVendaPct / 100;

        $margemContribuicaoValor = $precoVenda - $icmsVenda - $pisVenda - $cofinsVenda - $comissao - $freteVenda - $custoUnitario;
        $margemContribuicaoPercentual = $precoVenda > 0 ? $margemContribuicaoValor / $precoVenda * 100 : 0.0;

        return new PrecificacaoResultado(
            valorCompraTotal: $valorCompraTotal,
            icmsSt: $icmsSt,
            ipi: $ipi,
            freteCompra: $freteCompra,
            creditoPis: $creditoPis,
            creditoCofins: $creditoCofins,
            custoTotal: $custoTotal,
            custoUnitario: $custoUnitario,
            precoVenda: $precoVenda,
            icmsVenda: $icmsVenda,
            pisVenda: $pisVenda,
            cofinsVenda: $cofinsVenda,
            comissao: $comissao,
            freteVenda: $freteVenda,
            margemContribuicaoValor: $margemContribuicaoValor,
            margemContribuicaoPercentual: $margemContribuicaoPercentual,
            aliquotaEncontrada: $aliquota !== null,
        );
    }
}
