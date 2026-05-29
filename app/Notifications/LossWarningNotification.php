<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Product;
use App\Models\Warehouse;

class LossWarningNotification extends Notification
{
    use Queueable;

    private Product $product;
    private Warehouse $warehouse;
    private float $purchasePrice;
    private float $sellingPrice;

    /**
     * Create a new notification instance.
     */
    public function __construct(Product $product, Warehouse $warehouse, float $purchasePrice, float $sellingPrice)
    {
        $this->product = $product;
        $this->warehouse = $warehouse;
        $this->purchasePrice = $purchasePrice;
        $this->sellingPrice = $sellingPrice;
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
            'type' => 'loss_warning',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'warehouse_name' => $this->warehouse->name,
            'purchase_price' => $this->purchasePrice,
            'selling_price' => $this->sellingPrice,
            'message' => "¡ALERTA DE PÉRDIDA! El producto {$this->product->name} se compró a {$this->purchasePrice} pero su precio de venta es {$this->sellingPrice} en el almacén {$this->warehouse->name} (Sucursal: {$branchName}).",
        ];
    }
}
