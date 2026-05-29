<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Purchase;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Purchase $purchase) {}
}
