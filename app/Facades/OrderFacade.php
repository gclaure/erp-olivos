<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Models\Order createOrder(array $data, array $files = [], ?\App\Models\User $user = null)
 * @method static \App\Models\Order cancelOrder(string $orderId, ?string $reason, string $userId)
 * @method static \App\Models\OrderPayment addPayment(\App\Models\Order $order, array $data, ?\App\Models\User $user = null)
 * @method static array{file_path: string, file_url: string, original_name: string} processAndStoreAttachmentImage(\Illuminate\Http\UploadedFile $file)
 *
 * @see \App\Services\OrderService
 */
class OrderFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'order.service';
    }
}

