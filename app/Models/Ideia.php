<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ideia extends Model
{
    protected $table = 'ideias';

    protected $fillable = [
        'descricao',
        'colaborador_id',
        'status',
        'data_conclusao',
    ];

    protected $casts = [
        'data_conclusao' => 'date',
    ];

    public function colaborador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'colaborador_id');
    }
}
