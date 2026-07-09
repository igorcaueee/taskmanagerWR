<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class MensagemLida implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public int $conversaId, public int $usuarioId, public ?int $ultimaMensagemLidaId) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversa.'.$this->conversaId)];
    }

    public function broadcastAs(): string
    {
        return 'mensagem.lida';
    }

    public function broadcastWith(): array
    {
        return [
            'conversa_id' => $this->conversaId,
            'usuario_id' => $this->usuarioId,
            'ultima_mensagem_lida_id' => $this->ultimaMensagemLidaId,
        ];
    }
}
