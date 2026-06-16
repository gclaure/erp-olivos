<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\ConsumptionRequest;

class SolicitudConsumoDespachadaNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ConsumptionRequest $consumptionRequest)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param object $notifiable
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param object $notifiable
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $number = $this->consumptionRequest->formatted_number;
        $requestedBy = $this->consumptionRequest->requested_by;
        $warehouseName = $this->consumptionRequest->warehouse->name ?? 'N/A';
        $statusLabel = match ($this->consumptionRequest->status) {
            'entregado' => 'ENTREGADO',
            'parcial' => 'DESPACHADO PARCIALMENTE',
            'despachado' => 'DESPACHADO',
            default => strtoupper((string) $this->consumptionRequest->status),
        };

        return [
            'type' => 'consumption_request_dispatched',
            'consumption_request_id' => $this->consumptionRequest->id,
            'warehouse_name' => $warehouseName,
            'requested_by' => $requestedBy,
            'message' => "Tu solicitud de consumo #{$number} del área {$requestedBy} ha sido despachada por almacén ({$statusLabel}).",
        ];
    }
}
