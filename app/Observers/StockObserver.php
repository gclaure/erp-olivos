<?php

namespace App\Observers;

use App\Models\Stock;
use App\Models\User;
use App\Notifications\LowStockNotification;
use App\Notifications\OutOfStockNotification;
use Illuminate\Support\Facades\Notification;
use App\Events\NuevaNotificacion;

class StockObserver
{
    /**
     * Permite silenciar notificaciones en procesos masivos (como importaciones)
     */
    public static bool $muteNotifications = false;

    /**
     * Evita que se manden dos notificaciones en el mismo proceso (request)
     */
    private static array $handled = [];

    /**
     * Handle the Stock "updated" event.
     */
    public function updated(Stock $stock): void
    {
        $this->checkStockLevels($stock);
    }

    /**
     * Handle the Stock "created" event.
     */
    public function created(Stock $stock): void
    {
        $this->checkStockLevels($stock);
    }

    private function checkStockLevels(Stock $stock): void
    {
        if (self::$muteNotifications) {
            return;
        }

        // BLOQUEO ESTRICTO: Si ya procesamos este stock en esta request, salir inmediatamente
        if (isset(self::$handled[$stock->id])) {
            return;
        }
        self::$handled[$stock->id] = true;

        $product = $stock->product;
        
        if ($product) {
            $qty = (float) $stock->quantity;
            $min = (float) $product->min_stock;

            // 1. Filtrar usuarios: Super Admins o de la sucursal, excluyendo a Consumidores
            $users = User::where(function ($query) use ($stock) {
                    $query->where('is_super_admin', true)
                        ->orWhere('branch_id', $stock->warehouse->branch_id);
                })
                ->whereDoesntHave('roles', function ($query) {
                    $query->whereIn('name', ['Consumidor', 'consumidor']);
                })
                ->get()
                ->unique('id');

            if ($users->isEmpty()) {
                return;
            }

            // 2. Determinar tipo de notificación
            if ($qty <= 0) {
                $mensaje = "¡SIN STOCK!: {$product->name} se ha agotado en {$stock->warehouse->name}.";
                
                try {
                    Notification::send($users, new OutOfStockNotification($product, $stock->warehouse));
                    
                    foreach ($users as $user) {
                        NuevaNotificacion::dispatch((string)$user->id, $mensaje, 'error');
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning("No se pudo enviar notificación de sin stock: " . $e->getMessage());
                }
            } elseif ($min > 0 && $qty <= $min) {
                $mensaje = "Stock Bajo: {$product->name} ( " . ((float)$qty) . " ) en {$stock->warehouse->name}.";
                
                try {
                    Notification::send($users, new LowStockNotification($product, $stock->warehouse, $qty));

                    foreach ($users as $user) {
                        NuevaNotificacion::dispatch((string)$user->id, $mensaje, 'warning');
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning("No se pudo enviar notificación de stock bajo: " . $e->getMessage());
                }
            }
        }
    }
}
