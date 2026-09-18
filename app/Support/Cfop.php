<?php

namespace App\Support;

/**
 * Descrições dos CFOPs mais comuns em compras/vendas, para exibição no
 * dashboard fiscal do portal. Tabela pública de CFOP (SEFAZ). Códigos não
 * listados exibem apenas o número.
 */
class Cfop
{
    private const DESCRICOES = [
        '1101' => 'Compra para industrialização',
        '1102' => 'Compra para comercialização',
        '1401' => 'Compra para industrialização em operação com ST',
        '1403' => 'Compra para comercialização em operação com ST',
        '1405' => 'Compra para comercialização em operação com ST (consumidor final)',
        '1551' => 'Compra de bem para o ativo imobilizado',
        '1653' => 'Compra de combustível ou lubrificante por consumidor ou usuário final',
        '1910' => 'Entrada de bonificação, doação ou brinde',
        '1911' => 'Entrada de amostra grátis',
        '1933' => 'Aquisição de serviço com ISSQN',
        '1949' => 'Outra entrada de mercadoria ou prestação de serviço não especificado',
        '2101' => 'Compra para industrialização ou produção rural',
        '2102' => 'Compra para comercialização',
        '2401' => 'Compra para industrialização em operação com ST',
        '2551' => 'Compra de bem para o ativo imobilizado',
        '2556' => 'Compra de material para uso ou consumo',
        '2932' => 'Aquisição de serviço de transporte iniciado em UF diversa daquela onde inscrito o prestador',
        '2949' => 'Outra entrada de mercadoria ou prestação de serviço não especificado',
        '5101' => 'Venda de produção do estabelecimento',
        '5102' => 'Venda de mercadoria adquirida ou recebida de terceiros',
        '5117' => 'Venda de mercadoria adquirida ou recebida de terceiros, encomenda para entrega futura',
        '5201' => 'Devolução de compra para industrialização ou produção rural',
        '5910' => 'Remessa em bonificação, doação ou brinde',
        '5949' => 'Outra saída de mercadoria ou prestação de serviço não especificado',
        '6101' => 'Venda de produção do estabelecimento',
        '6102' => 'Venda de mercadoria adquirida ou recebida de terceiros',
        '6201' => 'Devolução de compra para industrialização ou produção rural',
        '6352' => 'Prestação de serviço de transporte a estabelecimento industrial',
        '6353' => 'Prestação de serviço de transporte a estabelecimento comercial',
    ];

    public static function descricao(string $codigo): string
    {
        return self::DESCRICOES[$codigo] ?? "CFOP {$codigo}";
    }

    /**
     * @return array<string, string>
     */
    public static function todas(): array
    {
        return self::DESCRICOES;
    }
}
