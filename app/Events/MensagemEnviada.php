<?php

namespace App\Events;

use App\Models\Mensagem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class MensagemEnviada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public Mensagem $mensagem, public array $participanteIds)
    {
        $this->mensagem->loadMissing(['usuario:id,nome,foto', 'anexos']);
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $canais = [new PrivateChannel('conversa.'.$this->mensagem->conversa_id)];

        foreach ($this->participanteIds as $usuarioId) {
            $canais[] = new PrivateChannel('usuario.'.$usuarioId);
        }

        return $canais;
    }

    public function broadcastAs(): string
    {
        return 'mensagem.enviada';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'mensagem' => [
                'id' => $this->mensagem->id,
                'conversa_id' => $this->mensagem->conversa_id,
                'texto' => $this->mensagem->texto,
                'usuario' => [
                    'id' => $this->mensagem->usuario->id,
                    'nome' => $this->mensagem->usuario->nome,
                    'foto_url' => $this->mensagem->usuario->foto_url,
                ],
                'anexos' => $this->mensagem->anexos->map(fn ($anexo) => [
                    'id' => $anexo->id,
                    'url' => $anexo->url,
                    'nome_original' => $anexo->nome_original,
                    'tipo' => $anexo->tipo,
                ]),
                'created_at' => $this->mensagem->created_at->toIso8601String(),
            ],
        ];
    }
}
