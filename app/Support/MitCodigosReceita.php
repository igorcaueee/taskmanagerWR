<?php

namespace App\Support;

/**
 * Catálogo de Código de Receita do MIT (Módulo de Inclusão de Tributos) —
 * 240 códigos extraídos do "Manual do MIT – janeiro/2025" (Receita Federal),
 * seção 10.1 "Tabela de Códigos de Receita". Fonte: PDF oficial baixado do
 * gov.br, extraído via pdf-parse (texto real, não OCR) e revisado pra
 * remover artefatos de hifenização de quebra de linha do PDF original.
 *
 * "grupo" já normalizado pro nome usado no payload da ENCAPURACAO314
 * (App\Services\SimplesNacional\MitService): Irpj, Csll, Irrf, Ipi, Iof,
 * PisPasep, Cofins, ContribuicoesDiversas, Cpss, RetPagamentoUnificado.
 *
 * "periodicidade": TR=trimestral, ME=mensal, AN=anual, DI=diária,
 * DC=decendial — usado pra decidir qual seletor de período mostrar na tela
 * e pra bloquear os poucos códigos DI/DC (não suportados na v1, ver
 * MitService).
 */
class MitCodigosReceita
{
    /**
     * @return array<int, array{codigo: string, grupo: string, periodicidade: string, descricao: string}>
     */
    public static function catalogo(): array
    {
        return [
        ['codigo' => '0220-01', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - PJ OBRIGADA AO LUCRO REAL - ENTIDADE NÃO FINANCEIRA - APURAÇÃO TRIMESTRAL'],
        ['codigo' => '0220-08', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - PJ OBRIGADA AO LUCRO REAL - ENTIDADE NÃO FINANCEIRA - APURAÇÃO TRIMESTRAL - SCP'],
        ['codigo' => '0220-10', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - PJ OBRIGADA AO LUCRO REAL - ENTIDADE NÃO FINANCEIRA - APURAÇAO TRIMESTRAL - IMPOSTO DE RENDA POSTERGADO DE PERIODOS DE APURAÇAO ANTERIORES'],
        ['codigo' => '0220-12', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - PJ OBRIGADA AO LUCRO REAL - ENTIDADE NÃO FINANCEIRA - APURAÇÃO TRIMESTRAL - DIFERENÇA APURADA PELO CANCELAMENTO DA HABILITAÇÃO NO ROTA 2030'],
        ['codigo' => '0231-01', 'grupo' => 'Irpj', 'periodicidade' => 'ME', 'descricao' => 'IRPJ - GANHOS LIQUIDOS EM OPERAÇOES NA BOLSA - LUCRO PRESUMIDO OU ARBITRADO'],
        ['codigo' => '0507-01', 'grupo' => 'Irpj', 'periodicidade' => 'ME', 'descricao' => 'IRPJ - GANHO DE CAPITAL - ALIENAÇÃO DE ATIVOS - ME/EPP OPTANTE PELO SIMPLES NACIONAL'],
        ['codigo' => '1599-01', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - PJ OBRIGADA AO LUCRO REAL - ENTIDADE FINANCEIRA - APURAÇÃO TRIMESTRAL'],
        ['codigo' => '1599-10', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - PJ OBRIGADA AO LUCRO REAL - ENTIDADE FINANCEIRA - APURAÇAO TRIMESTRAL - IMPOSTO DE RENDA POSTERGADO DE PERIODOS DE APURAÇAO ANTERIORES'],
        ['codigo' => '2089-01', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - LUCRO PRESUMIDO'],
        ['codigo' => '2089-02', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - LUCRO PRESUMIDO - PJ EXCLUSIVAMENTE PRESTADORA DE SERVIÇOS - DIFERENÇA DO IMPOSTO POSTERGADO APURADA NO MÊS EM QUE FOR EXCEDIDO O LIMITE DE RECEITA BRUTA'],
        ['codigo' => '2089-08', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - LUCRO PRESUMIDO - SCP'],
        ['codigo' => '2089-09', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - PJ EXCLUSIVAMENTE PRESTADORA DE SERVIÇOS - DIFERENÇA DO IMPOSTO POSTERGADO APURADA NO MÊS EM QUE FOR EXCEDIDO O LIMITE DE RECEITA BRUTA - SCP'],
        ['codigo' => '2089-10', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - LUCRO PRESUMIDO - IMPOSTO DE RENDA POSTERGADO DE PERIODOS DE APURAÇAO ANTERIORES'],
        ['codigo' => '2089-12', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - LUCRO PRESUMIDO - DIFERENÇA APURADA PELO CANCELAMENTO DA HABILITAÇÃO NO ROTA 2030'],
        ['codigo' => '2319-01', 'grupo' => 'Irpj', 'periodicidade' => 'ME', 'descricao' => 'IRPJ - PJ OBRIGADA AO LUCRO REAL - ENTIDADE FINANCEIRA - ESTIMATIVA MENSAL'],
        ['codigo' => '2362-01', 'grupo' => 'Irpj', 'periodicidade' => 'ME', 'descricao' => 'IRPJ - PJ OBRIGADA AO LUCRO REAL - ENTIDADE NÃO FINANCEIRA - ESTIMATIVA MENSAL'],
        ['codigo' => '2362-02', 'grupo' => 'Irpj', 'periodicidade' => 'ME', 'descricao' => 'IRPJ - PJ EXCLUSIVAMENTE PRESTADORA DE SERVIÇOS OBRIGADA AO LUCRO REAL - ENTIDADE NÃO FINANCEIRA - DIFERENCA DO IMPOSTO POSTERGADO APURADA NO MÊS EM QUE FOR EXCEDIDO O LIMITE DE RB'],
        ['codigo' => '2362-08', 'grupo' => 'Irpj', 'periodicidade' => 'ME', 'descricao' => 'IRPJ - PJ OBRIGADA AO LUCRO REAL - ENTIDADE NÃO FINANCEIRA - ESTIMATIVA MENSAL - SCP'],
        ['codigo' => '2362-09', 'grupo' => 'Irpj', 'periodicidade' => 'ME', 'descricao' => 'IRPJ - PJ EXCL. PRESTADORA DE SERVIÇOS OBRIGADA AO LUCRO REAL - ENTIDADE NÃO FINANCEIRA - EST. MENSAL - DIF. DO IR POSTERGADO APURADA NO MÊS EM QUE FOR EXCEDIDO O LIMITE DE RB -SCP'],
        ['codigo' => '2362-12', 'grupo' => 'Irpj', 'periodicidade' => 'ME', 'descricao' => 'IRPJ - PJ OBRIGADA AO LUCRO REAL - ENTIDADE NÃO FINANCEIRA - ESTIMATIVA MENSAL - DIFERENÇA APURADA PELO CANCELAMENTO DA HABILITAÇÃO NO ROTA 2030'],
        ['codigo' => '2390-01', 'grupo' => 'Irpj', 'periodicidade' => 'AN', 'descricao' => 'IRPJ - PJ OBRIGADA AO LUCRO REAL - ENTIDADE FINANCEIRA - SALDO DECORRENTE DO AJUSTE A SER PAGO EM QUOTA ÚNICA'],
        ['codigo' => '2390-10', 'grupo' => 'Irpj', 'periodicidade' => 'AN', 'descricao' => 'IRPJ - PJ OBRIGADA AO LUCRO REAL - ENTIDADE FINANCEIRA - IMPOSTO DE RENDA POSTERGADO DE PERIODOS DE APURAÇAO ANTERIORES'],
        ['codigo' => '2430-01', 'grupo' => 'Irpj', 'periodicidade' => 'AN', 'descricao' => 'IRPJ - PJ OBRIGADA LUCRO REAL - ENTIDADE NÃO FINANCEIRA - SALDO DECORRENTO DO AJUSTE A SER PAGO EM QUOTA ÚNICA'],
        ['codigo' => '2430-08', 'grupo' => 'Irpj', 'periodicidade' => 'AN', 'descricao' => 'IRPJ - PJ OBRIGADA AO LUCRO REAL - ENTIDADE NÃO FINANCEIRA - SALDO DECORRENTE DO AJUSTE A SER PAGO EM QUOTA ÚNICA - SCP'],
        ['codigo' => '2430-10', 'grupo' => 'Irpj', 'periodicidade' => 'AN', 'descricao' => 'IRPJ - PJ OBRIGADA AO LUCRO REAL - ENTIDADE NÃO FINANCEIRA - IMPOSTO POSTERGADO DE PERIODOS DE APURAÇAO ANTERIORES'],
        ['codigo' => '2456-01', 'grupo' => 'Irpj', 'periodicidade' => 'AN', 'descricao' => 'IRPJ - PJ OPTANTE PELO LUCRO REAL - ENTIDADE NÃO FINANCEIRA - SALDO DECORRENTE DO AJUSTE A SER PAGO EM QUOTA ÚNICA'],
        ['codigo' => '2456-08', 'grupo' => 'Irpj', 'periodicidade' => 'AN', 'descricao' => 'IRPJ - PJ OPTANTE PELO LUCRO REAL - ENTIDADE NÃO FINANCEIRA - SALDO DECORRENTE DO AJUSTE A SER PAGO EM QUOTA ÚNICA - SCP'],
        ['codigo' => '2456-10', 'grupo' => 'Irpj', 'periodicidade' => 'AN', 'descricao' => 'IRPJ - PJ OPTANTE PELO LUCRO REAL - ENTIDADE NÃO FINANCEIRA - IMPOSTO POSTERGADO DE PERÍODOS DE APURAÇÃO ANTERIORESDE PERIODOS DE APURAÇAO ANTERIORES'],
        ['codigo' => '3225-01', 'grupo' => 'Irpj', 'periodicidade' => 'ME', 'descricao' => 'IRPJ - GANHOS LÍQUIDOS EM OPERAÇÕES NA BOLSA - SIMPLES NACIONAL'],
        ['codigo' => '3317-01', 'grupo' => 'Irpj', 'periodicidade' => 'ME', 'descricao' => 'IRPJ - GANHOS LÍQUIDOS EM OPERAÇÕES NA BOLSA - LUCRO REAL'],
        ['codigo' => '3373-01', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - PJ OPTANTE PELO LUCRO REAL - ENTIDADE NÃO FINANCEIRA - APURAÇÃO TRIMESTRAL'],
        ['codigo' => '3373-08', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - PJ OPTANTE PELO LUCRO REAL - ENTIDADE NÃO FINANCEIRA - APURAÇÃO TRIMESTRAL - SCP'],
        ['codigo' => '3373-10', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - PJ OPTANTE PELO LUCRO REAL - APURAÇAO TRIMESTRAL - IMPOSTO POSTERGADO DE PERIODOS DE APURAÇAO ANTERIROES'],
        ['codigo' => '3373-12', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - PJ OPTANTE PELO LUCRO REAL - APURAÇÃO TRIMESTRAL - DIFERENÇA APURADA PELO CANCELAMENTO DA HABILITAÇÃO NO ROTA 2030'],
        ['codigo' => '5625-01', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - LUCRO ARBITRADO'],
        ['codigo' => '5625-02', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - LUCRO ARBITRADO - PJ EXCLUSIVAMENTE PRESTADORAS DE SERVICOS - DIFERENCA DO IMPOSTO POSTERGADO APURADA NO MÊS EM QUE FOR EXCEDIDO O LIMITE DE RECEITA BRUTA'],
        ['codigo' => '5625-08', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - LUCRO ARBITRADO - SCP'],
        ['codigo' => '5625-09', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - LUCRO ARBITRADO - PJ EXCLUSIVAMENTE PRESTADORA DE SERVIÇOS - DIFERENÇA DO IMPOSTO POSTERGADO APURADA NO MÊS EM QUE FOR EXCEDIDO O LIMITE DE RB - SCP'],
        ['codigo' => '5625-10', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - LUCRO ARBITRADO - PJ EXCLUSIVAMENTE PRESTADORA DE SERVIÇOS - IMPOSTO DE RENDA POSTERGADO DE PERIODOS DE APURAÇAO ANTERIORES'],
        ['codigo' => '5625-12', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - LUCRO ARBITRADO - DIFERENÇA APURADA PELO CANCELAMENTO DA HABILITAÇÃO NO ROTA 2030'],
        ['codigo' => '5993-01', 'grupo' => 'Irpj', 'periodicidade' => 'ME', 'descricao' => 'IRPJ - PJ OPTANTE PELO LUCRO REAL - ENTIDADE NÃO FINANCEIRA - ESTIMATIVA MENSAL'],
        ['codigo' => '5993-02', 'grupo' => 'Irpj', 'periodicidade' => 'ME', 'descricao' => 'IRPJ - PJ EXCL. PRESTADORA DE SERV. OPTANTE PELO LUCRO REAL - ENT. NÃO FINANCEIRA - ESTIMATIVA MENSAL - DIF. DO IMP. POSTERGADO APURADA NO MÊS EM QUE FOR EXCEDIDO O LIMITE DE RB'],
        ['codigo' => '5993-08', 'grupo' => 'Irpj', 'periodicidade' => 'ME', 'descricao' => 'IRPJ - PJ OPTANTE PELO LUCRO REAL - ENTIDADE NÃO FINANACEIRA - ESTIMATIVA MENSAL - SCP'],
        ['codigo' => '5993-09', 'grupo' => 'Irpj', 'periodicidade' => 'ME', 'descricao' => 'IRPJ - PJ EXCL. PRESTADORA DE SERVIÇOS OPTANTE PELO L. REAL - ENT. NÃO FINANCEIRA - ESTIMATIVA MENSAL - DIF. DO IMP. POSTERGADO APURADA NO MÊS EM QUE FOR EXC. O LIMITE DE RB - SCP'],
        ['codigo' => '5993-12', 'grupo' => 'Irpj', 'periodicidade' => 'ME', 'descricao' => 'IRPJ - PJ OPTANTE PELO LUCRO REAL - ENTIDADE NÃO FINANCEIRA - ESTIMATIVA MENSAL - DIFERENÇA APURADA PELO CANCELAMENTO DA HABILITAÇÃO NO ROTA 2030'],
        ['codigo' => '7756-01', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - LUCRO REAL - APURAÇÃO TRIMESTRAL - TRANS NAVIOS'],
        ['codigo' => '7756-02', 'grupo' => 'Irpj', 'periodicidade' => 'ME', 'descricao' => 'IRPJ - LUCRO REAL - APURAÇÃO ANUAL - ESTIMATIVA - TRANS NAVIOS'],
        ['codigo' => '7756-03', 'grupo' => 'Irpj', 'periodicidade' => 'AN', 'descricao' => 'IRPJ - LUCRO REAL - AJUSTE ANUAL - TRANS NAVIOS'],
        ['codigo' => '7756-04', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - LUCRO PRESUMIDO - TRANS NAVIOS'],
        ['codigo' => '7756-05', 'grupo' => 'Irpj', 'periodicidade' => 'TR', 'descricao' => 'IRPJ - LUCRO ARBITRADO - TRANS NAVIOS'],
        ['codigo' => '9086-01', 'grupo' => 'Irpj', 'periodicidade' => 'ME', 'descricao' => 'IRPJ - GANHOS LÍQUIDOS OPERAÇÕES BOLSA INVESTIMENTOS DE PAÍS C/ TRIBUTAÇÃO FAVORECIDA'],
        ['codigo' => '2030-01', 'grupo' => 'Csll', 'periodicidade' => 'TR', 'descricao' => 'CSLL DAS ENTIDADES FINANCEIRAS QUE APURAM O IRPJ COM BASE NO LUCRO REAL TRIMESTRAL'],
        ['codigo' => '2030-10', 'grupo' => 'Csll', 'periodicidade' => 'TR', 'descricao' => 'CSLL - PJ QUE APURA O IRPJ COM BASE NO LUCRO REAL - ENTIDADE FINANCEIRA - APURAÇAO TRIMESTRAL - POSTERGADA DE PERIODOS DE APURAÇAO ANTERIORES'],
        ['codigo' => '2372-01', 'grupo' => 'Csll', 'periodicidade' => 'TR', 'descricao' => 'CSLL - LUCRO PRESUMIDO OU ARBITRADO - ENTIDADE NÃO FINANCEIRA'],
        ['codigo' => '2372-03', 'grupo' => 'Csll', 'periodicidade' => 'TR', 'descricao' => 'CSLL - LUCRO ARBITRADO - ENTIDADE FINANCEIRA'],
        ['codigo' => '2372-08', 'grupo' => 'Csll', 'periodicidade' => 'TR', 'descricao' => 'CSLL - LUCRO PRESUMIDO OU ARBITRADO - SCP'],
        ['codigo' => '2372-10', 'grupo' => 'Csll', 'periodicidade' => 'TR', 'descricao' => 'CSLL - LUCRO PRESUMIDO OU ARBITRADO - POSTERGADA DE PERIODOS DE APURAÇAO ANTERIORES'],
        ['codigo' => '2372-12', 'grupo' => 'Csll', 'periodicidade' => 'TR', 'descricao' => 'CSLL - LUCRO PRESUMIDO OU ARBITRADO - ENTIDADE NÃO FINANCEIRA - DIFERENÇA APURADA EM DECORÊNCIA DO CANCELAMENTO DA HABILITAÇÃO NO PROGRAMA ROTA 2030'],
        ['codigo' => '2469-01', 'grupo' => 'Csll', 'periodicidade' => 'ME', 'descricao' => 'CSLL DAS ENTIDADES FINANCEIRAS QUE APURAM O IRPJ COM BASE EM ESTIMATIVA MENSAL'],
        ['codigo' => '2484-01', 'grupo' => 'Csll', 'periodicidade' => 'ME', 'descricao' => 'CSLL - LUCRO REAL - ENTIDADE NÃO FINANCEIRA - ESTIMATIVA MENSAL'],
        ['codigo' => '2484-08', 'grupo' => 'Csll', 'periodicidade' => 'ME', 'descricao' => 'CSLL - LUCRO REAL - ENTIDADE NÃO FINANCEIRA - ESTIMATIVA MENSAL - SCP'],
        ['codigo' => '2484-12', 'grupo' => 'Csll', 'periodicidade' => 'ME', 'descricao' => 'CSLL - LUCRO REAL - ENTIDADE NÃO FINANCEIRA - ESTIMATIVA MENSAL - DIFERENÇA APURADA EM DECORRÊNCIA DO CANCELAMENTO DA HABILITAÇÃO NO PROGRAMA ROTA 2030'],
        ['codigo' => '6012-01', 'grupo' => 'Csll', 'periodicidade' => 'TR', 'descricao' => 'CSLL - LUCRO REAL - ENTIDADE NÃO FINANCEIRA - APURAÇÃO TRIMESTRAL'],
        ['codigo' => '6012-08', 'grupo' => 'Csll', 'periodicidade' => 'TR', 'descricao' => 'CSLL - LUCRO REAL - ENTIDADE NÃO FINANCEIRA - APURAÇÃO TRIMESTRAL - SCP'],
        ['codigo' => '6012-10', 'grupo' => 'Csll', 'periodicidade' => 'TR', 'descricao' => 'CSLL - LUCRO REAL - ENTIDADE NÃO FINANCEIRA - APURAÇAO TRIMESTRAL - POSTERGADA DE PERIODOS DE APURAÇAO ANTERIORES'],
        ['codigo' => '6012-12', 'grupo' => 'Csll', 'periodicidade' => 'TR', 'descricao' => 'CSLL - LUCRO REAL - ENTIDADE NÃO FINANCEIRA - APURAÇÃO TRIMESTRAL - DIFERENÇA APURADA EM DECORRÊNCIA DO CANCELAMENTO DA HABILITAÇÃO NO PROGRAMA ROTA 2030'],
        ['codigo' => '6758-01', 'grupo' => 'Csll', 'periodicidade' => 'AN', 'descricao' => 'CSLL - ENTIDADES FINANCEIRAS/DECLARACAO DE AJUSTE'],
        ['codigo' => '6758-10', 'grupo' => 'Csll', 'periodicidade' => 'AN', 'descricao' => 'CSLL - PJ QUE APURA O IRPJ COM BASE NO LUCRO REAL - ENTIDADE FINANCEIRA - POSTERGADA DE PERIODOS DE APURAÇAO ANTERIORES'],
        ['codigo' => '6773-01', 'grupo' => 'Csll', 'periodicidade' => 'AN', 'descricao' => 'CSLL DEMAIS DECLARACAO DE AJUSTE'],
        ['codigo' => '6773-08', 'grupo' => 'Csll', 'periodicidade' => 'AN', 'descricao' => 'CSLL - DEMAIS PJ QUE APURAM O IRPJ COM BASE EM ESTIMATIVA MENSAL/AJUSTE ANUAL/SCP'],
        ['codigo' => '6773-10', 'grupo' => 'Csll', 'periodicidade' => 'AN', 'descricao' => 'CSLL - PJ QUE APURA O IRPJ COM BASE NO LUCRO REAL - DEMAIS ENTIDADES - POSTERGADA DE PERIODOS DE APURAÇAO ANTERIORES'],
        ['codigo' => '7837-01', 'grupo' => 'Csll', 'periodicidade' => 'TR', 'descricao' => 'CSLL - LUCRO REAL - APURAÇÃO TRIMESTRAL - TRANS NAVIOS'],
        ['codigo' => '7837-02', 'grupo' => 'Csll', 'periodicidade' => 'ME', 'descricao' => 'CSLL - LUCRO REAL - APURAÇÃO ANUAL - ESTIMATIVA - TRANS NAVIOS'],
        ['codigo' => '7837-03', 'grupo' => 'Csll', 'periodicidade' => 'AN', 'descricao' => 'CSLL - LUCRO REAL - AJUSTE ANUAL - TRANS NAVIOS'],
        ['codigo' => '7837-04', 'grupo' => 'Csll', 'periodicidade' => 'TR', 'descricao' => 'CSLL - LUCRO PRESUMIDO OU ARBITRADO - TRANS NAVIOS'],
        ['codigo' => '7769-01', 'grupo' => 'Irrf', 'periodicidade' => 'ME', 'descricao' => 'IRRF - TRABALHO ASSALARIADO - TRANS NAVIOS'],
        ['codigo' => '7769-02', 'grupo' => 'Irrf', 'periodicidade' => 'ME', 'descricao' => 'IRRF - TRABALHO SEM VÍNCULO EMPREGATÍCIO - TRANS NAVIOS'],
        ['codigo' => '7769-03', 'grupo' => 'Irrf', 'periodicidade' => 'ME', 'descricao' => 'IRRF - REMUNERAÇÃO DE SERVIÇOS PRESTADOS POR PJ - TRANS NAVIOS'],
        ['codigo' => '0668-03', 'grupo' => 'Ipi', 'periodicidade' => 'ME', 'descricao' => 'IPI - BEBIDAS (CAPITULO 22 DA TIPI)'],
        ['codigo' => '0676-02', 'grupo' => 'Ipi', 'periodicidade' => 'ME', 'descricao' => 'IPI - VEICULOS DAS POSIÇOES 87.03 E 87.06 DA TIPI'],
        ['codigo' => '0676-16', 'grupo' => 'Ipi', 'periodicidade' => 'ME', 'descricao' => 'IPI - VEICULOS DAS POSIÇOES 87.03 E 87.06 DA TIPI - PERDA DE ISENÇÃO, SUSPENSÃO, REDUÇÃO DE ALÍQUOTAS OU NÃOINCIDÊNCIA POR NÃO CUMPRIMENTO DAS CONDIÇÕES EXIGIDAS PARA O BENEFÍCIO'],
        ['codigo' => '0821-02', 'grupo' => 'Ipi', 'periodicidade' => 'ME', 'descricao' => 'IPI - TRIBUTAÇÃO DE BEBIDAS FRIAS - CERVEJAS'],
        ['codigo' => '0838-02', 'grupo' => 'Ipi', 'periodicidade' => 'ME', 'descricao' => 'IPI - TRIBUTAÇÃO DE BEBIDAS FRIAS - DEMAIS BEBIDAS'],
        ['codigo' => '1020-05', 'grupo' => 'Ipi', 'periodicidade' => 'ME', 'descricao' => 'IPI - CIGARROS DO CÓDIGO 2402.20.00 DA TIPI'],
        ['codigo' => '1020-06', 'grupo' => 'Ipi', 'periodicidade' => 'ME', 'descricao' => 'IPI - REGIME ESPECIAL DE TRIBUTAÇÃO - CIGARROS DO CÓDIGO 2402.20.00 DA TIPI (ART. 17, LEI 12.546/2011)'],
        ['codigo' => '1097-05', 'grupo' => 'Ipi', 'periodicidade' => 'ME', 'descricao' => 'IPI - MÁQUINAS, APARELHOS E MATERIAL DE TRANSPORTE'],
        ['codigo' => '1097-16', 'grupo' => 'Ipi', 'periodicidade' => 'ME', 'descricao' => 'IPI - MÁQUINAS, APARELHOS E MATERIAL DE TRANSPORTE - PERDA DE ISENÇÃO, SUSPENSÃO, REDUÇÃO DE ALÍQUOTAS OU NÃO-INCIDÊNCIA POR NÃO CUMPRIMENTO DAS CONDIÇÕES EXIGIDAS PARA O BENEFÍCIO'],
        ['codigo' => '2401-02', 'grupo' => 'Ipi', 'periodicidade' => 'ME', 'descricao' => 'IPI - BEBIDAS DO CAPÍTULO 22 DA TIPI - OPERAÇÕES INTRA-ORÇAMENTÁRIAS'],
        ['codigo' => '2410-03', 'grupo' => 'Ipi', 'periodicidade' => 'ME', 'descricao' => 'IPI - MÁQUINAS, APARELHOS E MATERIAL DE TRANSPORTE - OPERAÇÕES INTRA-ORÇAMENTÁRIAS'],
        ['codigo' => '2410-04', 'grupo' => 'Ipi', 'periodicidade' => 'ME', 'descricao' => 'IPI - DEMAIS PRODUTOS - OPERAÇÕES INTRA - ORÇAMENTÁRIAS'],
        ['codigo' => '5110-01', 'grupo' => 'Ipi', 'periodicidade' => 'ME', 'descricao' => 'IPI - TABACO E SEUS SUCEDÂNEOS MANUFATURADOS, EXCETO CIGARROS CONTENDO TABACO'],
        ['codigo' => '5123-01', 'grupo' => 'Ipi', 'periodicidade' => 'ME', 'descricao' => 'IPI - DEMAIS PRODUTOS'],
        ['codigo' => '5123-16', 'grupo' => 'Ipi', 'periodicidade' => 'ME', 'descricao' => 'IPI - DEMAIS PRODUTOS - PERDA DE ISENÇÃO, SUSPENSÃO, REDUÇÃO DE ALÍQUOTAS OU NÃO-INCIDÊNCIA POR NÃO CUMPRIMENTO DAS CONDIÇÕES EXIGIDAS PARA O BENEFÍCIO'],
        ['codigo' => '1150-02', 'grupo' => 'Iof', 'periodicidade' => 'ME', 'descricao' => 'IOF - OPERAÇÕES DE MÚTUO - TOMADOR PESSOA JURÍDICA - ART 13 LEI 9779/99 - APURAÇÃO DA BASE DE CÁLCULO NO ÚLTIMO DIA DO MÊS'],
        ['codigo' => '1150-03', 'grupo' => 'Iof', 'periodicidade' => 'DC', 'descricao' => 'IOF - OPERAÇÕES DE CRÉDITO - TOMADOR PESSOA JURÍDICA'],
        ['codigo' => '1150-04', 'grupo' => 'Iof', 'periodicidade' => 'DC', 'descricao' => 'IOF - OPERAÇÕES DE MÚTUO - TOMADOR PESSOA JURÍDICA - ART. 13 LEI 9779/1999'],
        ['codigo' => '1150-05', 'grupo' => 'Iof', 'periodicidade' => 'ME', 'descricao' => 'IOF - OPERAÇÕES DE CRÉDITO - TOMADOR PESSOA JURÍDICA - APURAÇÃO DA BASE DE CÁLCULO NO ÚLTIMO DIA DO MÊS'],
        ['codigo' => '2927-02', 'grupo' => 'Iof', 'periodicidade' => 'ME', 'descricao' => 'IOF - CONTRATO DE DERIVATIVOS'],
        ['codigo' => '2927-10', 'grupo' => 'Iof', 'periodicidade' => 'ME', 'descricao' => 'IOF - CONTRATO DE DERIVATIVOS - PERDA DE ISENÇÃO, SUSPENSÃO, REDUÇÃO DE ALÍQUOTAS OU NÃO-INCIDÊNCIA POR NÃO CUMPRIMENTO DAS CONDIÇÕES EXIGIDAS PARA O BENEFÍCIO'],
        ['codigo' => '3467-02', 'grupo' => 'Iof', 'periodicidade' => 'DC', 'descricao' => 'IOF - OPERAÇÕES DE SEGURO'],
        ['codigo' => '4028-02', 'grupo' => 'Iof', 'periodicidade' => 'DC', 'descricao' => 'IOF - OPERAÇÕES COM OURO - ATIVO FINANCEIRO'],
        ['codigo' => '4290-02', 'grupo' => 'Iof', 'periodicidade' => 'DC', 'descricao' => 'IOF - OPERAÇÕES DE CÂMBIO - ENTRADA DE MOEDA'],
        ['codigo' => '5220-02', 'grupo' => 'Iof', 'periodicidade' => 'DC', 'descricao' => 'IOF - OPERAÇÕES DE CÂMBIO - SAÍDA DE MOEDA'],
        ['codigo' => '6854-02', 'grupo' => 'Iof', 'periodicidade' => 'DC', 'descricao' => 'IOF - APLICAÇÕES FINANCEIRAS (PORT. MF 341-A/97)'],
        ['codigo' => '6895-02', 'grupo' => 'Iof', 'periodicidade' => 'DC', 'descricao' => 'IOF - FACTORING (ART. 58, LEI Nº 9.532/97)'],
        ['codigo' => '7893-02', 'grupo' => 'Iof', 'periodicidade' => 'ME', 'descricao' => 'IOF - OPERACÕES DE MÚTUO - TOMADOR PESSOA FÍSICA - ART 13 LEI 9779/99 - APURAÇÃO DA BASE DE CÁLCULO NO ÚLTIMO DIA DO MÊS'],
        ['codigo' => '7893-03', 'grupo' => 'Iof', 'periodicidade' => 'DC', 'descricao' => 'IOF - OPERAÇÕES DE CRÉDITO - TOMADOR PESSOA FÍSICA'],
        ['codigo' => '7893-04', 'grupo' => 'Iof', 'periodicidade' => 'DC', 'descricao' => 'IOF - OPERACÕES DE MÚTUO - TOMADOR PESSOA FÍSICA - ART 13 LEI 9779/99'],
        ['codigo' => '7893-05', 'grupo' => 'Iof', 'periodicidade' => 'ME', 'descricao' => 'IOF - OPERAÇÕES DE CRÉDITO - TOMADOR PESSOA FÍSICA - APURAÇÃO DA BASE DE CÁLCULO NO ÚLTIMO DIA DO MÊS'],
        ['codigo' => '0679-03', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS - TRIBUTAÇÃO DE BEBIDAS FRIAS - CERVEJAS'],
        ['codigo' => '0679-04', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS - TRIBUTAÇÃO DE BEBIDAS FRIAS - CERVEJAS - SCP'],
        ['codigo' => '0691-03', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS - TRIBUTAÇÃO DE BEBIDAS FRIAS - DEMAIS BEBIDAS'],
        ['codigo' => '0691-04', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS - TRIBUTAÇÃO DE BEBIDAS FRIAS - DEMAIS BEBIDAS - SCP'],
        ['codigo' => '0906-01', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS - REGIME ESPECIAL DE APURAÇAO E PAGAMENTO - ALCOOL'],
        ['codigo' => '0906-02', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS - REGIME ESPECIAL DE APURAÇÃO E PAGAMENTO (RECOB) - ÁLCOOL - SCP'],
        ['codigo' => '1921-01', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS - VENDAS A ZFM/SUBSTITUIÇÃO TRIBUTÁRIA (ART. 64, LEI N. 11.196/2005)'],
        ['codigo' => '1921-02', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS - VENDAS A ZFM/SUBSTITUIÇÃO TRIBUTÁRIA (ART. 65, LEI N. 11.196/2005)'],
        ['codigo' => '1921-03', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS/PASEP - SUBSTITUIÇÃO TRIBUTÁRIA NA REVENDA DE PRODUTOS SUJEITOS A ALÍQUOTAS DIFERENCIADAS (ART. 64 DA LEI Nº 11.196/2005)/SCP'],
        ['codigo' => '1921-04', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS/PASEP - SUBSTITUIÇÃO TRIBUTÁRIA NA REVENDA DE PRODUTOS SUJEITOS A ALÍQUOTAS DIFERENCIADAS (ART. 65 DA LEI Nº 11.196/2005)/SCP'],
        ['codigo' => '3121-02', 'grupo' => 'PisPasep', 'periodicidade' => 'DI', 'descricao' => 'PIS-OPERAÇÕES INTRA-ORÇAMENTÁRIAS'],
        ['codigo' => '3703-01', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS/PASEP - PESSOA JURIDICA DE DIREITO PUBLICO'],
        ['codigo' => '4574-01', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS - ENTIDADES FINANCEIRAS EQUIPARADAS'],
        ['codigo' => '5434-01', 'grupo' => 'PisPasep', 'periodicidade' => 'DI', 'descricao' => 'PIS/PASEP - IMPORTAÇÃO DE SERVIÇOS'],
        ['codigo' => '5434-08', 'grupo' => 'PisPasep', 'periodicidade' => 'DI', 'descricao' => 'PIS/PASEP - IMPORTAÇÃO DE SERVIÇOS/SCP'],
        ['codigo' => '5434-10', 'grupo' => 'PisPasep', 'periodicidade' => 'DI', 'descricao' => 'PIS/PASEP - IMPORTAÇAO DE SERVIÇOS - PERDA DE ISENÇÃO, SUSPENSÃO, REDUÇÃO DE ALÍQUOTAS OU NÃO-INCIDÊNCIA POR NÃO CUMPRIMENTO DAS CONDIÇÕES EXIGIDAS PARA O BENEFÍCIO'],
        ['codigo' => '5434-11', 'grupo' => 'PisPasep', 'periodicidade' => 'DI', 'descricao' => 'PIS/PASEP - IMPORTAÇAO DE SERVIÇOS - PERDA DE ISENÇÃO, SUSPENSAO, REDUÇÃO DE ALÍQUOTAS OU NÃO-INCIDÊNCIA POR NÃO CUMPRIMENTO DAS CONDIÇÕES EXIGIDAS PARA O BENEFÍCIO - SCP'],
        ['codigo' => '6824-01', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS - COMBUSTÍVEIS'],
        ['codigo' => '6824-08', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS/PASEP - COMBUSTÍVEIS/SCP'],
        ['codigo' => '6912-01', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS/PASEP - NÃO CUMULATIVO - LEI 10.637/2002'],
        ['codigo' => '6912-08', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS/PASEP - NÃO CUMULATIVO/SCP'],
        ['codigo' => '6912-16', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS/PASEP - NAO CUMULATIVO - PERDA DE ISENÇÃO, SUSPENSAO, REDUÇÃO DE ALÍQUOTAS OU NÃO-INCIDÊNCIA POR NÃO CUMPRIMENTO DAS CONDIÇÕES EXIGIDAS PARA O BENEFÍCIO'],
        ['codigo' => '6912-17', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS/PASEP - NAO CUMULATIVO - PERDA DE ISENÇÃO, SUSPENSAO, REDUÇÃO DE ALÍQUOTAS OU NÃO-INCIDÊNCIA POR NÃO CUMPRIMENTO DAS CONDIÇÕES EXIGIDAS PARA O BENEFÍCIO - SCP'],
        ['codigo' => '7797-01', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS - CUMULATIVO - TRANS NAVIOS'],
        ['codigo' => '7797-02', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS - NÃO CUMULATIVO - TRANS NAVIOS'],
        ['codigo' => '8109-02', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS - FATURAMENTO - PJ EM GERAL'],
        ['codigo' => '8109-03', 'grupo' => 'PisPasep', 'periodicidade' => 'DI', 'descricao' => 'PIS - FATURAMENTO - EMPRESA COMERCIAL EXPORTADORA - PRODUTOS NÃO EXPORTADOS'],
        ['codigo' => '8109-07', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS/PASEP - SUBSTITUIÇÃO TRIBUTÁRIA NA COMERCIALIZAÇÃO DE CIGARROS'],
        ['codigo' => '8109-08', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS/PASEP - FATURAMENTO - PJ EM GERAL - SCP'],
        ['codigo' => '8109-09', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS/PASEP - SUBSTITUIÇÃO TRIBUTÁRIA NA COMERCIALIZAÇÃO DE CIGARROS - SCP'],
        ['codigo' => '8109-16', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS/PASEP - FATURAMENTO - PJ EM GERAL - PERDA DE ISENÇÃO, SUSPENSAO, REDUÇÃO DE ALÍQUOTAS OU NÃO-INCIDÊNCIA POR NÃO CUMPRIMENTO DAS CONDIÇÕES EXIGIDAS PARA O BENEFÍCIO'],
        ['codigo' => '8109-17', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS/PASEP - FATURAMENTO - PJ EM GERAL - PERDA DE ISENÇÃO, SUSPENSAO, REDUÇÃO DE ALÍQUOTAS OU NÃO-INCIDÊNCIA POR NÃO CUMPRIMENTO DAS CONDIÇÕES EXIGIDAS PARA O BENEFÍCIO - SCP'],
        ['codigo' => '8496-01', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS - FABRICACAO E IMPORTACAO DE VEICULOS EM SUBSTITUICAO TRIBUTARIA'],
        ['codigo' => '8496-08', 'grupo' => 'PisPasep', 'periodicidade' => 'ME', 'descricao' => 'PIS/PASEP - SUBSTITUIÇÃO NA FABRICAÇÃO E IMPORTAÇÃO DE VEÍCULOS/SCP'],
        ['codigo' => '0760-03', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - TRIBUTAÇÃO DE BEBIDAS FRIAS - CERVEJAS'],
        ['codigo' => '0760-04', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - TRIBUTAÇÃO DE BEBIDAS FRIAS - CERVEJAS - SCP'],
        ['codigo' => '0776-03', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - TRIBUTAÇÃO DE BEBIDAS FRIAS - DEMAIS BEBIDAS'],
        ['codigo' => '0776-04', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - TRIBUTAÇÃO DE BEBIDAS FRIAS - DEMAIS BEBIDAS - SCP'],
        ['codigo' => '0929-01', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - REGIME ESPECIAL DE APURAÇAO E PAGAMENTO - ALCOOL'],
        ['codigo' => '0929-02', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - REGIME ESPECIAL DE APURAÇÃO E PAGAMENTO (RECOB) - ÁLCOOL - SCP'],
        ['codigo' => '1840-01', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - VENDAS A ZFM/SUBSTITUIÇÃO TRIBUTÁRIA (ART. 64, LEI N. 11.196/2005)'],
        ['codigo' => '1840-02', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - VENDAS A ZFM/SUBSTITUIÇÃO TRIBUTÁRIA (ART. 65, LEI N. 11.196/2005)'],
        ['codigo' => '1840-03', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - SUBSTITUIÇÃO TRIBUTÁRIA NA REVENDA DE PRODUTOS SUJEITOS A ALÍQUOTAS DIFERENCIADAS (ART. 64 DA LEI Nº 11.196/2005)/SCP'],
        ['codigo' => '1840-04', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - SUBSTITUIÇÃO TRIBUTÁRIA NA REVENDA DE PRODUTOS SUJEITOS A ALÍQUOTAS DIFERENCIADAS (ART. 65 DA LEI Nº 11.196/2005)/SCP'],
        ['codigo' => '2172-01', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - FATURAMENTO/PJ EM GERAL'],
        ['codigo' => '2172-02', 'grupo' => 'Cofins', 'periodicidade' => 'DI', 'descricao' => 'COFINS - FATURAMENTO - EMPRESA COMERCIAL EXPORTADORA - PRODUTOS NÃO EXPORTADOS'],
        ['codigo' => '2172-04', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - SUBSTITUIÇÃO TRIBUTÁRIA NA COMERCIALIZAÇÃO DE CIGARROS'],
        ['codigo' => '2172-08', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - FATURAMENTO/PJ EM GERAL/SCP'],
        ['codigo' => '2172-09', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - SUBSTITUIÇÃO TRIBUTÁRIA NA COMERCIALIZAÇÃO DE CIGARROS/SCP'],
        ['codigo' => '2172-16', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - FATURAMENTO - PJ EM GERAL - PERDA DE ISENÇÃO, SUSPENSAO, REDUÇÃO DE ALÍQUOTAS OU NÃO-INCIDÊNCIA POR NÃO CUMPRIMENTO DAS CONDIÇÕES EXIGIDAS PARA O BENEFÍCIO'],
        ['codigo' => '2172-17', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - FATURAMENTO - PJ EM GERAL - PERDA DE ISENÇÃO, SUSPENSAO, REDUÇÃO DE ALÍQUOTAS OU NÃO-INCIDÊNCIA POR NÃO CUMPRIMENTO DAS CONDIÇÕES EXIGIDAS PARA O BENEFÍCIO-SCP'],
        ['codigo' => '5442-01', 'grupo' => 'Cofins', 'periodicidade' => 'DI', 'descricao' => 'COFINS - IMPORTAÇÃO DE SERVIÇOS'],
        ['codigo' => '5442-08', 'grupo' => 'Cofins', 'periodicidade' => 'DI', 'descricao' => 'COFINS - IMPORTAÇÃO DE SERVIÇOS/SCP'],
        ['codigo' => '5442-10', 'grupo' => 'Cofins', 'periodicidade' => 'DI', 'descricao' => 'COFINS - IMPORTAÇAO DE SERVIÇOS - PERDA DE ISEÇÃO, SUSPENSAO, REDUÇÃO DE ALÍQUOTAS OU NÃO-INCIDÊNCIA POR NÃO CUMPRIMENTO DAS CONDIÇÕES EXIGIDAS PARA O BENEFÍCIO'],
        ['codigo' => '5442-11', 'grupo' => 'Cofins', 'periodicidade' => 'DI', 'descricao' => 'COFINS - IMPORTAÇAO DE SERVIÇOS - PERDA DE ISENÇÃO, SUSPENSAO, REDUÇÃO DE ALÍQUOTAS OU NÃO-INCIDÊNCIA POR NÃO CUMPRIMENTO DAS CONDIÇÕES EXIGIDAS PARA O BENEFÍCIO-SCP'],
        ['codigo' => '5856-01', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - NÃO CUMULATIVA'],
        ['codigo' => '5856-08', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - NÃO CUMULATIVO/SCP'],
        ['codigo' => '5856-16', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - NAO CUMULATIVA - PERDA DE ISENÇÃO, SUSPENSAO, REDUÇÃO DE ALÍQUOTAS OU NÃO-INCIDÊNCIA POR NÃO CUMPRIMENTO DAS CONDIÇÕES EXIGIDAS PARA O BENEFÍCIO'],
        ['codigo' => '5856-17', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - NAO CUMULATIVA - PERDA DE ISENÇÃO, SUSPENSAO, REDUÇÃO DE ALÍQUOTAS OU NÃO-INCIDÊNCIA POR NÃO CUMPRIMENTO DAS CONDIÇÕES EXIGIDAS PARA O BENEFÍCIO - SCP'],
        ['codigo' => '6840-01', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - COMBUSTÍVEIS'],
        ['codigo' => '6840-08', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - COMBUSTÍVEIS/SCP'],
        ['codigo' => '7784-01', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - CUMULATIVA - TRANS NAVIOS'],
        ['codigo' => '7784-02', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - NÃO CUMULATIVA - TRANS NAVIOS'],
        ['codigo' => '7987-01', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - ENTIDADES FINANCEIRAS'],
        ['codigo' => '8645-01', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - FABRICACAO E IMPORTACAO DE VEICULOS EM SUBSTITUICAO TRIBUTARIA'],
        ['codigo' => '8645-08', 'grupo' => 'Cofins', 'periodicidade' => 'ME', 'descricao' => 'COFINS - SUBSTITUIÇÃO NA FABRICAÇÃO E IMPORTAÇÃO DE VEÍCULOS/SCP'],
        ['codigo' => '8536-02', 'grupo' => 'ContribuicoesDiversas', 'periodicidade' => 'DC', 'descricao' => 'CPMF - ACAO JUDICIAL (ARTS 45 E 46 DA MP 2.037-21/00)'],
        ['codigo' => '8741-01', 'grupo' => 'ContribuicoesDiversas', 'periodicidade' => 'ME', 'descricao' => 'CIDE - REMESSAS AO EXTERIOR - LEI Nº 10.332/2001'],
        ['codigo' => '8741-11', 'grupo' => 'ContribuicoesDiversas', 'periodicidade' => 'ME', 'descricao' => 'CIDE - REMESSAS AO EXTERIOR - LEI Nº 10.332/2001 - PERDA DE ISENÇÃO, SUSPENSÃO, REDUÇÃO DE ALÍQUOTAS OU NÃOINCIDÊNCIA POR NÃO CUMPRIMENTO DAS CONDIÇÕES EXIGIDAS PARA O BENEFÍCIO'],
        ['codigo' => '9013-01', 'grupo' => 'ContribuicoesDiversas', 'periodicidade' => 'DI', 'descricao' => 'CONDECINE - PGTO/CREDITO/REMESSA P/ EXTERIOR - MP 2.228-1/2001, ART.33, § 2º'],
        ['codigo' => '9331-01', 'grupo' => 'ContribuicoesDiversas', 'periodicidade' => 'ME', 'descricao' => 'CIDE - COMBUSTÍVEIS - MERCADO INTERNO - LEI 10.336/2001, ART. 5º, INC.I - GASOLINA'],
        ['codigo' => '9331-03', 'grupo' => 'ContribuicoesDiversas', 'periodicidade' => 'ME', 'descricao' => 'CIDE-COMBUSTÍVEIS-MERCADO INTERNO-LEI 10.336/2001,ART.10,§ 4º-PERDA DE ISENÇÃO/SUSPENSÃO/REDUÇÃO DE ALÍQUOTAS/NÃO INCIDÊNCIA POR DESCUMPRIMENTO DAS COND. EXIGIDAS PARA O BENEFÍCIO'],
        ['codigo' => '9331-04', 'grupo' => 'ContribuicoesDiversas', 'periodicidade' => 'ME', 'descricao' => 'CIDE - COMBUSTÍVEIS - MERCADO INTERNO - LEI Nº 10.336/2001, ART. 5º, INC. II - DIESEL'],
        ['codigo' => '9331-05', 'grupo' => 'ContribuicoesDiversas', 'periodicidade' => 'ME', 'descricao' => 'CIDE - COMBUSTÍVEIS - MERCADO INTERNO - LEI Nº 10.336/2001, ART. 5º, INC. III - QAV'],
        ['codigo' => '9331-06', 'grupo' => 'ContribuicoesDiversas', 'periodicidade' => 'ME', 'descricao' => 'CIDE - COMBUSTÍVEIS - LEI Nº 10.336/2001, ART. 5º, INC. IV - OUTROS QUEROSENES'],
        ['codigo' => '9331-07', 'grupo' => 'ContribuicoesDiversas', 'periodicidade' => 'ME', 'descricao' => 'CIDE - COMBUSTÍVEIS - MERCADO INTERNO - LEI Nº 10.336/2001, ART. 5º, INC. V - FUEL OIL'],
        ['codigo' => '9331-08', 'grupo' => 'ContribuicoesDiversas', 'periodicidade' => 'ME', 'descricao' => 'CIDE - COMBUSTÍVEIS - MERCADO INTERNO - LEI Nº 10.336/2001, ART. 5º, INC. VI - GLP'],
        ['codigo' => '9331-09', 'grupo' => 'ContribuicoesDiversas', 'periodicidade' => 'ME', 'descricao' => 'CIDE - COMBUSTÍVEIS - MERCADO INTERNO - LEI Nº 10.336/2001, ART. 5º, INC. VII - ÁLCOOL'],
        ['codigo' => '9331-10', 'grupo' => 'ContribuicoesDiversas', 'periodicidade' => 'ME', 'descricao' => 'CIDE-COMBUSTÍVEIS-MERCADO INTERNO-LEI 10.336/2001,ART. 10,§ 1º-PERDA DE ISENÇÃO/SUSPENSÃO/REDUÇÃO DE ALÍQUOTAS/NÃO INCIDÊNCIA POR DESCUMPRIMENTO DAS COND. EXIGIDAS PARA O BENEFÍCIO'],
        ['codigo' => '9197-01', 'grupo' => 'ContribuicoesDiversas', 'periodicidade' => 'ME', 'descricao' => 'CONTRIBUIÇÃO SOBRE A RECEITA DE LOTERIA DE APOSTAS DE QUOTA FIXA'],
        ['codigo' => '1661-01', 'grupo' => 'Cpss', 'periodicidade' => 'DC', 'descricao' => 'CPSS - SERVIDOR CIVIL ATIVO'],
        ['codigo' => '1684-03', 'grupo' => 'Cpss', 'periodicidade' => 'DC', 'descricao' => 'CPSS - SERVIDOR CIVIL LICENCIADO/AFASTADO'],
        ['codigo' => '1700-01', 'grupo' => 'Cpss', 'periodicidade' => 'DC', 'descricao' => 'CPSS - SERVIDOR CIVIL INATIVO'],
        ['codigo' => '1717-01', 'grupo' => 'Cpss', 'periodicidade' => 'DC', 'descricao' => 'CPSS - PENSIONISTA CIVIL'],
        ['codigo' => '1723-03', 'grupo' => 'Cpss', 'periodicidade' => 'DC', 'descricao' => 'CPSS - SERVIDOR CIVIL ATIVO - PRECATÓRIO JUDICIAL E REQUISIÇAÕ DE PEQUENO VALOR'],
        ['codigo' => '1730-03', 'grupo' => 'Cpss', 'periodicidade' => 'DC', 'descricao' => 'CPSS - SERVIDOR CIVIL INATIVO - PRECATÓRIO JUDICIAL E REQUISIÇÃO DE PEQUENO VALOR'],
        ['codigo' => '1752-03', 'grupo' => 'Cpss', 'periodicidade' => 'DC', 'descricao' => 'CPSS - PENSIONISTA - PRECATÓRIO JUDICIAL E REQUISIÇÃO DE PEQUENO VALOR'],
        ['codigo' => '1769-01', 'grupo' => 'Cpss', 'periodicidade' => 'DC', 'descricao' => 'CPSS - PATRONAL - SERVIDOR CIVIL ATIVO - OPERAÇÃO INTRA - ORÇAMENTÁRIA'],
        ['codigo' => '1781-02', 'grupo' => 'Cpss', 'periodicidade' => 'ME', 'descricao' => 'CPSS - PATRONAL - SERVIDOR CIVIL LICENCIADO/AFASTADO - OPERAÇÃO INTRA-ORÇAMENTÁRIA'],
        ['codigo' => '1781-04', 'grupo' => 'Cpss', 'periodicidade' => 'DC', 'descricao' => 'CPSS - PATRONAL - SERVIDOR CIVIL LICENCIADO'],
        ['codigo' => '1814-01', 'grupo' => 'Cpss', 'periodicidade' => 'DC', 'descricao' => 'CPSS - PATRONAL - SERVIDOR NO EXTERIOR - OPERAÇÃO INTRA - ORÇAMENTÁRIA'],
        ['codigo' => '1837-04', 'grupo' => 'Cpss', 'periodicidade' => 'ME', 'descricao' => 'CPSS - PATRONAL - PRECATÓRIO JUDCIAL - OPERAÇÃO INTRA - ORÇAMENTÁRIA'],
        ['codigo' => '5492-01', 'grupo' => 'Cpss', 'periodicidade' => 'DC', 'descricao' => 'CPSS/FCDF - SERVIDOR CIVIL ATIVO'],
        ['codigo' => '5502-01', 'grupo' => 'Cpss', 'periodicidade' => 'DC', 'descricao' => 'CPSS/FCDF - SERVIDOR CIVIL INATIVO/PENSIONISTA'],
        ['codigo' => '5519-01', 'grupo' => 'Cpss', 'periodicidade' => 'DC', 'descricao' => 'CPSS/FCDF - PATRONAL'],
        ['codigo' => '1068-01', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET -INCORPORAÇÃO IMOBILIÁRIA - PMCMV - PAGAMENTO UNIFICADO'],
        ['codigo' => '1068-02', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET - CONTRATO DE CONSTRUÇÃO - PMCMV - PAGAMENTO UNIFICADO'],
        ['codigo' => '1068-05', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET - CONSTRUÇÃO/REFORMA DE CRECHES E PRÉ-ESCOLAS - PAGAMENTO UNIFICADO'],
        ['codigo' => '1068-06', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET - CONTRATO DE ALIENAÇÃO - PMCMV - PAGAMENTO UNIFICADO'],
        ['codigo' => '1068-07', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET -INCORPORAÇÃO IMOBILIÁRIA - PMCMV - PAGAMENTO UNIFICADO - SCP'],
        ['codigo' => '4095-01', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET - INCORPORAÇÃO IMOBILIÁRIA - PAGAMENTO UNIFICADO'],
        ['codigo' => '4095-02', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET - INCORPORAÇÃO IMOBILIÁRIA - PAGAMENTO UNIFICADO - SCP'],
        ['codigo' => '4112-01', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/IRPJ - INCORPORAÇÃO IMOBILIÁRIA - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO'],
        ['codigo' => '4112-02', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/IRPJ - INCORPORAÇÃO IMOBILIÁRIA - PMCMV - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO'],
        ['codigo' => '4112-03', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/IRPJ - CONTRATO DE CONSTRUÇÃO - PMCMV - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO'],
        ['codigo' => '4112-05', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/IRPJ - CONSTRUÇÃO/REFORMA DE CRECHES E PRÉ-ESCOLAS - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO'],
        ['codigo' => '4112-06', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/IRPJ - CONTRATO DE ALIENAÇÃO - PMCMV - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO'],
        ['codigo' => '4112-07', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/IRPJ - INCORPORAÇÃO IMOBILIÁRIA - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO - SCP'],
        ['codigo' => '4112-08', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/IRPJ - INCORPORAÇÃO IMOBILIÁRIA - PMCMV - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO - SCP'],
        ['codigo' => '4138-01', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/PIS - INCORPORAÇÃO IMOBILIÁRIA - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO'],
        ['codigo' => '4138-02', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/PIS - INCORPORAÇÃO IMOBILIÁRIA - PMCMV - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO'],
        ['codigo' => '4138-03', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/PIS - CONTRATO DE CONSTRUÇÃO - PMCMV - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO'],
        ['codigo' => '4138-05', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/PIS - CONSTRUÇÃO/REFORMA DE CRECHES E PRÉ-ESCOLAS - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO'],
        ['codigo' => '4138-06', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/PIS - CONTRATO DE ALIENAÇÃO - PMCMV - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO'],
        ['codigo' => '4138-07', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/PIS - INCORPORAÇÃO IMOBILIÁRIA - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO - SCP'],
        ['codigo' => '4138-08', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/PIS - INCORPORAÇÃO IMOBILIÁRIA - PMCMV - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO - SCP'],
        ['codigo' => '4153-01', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/CSLL - INCORPORAÇÃO IMOBILIÁRIA - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO'],
        ['codigo' => '4153-02', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/CSLL - INCORPORAÇÃO IMOBILIÁRIA - PMCMV - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO'],
        ['codigo' => '4153-03', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/CSLL - CONTRATO DE CONSTRUÇÃO - PMCMV - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO'],
        ['codigo' => '4153-05', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/CSLL - CONSTRUÇÃO/REFORMA DE CRECHES E PRÉ-ESCOLAS - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO'],
        ['codigo' => '4153-06', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/CSLL - CONTRATO DE ALIENAÇÃO - PMCMV - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO'],
        ['codigo' => '4153-07', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/CSLL - INCORPORAÇÃO IMOBILIÁRIA - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO - SCP'],
        ['codigo' => '4153-08', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/CSLL - INCORPORAÇÃO IMOBILIÁRIA - PMCMV - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO - SCP'],
        ['codigo' => '4166-01', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/COFINS - INCORPORAÇÃO IMOBLIÁRIA - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO'],
        ['codigo' => '4166-02', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/COFINS - INCORPORAÇÃO IMOBLIÁRIA - PMCMV - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO'],
        ['codigo' => '4166-03', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/COFINS - CONTRATO DE CONSTRUÇÃO - PMCMV - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO'],
        ['codigo' => '4166-05', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/COFINS - CONSTRUÇÃO/REFORMA DE CRECHES E PRÉESCOLAS - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO'],
        ['codigo' => '4166-06', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/COFINS - CONTRATO DE ALIENAÇÃO - PMCMV - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO'],
        ['codigo' => '4166-07', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/COFINS - INCORPORAÇÃO IMOBLIÁRIA - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO - SCP'],
        ['codigo' => '4166-08', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'RET/COFINS - INCORPORAÇÃO IMOBLIÁRIA - PMCMV - PAGAMENTO UNIFICADO - PJ AMPARADA PELA SUSPENSÃO DA EXIGIBILIDADE DO CRÉDITO TRIBUTÁRIO - SCP'],
        ['codigo' => '6177-01', 'grupo' => 'RetPagamentoUnificado', 'periodicidade' => 'ME', 'descricao' => 'PAGAMENTO UNIFICADO - REGIME DE TRIBUTAÇÃO ESPECÍFICA DO FUTEBOL (TEF)'],
        ];
    }

    /**
     * IPI e Contribuições Diversas (exceto código 9197-01) exigem o CNPJ do
     * estabelecimento (raiz + filial) — regra 4.2-I do manual.
     */
    public static function exigeEstabelecimento(string $grupo, string $codigo): bool
    {
        return in_array($grupo, ['Ipi', 'ContribuicoesDiversas'], true) && $codigo !== '9197-01';
    }

    /**
     * RET/Pagamento Unificado (exceto código 6177-01) exige o CNPJ da
     * incorporação — regra 4.2-II do manual.
     */
    public static function exigeIncorporacao(string $grupo, string $codigo): bool
    {
        return $grupo === 'RetPagamentoUnificado' && $codigo !== '6177-01';
    }

    /**
     * Códigos com "- SCP" no final da descrição exigem o CNPJ completo da
     * Sociedade em Conta de Participação — regra 4.2-III do manual.
     */
    public static function exigeScp(string $descricao): bool
    {
        return (bool) preg_match('/-\s*SCP$/i', trim($descricao));
    }

    /**
     * Código 4028-02 (IOF - Operações com Ouro - Ativo Financeiro) exige o
     * código do município sede do estabelecimento produtor — regra 4.2-IV.
     */
    public static function exigeMunicipioOuro(string $codigo): bool
    {
        return $codigo === '4028-02';
    }

    /**
     * Só cobrimos periodicidade mensal/trimestral/anual na v1 — códigos
     * diários/decendiais (poucos, majoritariamente IOF) ficam bloqueados
     * (ver MitService::encerrarApuracaoComMovimento).
     */
    public static function periodicidadeSuportada(string $periodicidade): bool
    {
        return in_array($periodicidade, ['ME', 'TR', 'AN'], true);
    }
}
