<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class ExtratorTextoArquivoService
{
    /**
     * Extrai o texto de um arquivo PDF ou Word enviado pelo usuário.
     */
    public function extrair(UploadedFile $arquivo): string
    {
        return match ($arquivo->getClientOriginalExtension()) {
            'pdf' => $this->extrairPdf($arquivo),
            'doc', 'docx' => $this->extrairWord($arquivo),
            default => '',
        };
    }

    private function extrairPdf(UploadedFile $arquivo): string
    {
        $parser = new PdfParser;
        $pdf = $parser->parseFile($arquivo->getRealPath());

        return trim($pdf->getText());
    }

    private function extrairWord(UploadedFile $arquivo): string
    {
        $documento = WordIOFactory::load($arquivo->getRealPath());
        $texto = [];

        foreach ($documento->getSections() as $secao) {
            foreach ($secao->getElements() as $elemento) {
                if (method_exists($elemento, 'getText')) {
                    $texto[] = strip_tags($elemento->getText());
                }
            }
        }

        return trim(implode("\n", array_filter($texto)));
    }
}
