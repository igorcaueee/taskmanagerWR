<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionarioRespostaItem extends Model
{
    protected $table = 'questionario_resposta_itens';

    protected $fillable = ['resposta_id', 'pergunta_id', 'opcao_id', 'pontos'];

    public function resposta(): BelongsTo
    {
        return $this->belongsTo(QuestionarioResposta::class, 'resposta_id');
    }

    public function pergunta(): BelongsTo
    {
        return $this->belongsTo(QuestionarioPergunta::class, 'pergunta_id');
    }

    public function opcao(): BelongsTo
    {
        return $this->belongsTo(QuestionarioOpcao::class, 'opcao_id');
    }
}
