<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Artigo extends Model
{
    protected $fillable = [
        'autor_id',
        'titulo',
        'slug',
        'resumo',
        'conteudo',
        'imagem_capa',
        'status',
        'publicado_em',
    ];

    protected $casts = [
        'publicado_em' => 'datetime',
    ];

    public function autor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'autor_id');
    }

    public function scopePublicados($query)
    {
        return $query->where('status', 'publicado')
            ->where('publicado_em', '<=', now());
    }

    public static function gerarSlug(string $titulo): string
    {
        $base = Str::slug($titulo);
        $slug = $base;
        $i = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
