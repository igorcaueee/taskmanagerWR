<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Conversa extends Model
{
    use HasFactory;

    protected $table = 'conversas';

    protected $fillable = [
        'tipo',
        'nome',
        'foto',
        'criado_por',
    ];

    public function criador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'criado_por');
    }

    public function participantes(): HasMany
    {
        return $this->hasMany(ConversaParticipante::class);
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'conversa_participantes')
            ->withPivot(['ultima_mensagem_lida_id', 'lida_em', 'is_admin'])
            ->withTimestamps();
    }

    public function mensagens(): HasMany
    {
        return $this->hasMany(Mensagem::class);
    }

    public function ultimaMensagem(): HasOne
    {
        return $this->hasOne(Mensagem::class)->latestOfMany();
    }

    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? Storage::disk('public')->url($this->foto) : null;
    }

    public function nomeParaUsuario(Usuario $usuario): string
    {
        if ($this->tipo === 'grupo') {
            return (string) $this->nome;
        }

        $outro = $this->usuarios->firstWhere('id', '!=', $usuario->id);

        return $outro?->nome ?? (string) $this->nome;
    }

    public function fotoUrlParaUsuario(Usuario $usuario): ?string
    {
        if ($this->tipo === 'grupo') {
            return $this->foto_url;
        }

        $outro = $this->usuarios->firstWhere('id', '!=', $usuario->id);

        return $outro?->foto_url;
    }

    public function naoLidasPara(Usuario $usuario): int
    {
        $participante = $this->participantes->firstWhere('usuario_id', $usuario->id);

        if (! $participante) {
            return 0;
        }

        $query = $this->mensagens()->where('usuario_id', '!=', $usuario->id);

        if ($participante->ultima_mensagem_lida_id) {
            $query->where('id', '>', $participante->ultima_mensagem_lida_id);
        }

        return $query->count();
    }
}
