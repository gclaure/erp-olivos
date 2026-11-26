<?php

declare(strict_types=1);

namespace App\Facades;

use App\Models\ConsumptionRequest;
use App\Models\ConsumptionRequestDetail;
use App\Models\ConsumptionRequestModification;
use App\Models\ConsumptionRequestModificationReturn;
use App\Services\ConsumptionRequestModificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ConsumptionRequestModification request(ConsumptionRequest $consumptionRequest, ConsumptionRequestDetail $detail, string $toProductId, float|int|string $requestedQuantity, string $reason, string $requestedByUserId, float|int|string|null $quantityPrepared = null, float|int|string|null $quantityPhysicallyDelivered = null)
 * @method static ConsumptionRequestModification approve(ConsumptionRequestModification $modification, string $resolvedByUserId, ?string $resolutionNotes = null, array $purchaseResolutions = [])
 * @method static ConsumptionRequestModification reject(ConsumptionRequestModification $modification, string $resolvedByUserId, string $resolutionNotes)
 * @method static ConsumptionRequestModificationReturn confirmReturn(ConsumptionRequestModification $modification, float|int|string $returnedQuantity, string $returnedByUserId, ?string $returnNotes = null)
 * @method static ConsumptionRequestModification cancel(ConsumptionRequestModification $modification, string $cancelledByUserId, string $cancellationReason)
 * @method static Collection getOpenPurchaseLines(string $consumptionRequestDetailId, string $productId, bool $lock = false)
 *
 * @see ConsumptionRequestModificationService
 */
class ConsumptionModificationFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'consumption.modification.service';
    }
}
