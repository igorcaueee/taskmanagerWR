<?php

namespace App\Events;

use App\Models\Usuario;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class UsuarioDigitando implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public Usuario $usuario, public int $conversaId, public bool $digitando) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversa.'.$this->conversaId)];
    }

    public function broadcastAs(): string
    {
        return 'usuario.digitando';
    }

    public function broadcastWith(): array
    {
        return [
            'usuario_id' => $this->usuario->id,
            'nome' => $this->usuario->nome,
            'digitando' => $this->digitando,
        ];
    }
}
