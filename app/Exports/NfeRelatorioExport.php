<?php

namespace App\Exports;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gera o relatório fiscal de NF-e/NFC-e (Excel, uma aba por tipo, uma linha
 * por item/produto), no mesmo padrão de colunas usado hoje num sistema pago
 * pela contabilidade. Os dados de cada linha vêm de App\Services\NfeXmlParser.
 *
 * A escrita é feita em streaming (OpenSpout) direto pro output, linha a linha,
 * sem montar a planilha inteira em memória — clientes com milhares de notas
 * (NFC-e de varejo/food service) estouravam o memory_limit no PhpSpreadsheet.
 */
class NfeRelatorioExport
{
    private const COLUNAS_NF = [
        'Chave_Acesso', 'Mod_Doc', 'Serie', 'Nº_Doc', 'Data_Emis', 'Hora_Emis', 'Data_Saida', 'Hora_Saida',
        'Local_da_Operação', 'Finalidade', 'Indicador_Operação', 'Cons_Final', 'Tipo_Oper', 'Nat_Oper',
        'CNPJ_Emit', 'Razao_Social_Emit', 'Bairro_Emit', 'IE_Emit', 'Munic_Emit', 'UF_Emit', 'Reg_Trib_Emit',
        'CNPJ/CPF_Dest', 'Razao/Nome_Dest', 'Bairro_Dest', 'IE_Dest', 'Munic_Dest', 'UF_Dest',
        'Item', 'Cod_Prod', 'Descr_Prod', 'Cod_EAN', 'NCM', 'CEST', 'EXTIPI', 'CFOP', 'Und_Com', 'Qtd_Com',
        'V_Unit_Com', 'V_Prod', 'Cod_EAN_Trib', 'Und_Trib', 'Qtd_Trib', 'V_Unit_Trib', 'V_Desconto', 'V_Frete',
        'V_Seg', 'V_Outros', 'Ind_Tot', 'Ficha_Importação', 'V_Tot_Trib', 'Orig', 'CST/CSON', 'Mod_BC_ICMS',
        '%_Red_BC_ICMS', 'V_BC_ICMS', '%_ICMS', '%_ICMS_SN', 'V_ICMS_Op', 'V_ICMS_Dif', 'V_ICMS', 'V_ICMS_SN',
        'V_BC_FCP', '%_FCP', 'V_FCP', 'V_ICMS_Deson', 'Mot_ICMS_Deson', 'Mod_BC_ST', '%_MVA_ST', '%_Red_BC_ST',
        'V_BC_ST', '%_ICMS_ST', 'V_ICMS_ST', 'V_BC_FCP_ST', '%_FCP_ST', 'V_FCP_ST', 'Qtd_Trib_Ret_Ant',
        '%_Rem_Ret_Ant', 'V_ICMS_Ret_Ant_Monof', 'V_BC_ST_Ret', '%_ST_Ret', 'V_ICMS_ST_Ret', 'V_BC_ST_FCP_Ret',
        '%_FCP_ST_Ret', 'V_FCP_ST_Ret', 'V_BC_ICMS_UF_Dest', '%_ICMS_UF_Dest', '%_ICMS_UF_Inter',
        '%_ICMS_UF_InterPart', 'V_ICMS_UF_Dest', 'V_ICMS_UF_Remet', 'V_BC_FCP_UF_Dest', '%_FCP_UF_Dest',
        'V_FCP_UF_Dest', 'Cod_Enq_IPI', 'CST_IPI', 'V_BC_IPI', '%_IPI', 'V_IPI', 'V_IPI_Devol', 'CST_PIS',
        'V_BC_PIS', '%_PIS', 'V_PIS', 'CST_COF', 'V_BC_COF', '%_COF', 'V_COF', 'V_BC_ISS', '%_ISS', 'V_ISS',
        'Cod_Mun_FG', 'Cod_List_Serv', 'V_Deducao_ISS', 'V_Outras_Ret', 'V_Desc_Incond', 'V_Desc_Cond',
        'V_ISS_Ret', 'Indicador_Exigib', 'Cod_Serv_Mun', 'Cod_Mun_Incid_ISS', 'Indicad_Incent_Fiscal',
        'Mod_Frete', 'CNPJ_Transp', 'Razao/Nome_Transp', 'IE_Transp', 'Munic_Transp', 'UF_Transp',
        'Dados_Adicionais_Produto', 'Dados_Adicionais_Interesse_Fisco', 'Dados_Adicionais_Interesse_Contribuinte',
        'Chave_NFe_Devolvida', 'Per_NFe_Devolv', 'Nº_NFe_Devolv', 'Cod. Pedido',
        'CST_IBS_CBS', 'Cod_Class_Trib_IBS_CBS', 'V_BC_IBS_CBS', '%_IBS_UF', 'V_IBS_UF', '%_IBS_Mun', 'V_IBS_Mun',
        '%_CBS', 'V_CBS', 'CST_IS', 'V_BC_IS', '%_IS', 'V_IS', 'Status_Doc',
    ];

