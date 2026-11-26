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

class ConsumptionRequestModificationResolved implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly ConsumptionRequestModification $modification,
        public readonly string $resolutionAction
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $this->modification->loadMissing('consumptionRequest.warehouse');
        $branchId = $this->modification->consumptionRequest?->warehouse?->branch_id ?? '';

        $channels = [
            new PrivateChannel("sucursal.{$branchId}"),
        ];

        if ($this->modification->requested_by_user_id) {
            $channels[] = new PrivateChannel("notificaciones.{$this->modification->requested_by_user_id}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'consumption-modification.resolved';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->modification->loadMissing([
            'consumptionRequest',
            'fromProduct',
            'toProduct',
            'resolvedByUser',
            'cancelledByUser',
        ]);

        return [
            'modification_id' => $this->modification->id,
            'consumption_request_id' => $this->modification->consumption_request_id,
            'consumption_request_number' => $this->modification->consumptionRequest?->formatted_number,
            'detail_id' => $this->modification->consumption_request_detail_id,
            'action' => $this->resolutionAction,
            'status' => $this->modification->status->value,
            'from_product_name' => $this->modification->fromProduct?->name,
            'to_product_name' => $this->modification->toProduct?->name,
            'resolved_by' => $this->modification->cancelledByUser?->name ?? $this->modification->resolvedByUser?->name,
        ];
    }
}
