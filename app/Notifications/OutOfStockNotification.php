<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OutOfStockNotification extends Notification
{
    use Queueable;

    private $product;
    private $warehouse;

    /**
     * Create a new notification instance.
     */
    public function __construct($product, $warehouse)
    {
        $this->product = $product;
        $this->warehouse = $warehouse;
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
        $branchName = $this->warehouse->branch->name ?? 'N/A';

        return [
            'type' => 'out_of_stock',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'warehouse_name' => $this->warehouse->name,
            'message' => "¡ALERTA CRÍTICA! El producto {$this->product->name} se ha AGOTADO completamente en el almacén {$this->warehouse->name} (Sucursal: {$branchName}).",
        ];
    }
}
