<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\ConsumptionRequest;

class SolicitudConsumoRecepcionadaNotification extends Notification
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
     * Obtener el mensaje detallado de confirmación de recepción.
     *
     * @return string
     */
    public function getMessage(): string
    {
        $this->consumptionRequest->loadMissing(['receivedByUser', 'details.product.unitOfMeasure']);
        
        $number = $this->consumptionRequest->formatted_number;
        $requestedBy = $this->consumptionRequest->requested_by;
        $userName = $this->consumptionRequest->receivedByUser->name ?? 'Usuario';

        $discrepancies = [];
        foreach ($this->consumptionRequest->details as $detail) {
            $received = (float)$detail->quantity_received;
            $requested = (float)$detail->quantity_requested;
            $unitName = $detail->product->unitOfMeasure->name ?? '';

            $diff = $received - $requested;
            if (abs($diff) >= 0.01) {
                $productName = $detail->product->name ?? 'Producto';
                if ($diff < 0) {
                    $discrepancies[] = "{$productName} (Faltó: " . abs($diff) . " {$unitName})";
                } else {
                    $discrepancies[] = "{$productName} (Entregado de más: " . abs($diff) . " {$unitName})";
                }
            }
        }

        $message = "El usuario {$userName} ha confirmado la recepción de la solicitud de consumo #{$number} por el área de {$requestedBy}.";
        if (!empty($discrepancies)) {
            $message .= " Con discrepancias: " . implode(', ', $discrepancies) . ".";
        } else {
            $message .= " Todo fue recibido conforme.";
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param object $notifiable
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $warehouseName = $this->consumptionRequest->warehouse->name ?? 'N/A';
        $requestedBy = $this->consumptionRequest->requested_by;

        return [
            'type' => 'consumption_request_received',
            'consumption_request_id' => $this->consumptionRequest->id,
            'warehouse_name' => $warehouseName,
            'requested_by' => $requestedBy,
            'message' => $this->getMessage(),
        ];
    }
}
