<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuração única do escritório para a API "Consulta CND" (SERPRO) —
 * contrato/credenciais separados do Integra Contador. Espera-se um único
 * registro.
 */
class ConsultaCndConfiguracao extends Model
{
    protected $table = 'consulta_cnd_configuracoes';

    protected $fillable = [
        'consumer_key',
        'consumer_secret',
        'ambiente',
    ];

    protected $casts = [
        'consumer_key' => 'encrypted',
        'consumer_secret' => 'encrypted',
    ];
}
