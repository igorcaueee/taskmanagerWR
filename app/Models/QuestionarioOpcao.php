<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionarioOpcao extends Model
{
    protected $table = 'questionario_opcoes';

    protected $fillable = ['pergunta_id', 'texto', 'pontos', 'ordem'];

    public function pergunta(): BelongsTo
    {
        return $this->belongsTo(QuestionarioPergunta::class, 'pergunta_id');
    }
}
