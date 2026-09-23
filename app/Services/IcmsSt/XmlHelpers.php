<?php

namespace App\Services\IcmsSt;

/**
 * Helpers de navegação XML namespace-agnósticos (via local-name()), extraídos
 * do mesmo padrão usado em App\Services\NfeXmlParser — reaproveitados aqui
 * para não duplicar/reinterpretar o parsing de NF-e nos motores de ICMS-ST.
 */
trait XmlHelpers
{
    private static function filho(?\SimpleXMLElement $ctx, string $tag): ?\SimpleXMLElement
    {
        if (! $ctx) {
            return null;
        }

        $r = $ctx->xpath("./*[local-name()='{$tag}']");

        return $r[0] ?? null;
    }

    /** @return \SimpleXMLElement[] */
    private static function filhos(?\SimpleXMLElement $ctx, string $tag): array
    {
        if (! $ctx) {
            return [];
        }

        return $ctx->xpath("./*[local-name()='{$tag}']") ?: [];
    }

    /** Primeiro filho direto, qualquer nome — acha a variante de ICMS (ICMS00, ICMS10, ...). */
    private static function primeiroFilho(?\SimpleXMLElement $ctx): ?\SimpleXMLElement
    {
        if (! $ctx) {
            return null;
        }

        $r = $ctx->xpath('./*');

        return $r[0] ?? null;
    }

    private static function txt(?\SimpleXMLElement $ctx, string $tag): ?string
    {
        $n = self::filho($ctx, $tag);

        if ($n === null) {
            return null;
        }

        $v = trim((string) $n);

        return $v !== '' ? $v : null;
    }

    private static function num(?\SimpleXMLElement $ctx, string $tag): ?float
    {
        $v = self::txt($ctx, $tag);

        return $v !== null ? (float) $v : null;
    }

    private static function descendente(\SimpleXMLElement $ctx, string $tag): ?\SimpleXMLElement
    {
        $r = $ctx->xpath(".//*[local-name()='{$tag}']");

        return $r[0] ?? null;
    }
}
