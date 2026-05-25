<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarefaUploadEvento extends Model
{
    public $timestamps = false;

    protected $table = 'tarefa_upload_eventos';

    protected $fillable = [
        'tarefa_upload_id',
        'portal_usuario_id',
        'tipo',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function upload(): BelongsTo
    {
        return $this->belongsTo(TarefaUpload::class, 'tarefa_upload_id');
    }

    public function portalUsuario(): BelongsTo
    {
        return $this->belongsTo(PortalUsuario::class, 'portal_usuario_id');
    }
}
