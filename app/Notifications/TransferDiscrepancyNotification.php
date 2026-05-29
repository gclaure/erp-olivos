<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Transfer;

class TransferDiscrepancyNotification extends Notification
{
    use Queueable;

    private Transfer $transfer;

    /**
     * Create a new notification instance.
     */
    public function __construct(Transfer $transfer)
    {
        $this->transfer = $transfer;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $originName = $this->transfer->originWarehouse->name ?? 'N/A';
        $destName = $this->transfer->destinationWarehouse->name ?? 'N/A';
        $branchName = $this->transfer->destinationWarehouse->branch->name ?? 'N/A';

        // Construir detalle de discrepancias
        $details = $this->transfer->details()->with('product')->get();
        $discrepancyItems = [];
        
        foreach ($details as $detail) {
            $sent = (float) $detail->quantity_sent;
            $received = (float) $detail->quantity_received;
            
            if (abs($sent - $received) > 0.0001) {
                $productName = $detail->product->name ?? 'Producto desconocido';
                $discrepancyItems[] = "{$productName} (Solicitado: " . number_format($sent, 2) . " / Recibido: " . number_format($received, 2) . ")";
            }
        }

        $discrepancyText = implode('; ', $discrepancyItems);

        return [
            'type' => 'transfer_discrepancy',
            'transfer_id' => $this->transfer->id,
            'transfer_number' => $this->transfer->number,
            'origin_warehouse' => $originName,
            'destination_warehouse' => $destName,
            'branch_name' => $branchName,
            'discrepancies' => $discrepancyItems,
            'message' => "¡DISCREPANCIA EN TRANSFERENCIA! La transferencia {$this->transfer->number} llegó con diferencias en {$destName} (Sucursal: {$branchName}). Detalles: {$discrepancyText}",
        ];
    }
}
