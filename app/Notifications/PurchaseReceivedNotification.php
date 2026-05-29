<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Purchase;

class PurchaseReceivedNotification extends Notification
{
    use Queueable;

    private Purchase $purchase;

    /**
     * Create a new notification instance.
     */
    public function __construct(Purchase $purchase)
    {
        $this->purchase = $purchase;
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
        $branchName = $this->purchase->warehouse->branch->name ?? 'N/A';
        $providerName = $this->purchase->provider->name ?? 'N/A';

        return [
            'type' => 'purchase_received',
            'purchase_id' => $this->purchase->id,
            'purchase_number' => $this->purchase->purchase_number,
            'warehouse_name' => $this->purchase->warehouse->name,
            'branch_name' => $branchName,
            'provider_name' => $providerName,
            'total' => $this->purchase->total,
            'message' => "Nueva recepción de compra {$this->purchase->purchase_number} registrada en el almacén {$this->purchase->warehouse->name} (Sucursal: {$branchName}). Proveedor: {$providerName}.",
        ];
    }
}
