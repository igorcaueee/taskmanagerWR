<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Um débito (código de receita + valor) dentro de uma MitApuracaoRascunho.
 */
class MitDebitoRascunho extends Model
{
    protected $table = 'mit_debitos_rascunho';

    protected $fillable = [
        'mit_apuracao_rascunho_id',
        'grupo',
        'codigo_receita',
        'periodicidade',
        'ano_referencia',
        'mes_referencia',
        'trimestre_referencia',
        'valor',
        'cnpj_estabelecimento',
        'cnpj_incorporacao',
        'cnpj_scp',
        'codigo_municipio_ouro',
    ];

    public function apuracao(): BelongsTo
    {
        return $this->belongsTo(MitApuracaoRascunho::class, 'mit_apuracao_rascunho_id');
    }
}
