<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Questionario extends Model
{
    protected $table = 'questionarios';

    protected $fillable = ['titulo', 'descricao', 'slug', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];

    public function perguntas(): HasMany
    {
        return $this->hasMany(QuestionarioPergunta::class, 'questionario_id')->orderBy('ordem');
    }

    public function respostas(): HasMany
    {
        return $this->hasMany(QuestionarioResposta::class, 'questionario_id');
    }
}
