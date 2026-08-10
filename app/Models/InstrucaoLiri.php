<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstrucaoLiri extends Model
{
    protected $table = 'instrucoes_liri';

    protected $fillable = [
        'autor_id',
        'titulo',
        'conteudo',
        'arquivo_nome',
        'arquivo_path',
    ];

    public function autor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'autor_id');
    }
}
