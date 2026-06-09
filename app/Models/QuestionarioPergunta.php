<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionarioPergunta extends Model
{
    protected $table = 'questionario_perguntas';

    protected $fillable = ['questionario_id', 'categoria', 'texto', 'ordem'];

    public function questionario(): BelongsTo
    {
        return $this->belongsTo(Questionario::class, 'questionario_id');
    }

    public function opcoes(): HasMany
    {
        return $this->hasMany(QuestionarioOpcao::class, 'pergunta_id')->orderBy('ordem');
    }
}
