<?php

declare(strict_types=1);

namespace App\Events;

use App\Http\Resources\ConsumptionRequestResource;
use App\Models\ConsumptionRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConsumptionRequestUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ConsumptionRequest $consumptionRequest
    ) {
        $this->consumptionRequest->loadMissing('warehouse');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $branchId = $this->consumptionRequest->warehouse->branch_id ?? '';

        return [
            new PrivateChannel("sucursal.{$branchId}"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'consumption-request.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        // Cargar relaciones necesarias para que el recurso esté completo en el listado y detalle
        $this->consumptionRequest->load([
            'warehouse', 
            'user', 
            'receivedByUser', 
            'details.product.stocks', 
            'details.product.unitOfMeasure', 
            'details.product.categories'
        ]);

        return [
            'request' => (new ConsumptionRequestResource($this->consumptionRequest))->toArray(request()),
        ];
    }
}
