<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Referência estática da Lista Nacional de Serviços (LC 116) usada para
 * selecionar o código de tributação nacional (cTribNac) na emissão da DPS.
 */
class NfseServicoNacional extends Model
{
    protected $table = 'nfse_servicos_nacionais';

    protected $fillable = [
        'codigo_tributacao_nacional',
        'descricao',
    ];
}
