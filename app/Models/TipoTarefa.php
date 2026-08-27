<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoTarefa extends Model
{
    protected $table = 'tipos_tarefa';

    protected $fillable = [
        'nome',
        'titulo_padrao',
        'data_vencimento',
    ];

    protected $casts = [
        'data_vencimento' => 'date',
    ];

    public function tarefas(): HasMany
    {
        return $this->hasMany(Tarefa::class);
    }

    public function regras(): HasMany
    {
        return $this->hasMany(TipoTarefaRegra::class);
    }
}
