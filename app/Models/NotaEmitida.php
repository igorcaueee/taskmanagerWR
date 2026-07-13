<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaEmitida extends Model
{
    protected $table = 'notas_emitidas';

    protected $fillable = [
        'emitente_id',
        'usuario_id',
        'estornado',
    ];

    protected $casts = [
        'estornado' => 'boolean',
    ];

    public function emitente(): BelongsTo
    {
        return $this->belongsTo(NotaEmitente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
