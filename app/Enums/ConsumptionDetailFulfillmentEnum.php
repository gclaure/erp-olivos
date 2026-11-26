<?php

declare(strict_types=1);

namespace App\Enums;

enum ConsumptionDetailFulfillmentEnum: string
{
    case Normal = 'normal';
    case PendingStock = 'pending_stock';
    case PendingReturn = 'pending_return';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::PendingStock => 'Sin existencias',
            self::PendingReturn => 'Devolución pendiente',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases()
        );
    }
}
