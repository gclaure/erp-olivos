<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NuevaNotificacion implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Usamos ShouldBroadcastNow para que sea instantáneo y no dependa de colas
     * durante las pruebas iniciales.
     */
    public function __construct(
        public readonly string $userId,
        public readonly string $mensaje,
        public readonly string $tipo = 'info'
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("notificaciones.{$this->userId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'nueva.notificacion';
    }

    public function broadcastWith(): array
    {
        return [
            'mensaje' => $this->mensaje,
            'tipo' => $this->tipo,
        ];
    }
}
