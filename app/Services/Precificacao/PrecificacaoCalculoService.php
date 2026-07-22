<?php

namespace App\Services\Precificacao;

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

        $aliquotaIcmsInterna = $aliquota ? (float) $aliquota->aliquota_icms_interna : 0.0;
        $regimePisCofins = $aliquota->regime_pis_cofins ?? 'tributado';
        $aliquotaPis = $aliquota ? (float) $aliquota->aliquota_pis : 0.0;
        $aliquotaCofins = $aliquota ? (float) $aliquota->aliquota_cofins : 0.0;

        // ICMS na compra é informado manualmente por cenário (ST = custo extra; Normal = crédito).
        // A venda segue a mesma escolha: se a compra teve ST, o ICMS já foi recolhido e não incide de novo na venda.
        $tipoIcmsCompra = $cenario->tipo_icms_compra ?? 'normal';
        $aliquotaIcmsCompraPct = (float) $cenario->aliquota_icms_compra_pct;
        $icmsVendaRegra = $tipoIcmsCompra === 'st' ? 'st_ja_paga' : 'tributado';

        // Compra
        $icmsSt = $tipoIcmsCompra === 'st' ? $valorCompraTotal * $aliquotaIcmsCompraPct / 100 : 0.0;
        $creditoIcms = $tipoIcmsCompra === 'normal' ? $valorCompraTotal * $aliquotaIcmsCompraPct / 100 : 0.0;
        $ipi = $valorCompraTotal * $ipiPct / 100;
        $creditoPis = $valorCompraTotal * $aliquotaPis / 100;
        $creditoCofins = $valorCompraTotal * $aliquotaCofins / 100;

        $custoTotal = $valorCompraTotal + $icmsSt + $ipi + $freteCompra - $creditoIcms - $creditoPis - $creditoCofins;
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
            creditoIcms: $creditoIcms,
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
