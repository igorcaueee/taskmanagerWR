<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrecificacaoNcmGrupoItem extends Model
{
    protected $table = 'precificacao_ncm_grupo_itens';

    protected $fillable = [
        'precificacao_ncm_grupo_id',
        'ncm',
    ];

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(PrecificacaoNcmGrupo::class, 'precificacao_ncm_grupo_id');
    }
}
