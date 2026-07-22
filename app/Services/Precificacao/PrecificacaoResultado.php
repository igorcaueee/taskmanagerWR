<?php

namespace App\Services\Precificacao;

class PrecificacaoResultado
{
    public function __construct(
        // Compra
        public readonly float $valorCompraTotal,
        public readonly float $icmsSt,
        public readonly float $creditoIcms,
        public readonly float $ipi,
        public readonly float $freteCompra,
        public readonly float $creditoPis,
        public readonly float $creditoCofins,
        public readonly float $custoTotal,
        public readonly float $custoUnitario,
        // Venda
        public readonly float $precoVenda,
        public readonly float $icmsVenda,
        public readonly float $pisVenda,
        public readonly float $cofinsVenda,
        public readonly float $comissao,
        public readonly float $freteVenda,
        public readonly float $margemContribuicaoValor,
        public readonly float $margemContribuicaoPercentual,
        // Aviso quando não há alíquota cadastrada para o NCM/CEST do produto
        public readonly bool $aliquotaEncontrada,
    ) {}

    /**
     * @return array<string, float|bool>
     */
    public function toArray(): array
    {
        return [
            'valor_compra_total' => $this->valorCompraTotal,
            'icms_st' => $this->icmsSt,
            'credito_icms' => $this->creditoIcms,
            'ipi' => $this->ipi,
            'frete_compra' => $this->freteCompra,
            'credito_pis' => $this->creditoPis,
            'credito_cofins' => $this->creditoCofins,
            'custo_total' => $this->custoTotal,
            'custo_unitario' => $this->custoUnitario,
            'preco_venda' => $this->precoVenda,
            'icms_venda' => $this->icmsVenda,
            'pis_venda' => $this->pisVenda,
            'cofins_venda' => $this->cofinsVenda,
            'comissao' => $this->comissao,
            'frete_venda' => $this->freteVenda,
            'margem_contribuicao_valor' => $this->margemContribuicaoValor,
            'margem_contribuicao_percentual' => $this->margemContribuicaoPercentual,
            'aliquota_encontrada' => $this->aliquotaEncontrada,
        ];
    }
}
