<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mensagem extends Model
{
    use HasFactory;

    protected $table = 'mensagens';

    protected $fillable = [
        'conversa_id',
        'usuario_id',
        'texto',
        'editada',
        'editada_em',
        'deletada_em',
    ];

    protected $casts = [
        'editada' => 'boolean',
        'editada_em' => 'datetime',
        'deletada_em' => 'datetime',
    ];

    public function conversa(): BelongsTo
    {
        return $this->belongsTo(Conversa::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(MensagemAnexo::class);
    }
}
