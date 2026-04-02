<?php

namespace App\Models;

use Database\Factories\CompromissoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Compromisso extends Model
{
    /** @use HasFactory<CompromissoFactory> */
    use HasFactory;

    protected $table = 'compromissos';

    protected $fillable = [
        'titulo',
        'descricao',
        'data',
        'hora',
        'cor',
        'criado_por',
    ];

    protected $casts = [
        'data' => 'date',
    ];

    public function criador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'criado_por');
    }
}
