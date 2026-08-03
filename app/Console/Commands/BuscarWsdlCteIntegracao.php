<?php

namespace App\Console\Commands;

use App\Models\CertificadoContabilidade;
use App\Services\Concerns\LidaComCertificadoPfx;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('cte:buscar-wsdl')]
#[Description('Baixa o WSDL real do webservice CTeIntegracao (SEFAZ-RS) usando o certificado da contabilidade, para conferir os nomes corretos de operação/elementos.')]
class BuscarWsdlCteIntegracao extends Command
{
    use LidaComCertificadoPfx;

    public function handle(): int
    {
        $certificado = CertificadoContabilidade::first();

        if (!$certificado) {
            $this->error('Nenhum certificado da contabilidade configurado.');
            return self::FAILURE;
        }

        $certPath = storage_path('app/' . $certificado->arquivo);
        $endpoint = $certificado->ambiente === 'producao'
            ? 'https://dfe-servico.svrs.rs.gov.br/ws/CTeIntegracao/CTeIntegracao.asmx?wsdl'
            : 'https://dfe-servico-homologacao.svrs.rs.gov.br/ws/CTeIntegracao/CTeIntegracao.asmx?wsdl';

        [$pemCert, $pemKey, $tempFiles] = $this->extrairPem($certPath, $certificado->senha);

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_SSLCERT        => $pemCert,
                CURLOPT_SSLKEY         => $pemKey,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO         => resource_path('certificados-icp-brasil/dfe-rs-ca-bundle.pem'),
            ]);

            $resposta  = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            unset($ch);

            if ($resposta === false) {
                $this->error("Falha na conexão: {$curlError}");
                return self::FAILURE;
            }

            if ($httpCode >= 400) {
                $this->error("HTTP {$httpCode} ao buscar o WSDL.");
                $this->line(substr($resposta, 0, 2000));
                return self::FAILURE;
            }

            $destino = storage_path('app/temp/cte-integracao-wsdl.xml');

            if (!is_dir(dirname($destino))) {
                mkdir(dirname($destino), 0755, true);
            }

            file_put_contents($destino, $resposta);

            $this->info("WSDL salvo em: {$destino}");
            $this->info('Rode: cat ' . $destino . ' — e me envie o conteúdo.');

            return self::SUCCESS;
        } finally {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }
    }
}
