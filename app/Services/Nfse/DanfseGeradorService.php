<?php

namespace App\Services\Nfse;

use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Gera o DANFSe v2.0 (PDF) a partir do XML da NFS-e de padrão nacional,
 * localmente, seguindo a Nota Técnica SE/CGNFS-e nº 008/2026.
 *
 * Substitui a antiga API oficial GET https://adn.nfse.gov.br/danfse/{chave},
 * sobrestada (suspensa) em 1º de julho de 2026.
 */
class DanfseGeradorService
{
    public function gerar(string $xmlNfse): string
    {
        $reader = new DanfseXmlReader($xmlNfse);
        $dados = $reader->toArray();
        $dados['qrCodeImg'] = $this->qrCode($dados['qrCodeUrl']);
        $dados['logoBase64'] = $this->logo();

        $pdf = Pdf::setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,        // libera as fontes locais via @font-face (dentro do chroot)
                'isHtml5ParserEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'defaultFont' => 'arimo',
                'dpi' => 96,
                'fontDir' => storage_path('fonts'),
                'fontCache' => storage_path('fonts'),
                'chroot' => [base_path(), storage_path()],
            ])
            ->loadView('nfse.danfse', $dados);

        return $pdf->output();
    }

    /** HTML puro do DANFSe — pré-visualização do layout sem passar pelo dompdf. */
    public function html(string $xmlNfse): string
    {
        $reader = new DanfseXmlReader($xmlNfse);
        $dados = $reader->toArray();
        $dados['qrCodeImg'] = $this->qrCode($dados['qrCodeUrl']);
        $dados['logoBase64'] = $this->logo();

        return view('nfse.danfse', $dados)->render();
    }

    /** Nome de arquivo padronizado para o DANFSe. */
    public function nomeArquivo(string $xmlNfse): string
    {
        $chave = (new DanfseXmlReader($xmlNfse))->chaveAcesso();

        return 'DANFSe_' . substr($chave, -20) . '.pdf';
    }

    private function logo(): ?string
    {
        $path = public_path('img/nfse-logo.png');
        if (! is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode(file_get_contents($path));
    }

    /**
     * QR Code como PNG (data URI). PNG via GD é mais confiável no dompdf do que
     * SVG inline (o php-svg-lib não renderiza bem transforms aninhados).
     */
    private function qrCode(string $url): string
    {
        $options = new \chillerlan\QRCode\QROptions([
            'eccLevel'      => \chillerlan\QRCode\Common\EccLevel::M,
            'outputInterface' => \chillerlan\QRCode\Output\QRGdImagePNG::class,
            'scale'         => 6,
            'quietzoneSize' => 1,
            'outputBase64'  => true,
        ]);

        return (new \chillerlan\QRCode\QRCode($options))->render($url);
    }
}
