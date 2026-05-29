<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    private $product;
    private $warehouse;
    private $quantity;

    /**
     * Create a new notification instance.
     */
    public function __construct($product, $warehouse, $quantity)
    {
        $this->product = $product;
        $this->warehouse = $warehouse;
        $this->quantity = $quantity;
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

        $formattedQty = (float) $this->quantity;
        $formattedMin = (float) $this->product->min_stock;

        return [
            'type' => 'low_stock',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'warehouse_name' => $this->warehouse->name,
            'quantity' => $formattedQty,
            'min_stock' => $formattedMin,
            'message' => "El producto {$this->product->name} ha alcanzado el stock mínimo ({$formattedMin}) en el almacén {$this->warehouse->name} (Sucursal: {$branchName}). El stock actual es {$formattedQty}.",
        ];
    }
}
