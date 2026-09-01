<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\ConsumptionRequest;

class SolicitudConsumoModificadaNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ConsumptionRequest $consumptionRequest,
        private readonly string $productName,
        private readonly float $oldQty,
        private readonly float $newQty,
        private readonly string $unitName,
        private readonly ?string $notes = null
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
        $notesText = $this->notes ? " Nota: {$this->notes}" : '';

        return [
            'type' => 'consumption_request_modified',
            'consumption_request_id' => $this->consumptionRequest->id,
            'warehouse_name' => $warehouseName,
            'requested_by' => $requestedBy,
            'product_name' => $this->productName,
            'old_quantity' => $this->oldQty,
            'new_quantity' => $this->newQty,
            'unit_name' => $this->unitName,
            'message' => "El Administrador modificó la cantidad solicitada de '{$this->productName}' a {$this->newQty} {$this->unitName} en la solicitud #{$number} ({$requestedBy}).{$notesText}",
        ];
    }
}
