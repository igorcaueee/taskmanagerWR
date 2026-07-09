<?php

use App\Models\ConversaParticipante;
use App\Models\Usuario;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('conversa.{conversaId}', function (Usuario $usuario, int $conversaId) {
    if (! ConversaParticipante::where('conversa_id', $conversaId)->where('usuario_id', $usuario->id)->exists()) {
        return false;
    }

    return ['id' => $usuario->id, 'nome' => $usuario->nome, 'foto_url' => $usuario->foto_url];
});

Broadcast::channel('usuario.{usuarioId}', function (Usuario $usuario, int $usuarioId) {
    return (int) $usuario->id === $usuarioId;
});
