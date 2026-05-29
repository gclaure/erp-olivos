<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExtensionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionExtension extends Model
{
    use HasUuids;

    protected $fillable = [
        'subscription_id',
        'days_added',
        'type',
        'reason',
        'added_by',
    ];

    protected $casts = [
        'days_added' => 'integer',
        'type' => ExtensionType::class,
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
