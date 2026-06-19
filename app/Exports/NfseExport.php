<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class NfseExport
{
    public function __construct(private array $notas) {}

    public function download(string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $spreadsheet = new Spreadsheet();

        $this->buildDados($spreadsheet);
        $this->buildResumo($spreadsheet);

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . addslashes($filename) . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    // ─── Aba Dados ────────────────────────────────────────────────────────────

    private function buildDados(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Dados');

        $headers = [
            'A' => 'Numero (nNFSe)',
            'B' => 'Situacao NFS-e (cStat)',
            'C' => 'Data de Competencia (dCompet)',
            'D' => 'Data da Emissao (dhEmi)',
            'E' => 'Emitente (CNPJ / CPF)',
            'F' => 'Emitente (xNome)',
            'G' => 'Tomador (CNPJ / CPF / NIF)',
            'H' => 'Tomador (xNome)',
            'I' => 'Localidade de incidencia do ISSQN (xLocIncid)',
            'J' => 'Tipo Retencao ISSQN (tpRetISSQN)',
            'K' => 'Base Calculo ISSQN (R$) (vBC)',
            'L' => 'Aliquota ISSQN (%) (pAliqAplic)',
            'M' => 'Valor ISSQN (R$) (vISSQN)',
            'N' => 'CST PIS/COFINS (CST)',
            'O' => 'Base Calculo PIS/COFINS (R$) (vBCPisCofins)',
            'P' => 'Aliquota PIS (%) (pAliqPis)',
            'Q' => 'Aliquota COFINS (%) (pAliqCofins)',
            'R' => 'Tipo Retencao PIS/COFINS (tpRetPisCofins)',
            'S' => 'Retencao CP (R$) (vRetCP)',
            'T' => 'Retencao IRRF (R$) (vRetIRRF)',
            'U' => 'Retencao CSLL (R$) (vRetCSLL)',
            'V' => 'Valor Servico (vServ)',
            'W' => 'Valor Total Retencoes (R$) (vTotalRet)',
            'X' => 'Valor Liquido (R$) (vLiq)',
            'Y' => 'Consulta Publica da NFS-e (infNFSe)',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}1", $label);
        }

        $sheet->getStyle('A1:Y1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3864']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(35);

        $monetaryFmt = '#,##0.00';
        $moneyCols   = ['K', 'M', 'O', 'S', 'T', 'U', 'V', 'W', 'X'];
        $numericCols = ['L', 'P', 'Q'];

        $row = 2;
        foreach ($this->notas as $nota) {
            $bg = ($row % 2 === 0) ? 'EBF3FB' : 'FFFFFF';

            $values = [
                'A'  => $nota['nNFSe'],
                'B'  => $nota['cStat'],
                'C'  => $nota['dCompet'],
                'D'  => $nota['dhEmi'],
                'E'  => $nota['emitDoc'],
                'F'  => $nota['emitNome'],
                'G'  => $nota['tomaCNPJ'],
                'H'  => $nota['tomaNome'],
                'I'  => $nota['xLocIncid'],
                'J'  => $nota['tpRetISSQN'],
                'K'  => $nota['vBC'],
                'L'  => $nota['pAliqAplic'],
                'M'  => $nota['vISSQN'],
                'N'  => $nota['cst'],
                'O'  => $nota['vBCPisCofins'],
                'P'  => $nota['pAliqPis'],
                'Q'  => $nota['pAliqCofins'],
                'R'  => $nota['tpRetPisCofins'],
                'S'  => $nota['vRetCP'],
                'T'  => $nota['vRetIRRF'],
                'U'  => $nota['vRetCSLL'],
                'V'  => $nota['vServ'],
                'W'  => $nota['vTotalRet'],
                'X'  => $nota['vLiq'],
                'Y'  => $nota['chaveAcesso'],
            ];

            foreach ($values as $col => $value) {
                $sheet->setCellValue("{$col}{$row}", $value);
            }

            $sheet->getStyle("A{$row}:Y{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            foreach ($moneyCols as $col) {
                $sheet->getStyle("{$col}{$row}")->getNumberFormat()->setFormatCode($monetaryFmt);
            }

            $row++;
        }

        // Bordas na tabela completa
        if (count($this->notas)) {
            $sheet->getStyle("A1:Y" . ($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'B0B8C1'],
                    ],
                ],
            ]);
        }

        // Largura das colunas
        $widths = [
            'A' => 12, 'B' => 24, 'C' => 18, 'D' => 18,
            'E' => 18, 'F' => 30, 'G' => 18, 'H' => 30,
            'I' => 25, 'J' => 35, 'K' => 18, 'L' => 14,
            'M' => 16, 'N' => 12, 'O' => 18, 'P' => 14,
            'Q' => 16, 'R' => 30, 'S' => 16, 'T' => 16,
            'U' => 16, 'V' => 16, 'W' => 20, 'X' => 16,
            'Y' => 50,
        ];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
    }

    // ─── Aba Resumo ───────────────────────────────────────────────────────────

    private function buildResumo(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Resumo');

        $azulEscuro = '1F3864';
        $azulMedio  = '2E75B6';
        $azulClaro  = 'DEEAF1';
        $ambar      = 'FFD966';
        $branco     = 'FFFFFF';

        $ativas     = array_filter($this->notas, fn($n) => !$this->isCancelada($n));
        $canceladas = array_filter($this->notas, fn($n) => $this->isCancelada($n));
        $totalNota  = count($this->notas);
        $qtdAtivas  = count($ativas);
        $qtdCanceladas = count($canceladas);

        // ── Bloco 1: Cabeçalho ────────────────────────────────────────────────

        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'RESUMO DE RETENCOES - NFS-e');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => $branco]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $azulEscuro]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $sheet->mergeCells('A2:E2');
        $sheet->setCellValue('A2', "{$qtdAtivas} nota(s) ativa(s)  |  {$qtdCanceladas} cancelada(s)  |  Total: {$totalNota}");
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => $branco]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $azulMedio]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(22);

        // Linha 3: spacer
        $sheet->getRowDimension(3)->setRowHeight(10);

        $sheet->mergeCells('A4:E4');
        $sheet->setCellValue('A4', 'TOTAIS GERAIS');
        $sheet->getStyle('A4')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => $branco]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $azulMedio]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(22);

        // ── Bloco 2: Cabeçalho da tabela de totais ────────────────────────────

        $sheet->setCellValue('A5', 'Campo');
        $sheet->setCellValue('B5', 'Valor (R$)');
        $sheet->setCellValue('C5', 'Notas com retencao > 0');
        $sheet->getStyle('A5:C5')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => $branco]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $azulEscuro]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ── Cálculos ──────────────────────────────────────────────────────────

        $somaServ = array_sum(array_column(iterator_to_array((function () use ($ativas) {
            foreach ($ativas as $n) yield $n;
        })(), false), 'vServ'));

        $issRetidas   = array_filter($ativas, fn($n) => $this->issRetido($n));
        $pisNotas     = array_filter($this->notas, fn($n) => $n['vPis'] > 0);
        $cofinsNotas  = array_filter($this->notas, fn($n) => $n['vCofins'] > 0);
        $cpNotas      = array_filter($this->notas, fn($n) => $n['vRetCP'] > 0);
        $irrfNotas    = array_filter($this->notas, fn($n) => $n['vRetIRRF'] > 0);
        $csllNotas    = array_filter($this->notas, fn($n) => $n['vRetCSLL'] > 0);

        $somaServ    = array_sum(array_column(array_values($ativas), 'vServ'));
        $somaIss     = array_sum(array_column(array_values($issRetidas), 'vISSQN'));
        $somaPis     = array_sum(array_column(array_values($this->notas), 'vPis'));
        $somaCofins  = array_sum(array_column(array_values($this->notas), 'vCofins'));
        $somaCP      = array_sum(array_column(array_values($this->notas), 'vRetCP'));
        $somaIRRF    = array_sum(array_column(array_values($this->notas), 'vRetIRRF'));
        $somaCSLL    = array_sum(array_column(array_values($this->notas), 'vRetCSLL'));
        $totalRet    = $somaIss + $somaPis + $somaCofins + $somaCP + $somaIRRF + $somaCSLL;

        $numIss    = implode(', ', array_column(array_values($issRetidas), 'nNFSe'));
        $numPis    = implode(', ', array_column(array_values($pisNotas), 'nNFSe'));
        $numCofins = implode(', ', array_column(array_values($cofinsNotas), 'nNFSe'));
        $numCP     = implode(', ', array_column(array_values($cpNotas), 'nNFSe'));
        $numIRRF   = implode(', ', array_column(array_values($irrfNotas), 'nNFSe'));
        $numCSLL   = implode(', ', array_column(array_values($csllNotas), 'nNFSe'));

        $tableRows = [
            6  => ['Total de Servicos (notas ativas)',       $somaServ,   ''],
            7  => ['Total ISS Retido',                       $somaIss,    $numIss],
            8  => ['Total PIS',                              $somaPis,    $numPis],
            9  => ['Total COFINS',                           $somaCofins, $numCofins],
            10 => ['Total PIS + COFINS',                     $somaPis + $somaCofins, ''],
            11 => ['Retencao CP (Contrib. Previdenciaria)',  $somaCP,     $numCP],
            12 => ['Retencao IRRF',                          $somaIRRF,   $numIRRF],
            13 => ['Retencao CSLL',                          $somaCSLL,   $numCSLL],
        ];

        $moneyFmt = '#,##0.00';
        foreach ($tableRows as $rowNum => $data) {
            $bg = ($rowNum % 2 === 0) ? $azulClaro : $branco;
            $sheet->setCellValue("A{$rowNum}", $data[0]);
            $sheet->setCellValue("B{$rowNum}", $data[1]);
            $sheet->setCellValue("C{$rowNum}", $data[2]);
            $sheet->getStyle("A{$rowNum}:C{$rowNum}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("B{$rowNum}")->getNumberFormat()->setFormatCode($moneyFmt);
        }

        // Linha 14: Total de retenções
        $sheet->setCellValue('A14', 'TOTAL RETENCOES');
        $sheet->setCellValue('B14', $totalRet);
        $sheet->getStyle('A14:C14')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $ambar]],
        ]);
        $sheet->getStyle('B14')->getNumberFormat()->setFormatCode($moneyFmt);

        // Bordas bloco 2
        $sheet->getStyle('A5:C14')->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B0B8C1']]],
        ]);

        // ── Bloco 3: Detalhamento por nota ────────────────────────────────────

        $sheet->getRowDimension(15)->setRowHeight(8);

        $sheet->mergeCells('A16:K16');
        $sheet->setCellValue('A16', 'DETALHAMENTO POR NOTA');
        $sheet->getStyle('A16')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => $branco]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $azulEscuro]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(16)->setRowHeight(22);

        $detHeaders = ['N. Nota', 'Situacao', 'Prestador', 'Tomador', 'Valor Servico', 'ISS Retido', 'PIS', 'COFINS', 'CP', 'IRRF', 'CSLL'];
        $detCols    = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];

        foreach ($detCols as $i => $col) {
            $sheet->setCellValue("{$col}17", $detHeaders[$i]);
        }
        $sheet->getStyle('A17:K17')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => $branco]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $azulMedio]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $notasComRetencao = array_filter($this->notas, fn($n) =>
            ($this->issRetido($n)) ||
            $n['vPis'] > 0 || $n['vCofins'] > 0 ||
            $n['vRetCP'] > 0 || $n['vRetIRRF'] > 0 || $n['vRetCSLL'] > 0
        );

        $detRow = 18;
        foreach ($notasComRetencao as $nota) {
            $bg = ($detRow % 2 === 0) ? $azulClaro : $branco;
            $issRetidoValor = $this->issRetido($nota) ? $nota['vISSQN'] : 0;

            $sheet->setCellValue("A{$detRow}", $nota['nNFSe']);
            $sheet->setCellValue("B{$detRow}", $nota['cStat']);
            $sheet->setCellValue("C{$detRow}", $nota['emitNome'] ?: $nota['emitDoc']);
            $sheet->setCellValue("D{$detRow}", $nota['tomaNome'] ?: $nota['tomaCNPJ']);
            $sheet->setCellValue("E{$detRow}", $nota['vServ']);
            $sheet->setCellValue("F{$detRow}", $issRetidoValor);
            $sheet->setCellValue("G{$detRow}", $nota['vPis']);
            $sheet->setCellValue("H{$detRow}", $nota['vCofins']);
            $sheet->setCellValue("I{$detRow}", $nota['vRetCP']);
            $sheet->setCellValue("J{$detRow}", $nota['vRetIRRF']);
            $sheet->setCellValue("K{$detRow}", $nota['vRetCSLL']);

            $sheet->getStyle("A{$detRow}:K{$detRow}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            foreach (['E', 'F', 'G', 'H', 'I', 'J', 'K'] as $col) {
                $sheet->getStyle("{$col}{$detRow}")->getNumberFormat()->setFormatCode($moneyFmt);
            }

            $detRow++;
        }

        if ($detRow > 18) {
            $sheet->getStyle("A17:K" . ($detRow - 1))->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B0B8C1']]],
            ]);
        }

        // Larguras
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(26);
        $sheet->getColumnDimension('C')->setWidth(32);
        $sheet->getColumnDimension('D')->setWidth(32);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(14);
        $sheet->getColumnDimension('H')->setWidth(14);
        $sheet->getColumnDimension('I')->setWidth(14);
        $sheet->getColumnDimension('J')->setWidth(14);
        $sheet->getColumnDimension('K')->setWidth(14);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function isCancelada(array $nota): bool
    {
        return stripos($nota['cStat'], 'Cancelad') !== false;
    }

    private function issRetido(array $nota): bool
    {
        return stripos($nota['tpRetISSQN'], 'Retido') !== false && $nota['vISSQN'] > 0;
    }
}
