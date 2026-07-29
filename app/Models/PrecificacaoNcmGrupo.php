<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrecificacaoNcmGrupo extends Model
{
    protected $table = 'precificacao_ncm_grupos';

    protected $fillable = [
        'nome',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function itens(): HasMany
    {
        return $this->hasMany(PrecificacaoNcmGrupoItem::class);
    }

    public function contemNcm(string $ncm): bool
    {
        return $this->itens->contains(fn (PrecificacaoNcmGrupoItem $item) => str_starts_with($ncm, $item->ncm));
    }
}
