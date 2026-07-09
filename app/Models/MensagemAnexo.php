<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MensagemAnexo extends Model
{
    use HasFactory;

    protected $table = 'mensagem_anexos';

    protected $fillable = [
        'mensagem_id',
        'caminho',
        'nome_original',
        'mime_type',
        'tamanho',
        'tipo',
    ];

    public function mensagem(): BelongsTo
    {
        return $this->belongsTo(Mensagem::class);
    }

    public function getUrlAttribute(): ?string
    {
        return route('chat.anexo.download', $this);
    }
}
