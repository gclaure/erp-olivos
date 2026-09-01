<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\ConsumptionRequest;

class SolicitudConsumoCanceladaNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ConsumptionRequest $consumptionRequest,
        private readonly ?string $reason = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $number = $this->consumptionRequest->formatted_number;
        $requestedBy = $this->consumptionRequest->requested_by;
        $warehouseName = $this->consumptionRequest->warehouse->name ?? 'N/A';
        $reasonText = $this->reason ? " Motivo: {$this->reason}" : '';

        return [
            'type' => 'consumption_request_cancelled',
            'consumption_request_id' => $this->consumptionRequest->id,
            'warehouse_name' => $warehouseName,
            'requested_by' => $requestedBy,
            'message' => "La solicitud de consumo #{$number} del área {$requestedBy} ha sido cancelada por el Administrador.{$reasonText}",
        ];
    }
}
