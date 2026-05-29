<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Movement;

class InventoryDiscrepancyNotification extends Notification
{
    use Queueable;

    private Movement $movement;

    /**
     * Create a new notification instance.
     */
    public function __construct(Movement $movement)
    {
        $this->movement = $movement;
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
        $warehouseName = $this->movement->warehouse->name ?? 'N/A';
        $branchName = $this->movement->warehouse->branch->name ?? 'N/A';
        $userName = $this->movement->user->name ?? 'Sistema';
        $typeLabel = $this->movement->type === 'entrada' ? 'ENTRADA' : 'SALIDA';

        // Construir detalle de productos
        $details = $this->movement->details()->with('product')->get();
        $items = [];
        foreach ($details as $detail) {
            $productName = $detail->product->name ?? 'Desconocido';
            $qty = number_format((float)$detail->quantity, 2);
            $items[] = "{$productName} ({$qty})";
        }
        $itemsText = implode('; ', $items);

        return [
            'type' => 'inventory_discrepancy',
            'movement_id' => $this->movement->id,
            'warehouse_name' => $warehouseName,
            'branch_name' => $branchName,
            'user_name' => $userName,
            'reason' => $this->movement->reason,
            'movement_type' => $this->movement->type,
            'items' => $items,
            'message' => "Ajuste de Inventario: El usuario {$userName} registró una {$typeLabel} en {$warehouseName} (Sucursal: {$branchName}) por motivo: '{$this->movement->reason}'. Detalles: {$itemsText}",
        ];
    }
}
