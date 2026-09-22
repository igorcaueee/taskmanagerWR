<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalUsuarioAcesso extends Model
{
    const UPDATED_AT = null;

    protected $table = 'portal_usuario_acessos';

    protected $fillable = [
        'portal_usuario_id',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function portalUsuario(): BelongsTo
    {
        return $this->belongsTo(PortalUsuario::class);
    }
}
