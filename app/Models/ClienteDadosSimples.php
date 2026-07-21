<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dados fiscais do Simples Nacional cadastrados manualmente por cliente —
 * CNAE/anexo/RBT12 (referência) e id_atividade (código interno da SERPRO/RFB
 * usado no payload de transmissão do PGDASD, diferente do CNAE).
 */
class ClienteDadosSimples extends Model
{
    protected $table = 'cliente_dados_simples';

    protected $fillable = [
        'cliente_id',
        'cnae_principal',
        'id_atividade',
        'anexo_simples',
        'rbt12',
        'dados_atualizados_em',
    ];

    protected $casts = [
        'rbt12' => 'decimal:2',
        'dados_atualizados_em' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
