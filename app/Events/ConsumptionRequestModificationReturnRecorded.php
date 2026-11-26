<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ConsumptionRequestModification;
use App\Models\ConsumptionRequestModificationReturn;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConsumptionRequestModificationReturnRecorded implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ConsumptionRequestModification $modification,
        public readonly ConsumptionRequestModificationReturn $returnRecord,
        public readonly bool $isComplete
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
        return 'consumption-modification.return-recorded';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'modification_id' => $this->modification->id,
            'consumption_request_id' => $this->modification->consumption_request_id,
            'detail_id' => $this->modification->consumption_request_detail_id,
            'return_id' => $this->returnRecord->id,
            'returned_quantity' => (float) $this->returnRecord->returned_quantity,
            'is_complete' => $this->isComplete,
            'status' => $this->modification->status->value,
        ];
    }
}
