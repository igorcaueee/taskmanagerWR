<?php

namespace App\Services\Concerns;

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * Assinatura digital XML (XMLDSig enveloped, C14N + RSA-SHA256) exigida pelo
 * Sistema Nacional NFS-e para a DPS, usando o par cert/chave PEM já extraído
 * do .pfx do cliente por LidaComCertificadoPfx::extrairPem().
 */
trait AssinaXmlDigitalmente
{
    /**
     * Assina o elemento identificado por $idElemento (ex: 'infDPS') dentro do
     * XML informado, com o atributo de id $atributoId (ex: 'id'), e retorna o
     * XML completo assinado.
     */
    protected function assinarElemento(
        string $xml,
        string $idElemento,
        string $atributoId,
        string $caminhoPemCert,
        string $caminhoPemKey
    ): string {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($xml);

        $node = $dom->getElementsByTagName($idElemento)->item(0);

        if ($node === null) {
            throw new \RuntimeException("Elemento <{$idElemento}> não encontrado no XML para assinatura.");
        }

        $dsig = new XMLSecurityDSig();
        $dsig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
        $dsig->addReferenceList(
            [$node],
            XMLSecurityDSig::SHA256,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature'],
            ['id_name' => $atributoId, 'overwrite' => false]
        );

        $chave = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $chave->loadKey($caminhoPemKey, true);

        $dsig->sign($chave);
        $dsig->add509Cert(file_get_contents($caminhoPemCert), true, false, ['subjectName' => true]);
        $dsig->appendSignature($node);

        return $dom->saveXML();
    }
}
