<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TentativaAcessoBloqueada extends Model
{
    use HasFactory;

    protected $table = 'tentativas_acesso_bloqueadas';

    protected $fillable = [
        'usuario_id',
        'ip',
        'url',
        'user_agent',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
