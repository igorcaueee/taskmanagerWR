<?php

namespace App\Exports;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exportação Excel do módulo ICMS-ST, item a item — mesmas colunas dos CSVs
 * que os motores Python já geram (produto, NCM, CEST e origem do CEST,
 * segmento, base ST, alíquota, ICMS-ST, adicional, total, status). Mesmo
 * padrão de streaming (OpenSpout) de App\Exports\NfeRelatorioExport — não
 * monta a planilha inteira em memória.
 */
class IcmsStRelatorioExport
{
    private const COLUNAS = [
        'NF-e', 'Item', 'Chave_Acesso', 'Data_Emissao', 'Produto', 'NCM',
        'CEST_XML', 'CEST_Usado', 'CEST_Origem', 'Segmento', 'CFOP',
        'UF_Origem', 'UF_Destino', 'V_Prod', 'V_BC_Origem', 'Base_Operacao_Usada',
        'V_IPI', 'Aliq_Interestadual', 'MVA_Aplicada', 'Base_ST_Calculada',
        'Aliquota_Interna', 'ICMS_Proprio', 'ICMS_ST_Devido', 'Adicional_Tipo',
        'Adicional_Valor', 'Total_a_Recolher', 'Responsavel', 'Status', 'Status_Detalhe',
    ];

    /**
     * @param  iterable<array<string, mixed>>  $linhas
     */
    public function __construct(private iterable $linhas) {}

    public function download(string $filename): StreamedResponse
    {
        return response()->stream(function () {
            $writer = new Writer;
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValuesWithStyle(self::COLUNAS, self::estiloCabecalho()));

            foreach ($this->linhas as $linha) {
                $valores = [];
                foreach (self::COLUNAS as $nome) {
                    $valores[] = $linha[$nome] ?? '';
                }
                $writer->addRow(Row::fromValues($valores));
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