    private const COLUNAS_CTE = [
        'Chave_Acesso', 'Mod_Doc', 'Serie', 'Nº_Doc', 'Data_Emis', 'Hora_Emis', 'Nat_Oper', 'CFOP',
        'CNPJ_Emit', 'Razao_Social_Emit', 'IE_Emit', 'Munic_Emit', 'UF_Emit',
        'CNPJ/CPF_Rem', 'Razao/Nome_Rem', 'CNPJ/CPF_Dest', 'Razao/Nome_Dest', 'Munic_Dest', 'UF_Dest',
        'Munic_Ini', 'UF_Ini', 'Munic_Fim', 'UF_Fim', 'V_Prest', 'V_Receber',
        'CST_ICMS', 'V_BC_ICMS', '%_ICMS', 'V_ICMS',
        'CST_IBS_CBS', 'Cod_Class_Trib_IBS_CBS', 'V_BC_IBS_CBS', '%_IBS_UF', 'V_IBS_UF', '%_IBS_Mun', 'V_IBS_Mun',
        '%_CBS', 'V_CBS', 'Status_Doc',
    ];

    private const COLUNAS_NFC = [
        'Chave_Acesso', 'Mod_Doc', 'Serie', 'Nº_Doc', 'Data_Emis', 'Hora_Emis', 'Finalidade', 'Cons_Final',
        'Tipo_Oper', 'Nat_Oper', 'CNPJ_Emit', 'Razao_Social_Emit', 'IE_Emit', 'Munic_Emit', 'UF_Emit',
        'Reg_Trib_Emit', 'CNPJ/CPF_Dest', 'Razao/Nome_Dest', 'IE_Dest', 'Munic_Dest', 'UF_Dest', 'Item',
        'Cod_Prod', 'Descr_Prod', 'Cod_EAN', 'NCM', 'CEST', 'CFOP', 'Und_Com', 'Qtd_Com', 'V_Unit_Com',
        'V_Prod', 'Cod_EAN_Trib', 'Und_Trib', 'Qtd_Trib', 'V_Unit_Trib', 'V_Desconto', 'V_Frete', 'V_Seg',
        'V_Outros', 'Ind_Tot', 'V_Tot_Trib', 'Orig', 'CST/CSON', 'Mod_BC_ICMS', '%_Red_BC_ICMS', 'V_BC_ICMS',
        '%_ICMS', 'V_ICMS_Op', 'V_ICMS_Dif', 'V_ICMS', 'V_ICMS_Deson', 'Mot_ICMS_Deson', '%_FCP', 'V_FCP',
        'Mod_BC_ST', '%_MVA_ST', '%_Red_BC_ST', 'V_BC_ST', '%_ICMS_ST', 'V_ICMS_ST', 'V_BC_FCP_ST', '%_FCP_ST',
        'V_FCP_ST', 'V_BC_ST_Ret', '%_ST_Ret', 'V_ICMS_ST_Ret', 'V_BC_ST_FCP_Ret', '%_FCP_ST_Ret', 'V_FCP_ST_Ret',
        'Cod_Enq_IPI', 'CST_IPI', 'V_BC_IPI', '%_IPI', 'V_IPI', 'CST_PIS', 'V_BC_PIS', '%_PIS', 'V_PIS',
        'CST_COF', 'V_BC_COF', '%_COF', 'V_COF', 'Mod_Frete', 'CNPJ_Transp', 'Razao/Nome_Transp', 'IE_Transp',
        'Munic_Transp', 'UF_Transp', 'Dados_Adicionais_Interesse_Fisco', 'Dados_Adicionais_Interesse_Contribuinte',
        'CST_IBS_CBS', 'Cod_Class_Trib_IBS_CBS', 'V_BC_IBS_CBS', '%_IBS_UF', 'V_IBS_UF', '%_IBS_Mun', 'V_IBS_Mun',
        '%_CBS', 'V_CBS', 'CST_IS', 'V_BC_IS', '%_IS', 'V_IS', 'Status_Doc',
    ];

    /**
     * Passe `null` em `$linhasNf`/`$linhasNfc`/`$linhasCte` para gerar o relatório
     * apenas com as demais abas (ex.: exportação individual de NF-e ou de NFC-e).
     * Cada fonte pode ser um array ou um iterável/Generator (lido uma única vez).
     *
     * @param  iterable<array<string, mixed>>|null  $linhasNf
     * @param  iterable<array<string, mixed>>|null  $linhasNfc
     * @param  iterable<array<string, mixed>>|null  $linhasCte
     */
    public function __construct(private ?iterable $linhasNf, private ?iterable $linhasNfc, private ?iterable $linhasCte = null) {}

    public function download(string $filename): StreamedResponse
    {
        $abas = array_filter([
            ['NF', self::COLUNAS_NF, $this->linhasNf],
            ['NFC', self::COLUNAS_NFC, $this->linhasNfc],
            ['CTe', self::COLUNAS_CTE, $this->linhasCte],
        ], fn (array $aba) => $aba[2] !== null);

        return response()->stream(function () use ($abas) {
            $writer = new Writer;
            $writer->openToFile('php://output');

            $primeira = true;
            foreach ($abas as [$titulo, $colunas, $linhas]) {
                $sheet = $primeira ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
                $sheet->setName($titulo);
                $primeira = false;

                $writer->addRow(Row::fromValuesWithStyle($colunas, self::estiloCabecalho()));

                foreach ($linhas as $linha) {
                    $valores = [];
                    foreach ($colunas as $nome) {
                        $valores[] = $linha[$nome] ?? '';
                    }
                    $writer->addRow(Row::fromValues($valores));
                }
            }

            $writer->close();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.addslashes($filename).'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private static function estiloCabecalho(): Style
    {
        return (new Style)
            ->withFontBold(true)
            ->withFontColor('FFFFFF')
            ->withBackgroundColor('1F3864')
            ->withShouldWrapText(true);
    }
}
