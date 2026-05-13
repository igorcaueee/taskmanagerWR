<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailCampanha extends Model
{
    protected $table = 'email_campanhas';

    protected $fillable = [
        'titulo',
        'assunto',
        'conteudo_html',
        'status',
        'destinatarios',
        'total_destinatarios',
        'total_enviados',
        'enviada_em',
        'criado_por',
    ];

    protected $casts = [
        'destinatarios' => 'array',
        'enviada_em' => 'datetime',
    ];

    public function criador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'criado_por');
    }
}
