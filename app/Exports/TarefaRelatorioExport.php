<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TarefaRelatorioExport
{
    public function __construct(private Collection $tarefas)
    {
    }

    public function download(string $filename): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Tarefas');

        $headers = [
            'A' => 'Título',
            'B' => 'Cliente',
            'C' => 'Responsável',
            'D' => 'Supervisor',
            'E' => 'Etapa',
            'F' => 'Vencimento',
            'G' => 'Conclusão',
            'H' => 'Prioridade',
            'I' => 'Status',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue("{$col}1", $label);
        }

        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3864']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        $prioridadeLabels = ['alta' => 'Alta', 'media' => 'Média', 'baixa' => 'Baixa'];

        $row = 2;
        foreach ($this->tarefas as $tarefa) {
            $concluida = (bool) $tarefa->data_conclusao;
            $vencida = ! $concluida && $tarefa->data_vencimento?->isPast();
            $status = $concluida ? 'Concluída' : ($vencida ? 'Vencida' : 'Pendente');

            $bg = ($row % 2 === 0) ? 'EBF3FB' : 'FFFFFF';

            $values = [
                'A' => $tarefa->titulo,
                'B' => $tarefa->cliente?->nome ?? '—',
                'C' => $tarefa->responsavel?->nome ?? '—',
                'D' => $tarefa->supervisor?->nome ?? '—',
                'E' => $tarefa->etapa?->nome ?? '—',
                'F' => $tarefa->data_vencimento?->format('d/m/Y') ?? '—',
                'G' => $tarefa->data_conclusao?->format('d/m/Y') ?? '—',
                'H' => $prioridadeLabels[$tarefa->prioridade] ?? $tarefa->prioridade,
                'I' => $status,
            ];

            foreach ($values as $col => $value) {
                $sheet->setCellValue("{$col}{$row}", $value);
            }

            $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            $row++;
        }

        if ($this->tarefas->isNotEmpty()) {
            $sheet->getStyle('A1:I' . ($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'B0B8C1'],
                    ],
                ],
            ]);
        }

        $widths = ['A' => 40, 'B' => 30, 'C' => 25, 'D' => 25, 'E' => 20, 'F' => 14, 'G' => 14, 'H' => 12, 'I' => 14];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . addslashes($filename) . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
