<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Apuração MIT com movimento em rascunho ou já transmitida — 1 registro
 * por cliente+ano+mês.
 */
class MitApuracaoRascunho extends Model
{
    protected $table = 'mit_apuracoes_rascunho';

    protected $fillable = [
        'cliente_id',
        'ano_apuracao',
        'mes_apuracao',
        'qualificacao_pj',
        'tributacao_lucro',
        'variacoes_monetarias',
        'regime_pis_cofins',
        'cpf_responsavel',
        'balanco_irpj',
        'balanco_csll',
        'sem_movimento',
        'status',
        'id_apuracao_serpro',
        'mensagem_erro',
        'encerrado_em',
    ];

    protected $casts = [
        'balanco_irpj' => 'boolean',
        'balanco_csll' => 'boolean',
        'sem_movimento' => 'boolean',
        'encerrado_em' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function debitos(): HasMany
    {
        return $this->hasMany(MitDebitoRascunho::class, 'mit_apuracao_rascunho_id');
    }
}
