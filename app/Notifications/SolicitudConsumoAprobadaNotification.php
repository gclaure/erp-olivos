<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\ConsumptionRequest;

class SolicitudConsumoAprobadaNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ConsumptionRequest $consumptionRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $number = $this->consumptionRequest->formatted_number;
        $requestedBy = $this->consumptionRequest->requested_by;
        $warehouseName = $this->consumptionRequest->warehouse->name ?? 'N/A';

        return [
            'type' => 'consumption_request_approved',
            'consumption_request_id' => $this->consumptionRequest->id,
            'warehouse_name' => $warehouseName,
            'requested_by' => $requestedBy,
            'message' => "Tu solicitud de consumo #{$number} del área {$requestedBy} ha sido aprobada por el Administrador y está lista para ser despachada.",
        ];
    }
}
