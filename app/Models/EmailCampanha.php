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
        'total_falhas',
        'enviada_em',
        'enviar_em',
        'criado_por',
    ];

    protected $casts = [
        'destinatarios' => 'array',
        'enviada_em' => 'datetime',
        'enviar_em' => 'datetime',
    ];

    public function isConcluida(): bool
    {
        return ($this->total_enviados + $this->total_falhas) >= $this->total_destinatarios;
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'criado_por');
    }
}
