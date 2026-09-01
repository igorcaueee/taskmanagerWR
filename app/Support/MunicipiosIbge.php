<?php

namespace App\Support;

/**
 * Tabela de municípios do IBGE (DTB 2025) para resolver código de 7 dígitos
 * em nome + UF. Usada no DANFSe quando o XML da NFS-e traz só o código.
 */
class MunicipiosIbge
{
    /** @var array<string,string>|null  código => "Nome|UF" */
    private static ?array $map = null;

    private static function map(): array
    {
        return self::$map ??= require resource_path('data/municipios_ibge.php');
    }

    private static function raw(?string $codigo): ?string
    {
        if ($codigo === null) {
            return null;
        }
        $codigo = preg_replace('/\D/', '', $codigo);

        return self::map()[$codigo] ?? null;
    }

    public static function nome(?string $codigo): ?string
    {
        $raw = self::raw($codigo);

        return $raw ? explode('|', $raw)[0] : null;
    }

    public static function uf(?string $codigo): ?string
    {
        $raw = self::raw($codigo);

        return $raw ? (explode('|', $raw)[1] ?: null) : null;
    }

    /** "Teutônia / RS" (ou null se o código não existir). */
    public static function nomeUf(?string $codigo): ?string
    {
        $raw = self::raw($codigo);
        if (! $raw) {
            return null;
        }
        [$nome, $uf] = array_pad(explode('|', $raw), 2, '');

        return $uf ? "{$nome} / {$uf}" : $nome;
    }
}
