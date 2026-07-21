<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuração única do escritório para a API Integra Contador (SERPRO) —
 * procuração eletrônica única para toda a carteira, não por cliente.
 * Espera-se um único registro.
 */
class IntegraContadorConfiguracao extends Model
{
    protected $table = 'integra_contador_configuracoes';

    protected $fillable = [
        'cnpj_contratante',
        'arquivo_certificado',
        'senha_certificado',
        'consumer_key',
        'consumer_secret',
        'ambiente',
    ];

    protected $casts = [
        'senha_certificado' => 'encrypted',
        'consumer_key' => 'encrypted',
        'consumer_secret' => 'encrypted',
    ];
}
