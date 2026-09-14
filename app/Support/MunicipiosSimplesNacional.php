<?php

namespace App\Support;

/**
 * Traduz o código IBGE de 7 dígitos (App\Support\MunicipiosIbge, usado no
 * select de Município da tela do PGDAS) para o "Código do Município" próprio
 * do Simples Nacional — é esse código, não o IBGE, que o campo
 * "codigoOutroMunicipio" do TRANSDECLARACAO11 espera. Ver o cabeçalho de
 * resources/data/municipios_sn_codigos.php para como essa tabela foi gerada
 * e o erro real da API que confirmou a distinção.
 */
class MunicipiosSimplesNacional
{
    /** @var array<string,string>|null  código IBGE => código SN */
    private static ?array $map = null;

    private static function map(): array
    {
        return self::$map ??= require resource_path('data/municipios_sn_codigos.php');
    }

    public static function codigo(?string $codigoIbge): ?string
    {
        if ($codigoIbge === null) {
            return null;
        }

        $codigoIbge = preg_replace('/\D/', '', $codigoIbge);

        return self::map()[$codigoIbge] ?? null;
    }
}
