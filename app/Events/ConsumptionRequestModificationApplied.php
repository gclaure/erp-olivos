<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ConsumptionRequestModification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConsumptionRequestModificationApplied implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ConsumptionRequestModification $modification
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $this->modification->loadMissing('consumptionRequest.warehouse');
        $branchId = $this->modification->consumptionRequest?->warehouse?->branch_id ?? '';

        return [
            new PrivateChannel("sucursal.{$branchId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'consumption-modification.applied';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->modification->loadMissing(['fromProduct', 'toProduct']);

        return [
            'modification_id' => $this->modification->id,
            'consumption_request_id' => $this->modification->consumption_request_id,
            'detail_id' => $this->modification->consumption_request_detail_id,
            'from_product_name' => $this->modification->fromProduct?->name,
            'to_product_name' => $this->modification->toProduct?->name,
            'requested_quantity' => (float) $this->modification->requested_quantity,
            'status' => $this->modification->status->value,
        ];
    }
}
