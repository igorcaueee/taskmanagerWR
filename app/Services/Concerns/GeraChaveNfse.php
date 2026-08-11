<?php

namespace App\Services\Concerns;

/** Extrai a chave de acesso da resposta de emissão do ADN (formatos alternativos ainda não confirmados). */
trait GeraChaveNfse
{
    private function extrairChaveAcesso(array $resposta): ?string
    {
        return $resposta['chaveAcesso']
            ?? $resposta['ChaveAcesso']
            ?? $resposta['chNFSe']
            ?? null;
    }

    private function extrairNumeroNfse(array $resposta): ?string
    {
        return $resposta['numeroNFSe']
            ?? $resposta['nNFSe']
            ?? null;
    }

    private function extrairXmlNfse(array $resposta): ?string
    {
        $xmlGZipB64 = $resposta['nfseXmlGZipB64'] ?? $resposta['NfseXmlGZipB64'] ?? null;

        if ($xmlGZipB64 === null) {
            return $resposta['nfseXml'] ?? null;
        }

        return $this->descomprimirXml($xmlGZipB64);
    }
}
