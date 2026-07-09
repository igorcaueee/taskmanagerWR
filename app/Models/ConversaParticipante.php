<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversaParticipante extends Model
{
    use HasFactory;

    protected $table = 'conversa_participantes';

    protected $fillable = [
        'conversa_id',
        'usuario_id',
        'ultima_mensagem_lida_id',
        'lida_em',
        'is_admin',
    ];

    protected $casts = [
        'lida_em' => 'datetime',
        'is_admin' => 'boolean',
    ];

    public function conversa(): BelongsTo
    {
        return $this->belongsTo(Conversa::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function ultimaMensagemLida(): BelongsTo
    {
        return $this->belongsTo(Mensagem::class, 'ultima_mensagem_lida_id');
    }
}
