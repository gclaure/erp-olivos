<?php

declare(strict_types=1);

namespace App\Enums;

enum ExtensionType: string
{
    case GIFT = 'GIFT';
    case PAYMENT = 'PAYMENT';
    case PROMO = 'PROMO';
    case SUPPORT = 'SUPPORT';
    case COMPENSATION = 'COMPENSATION';

    public function label(): string
    {
        return match ($this) {
            self::GIFT => 'Regalo',
            self::PAYMENT => 'Pago',
            self::PROMO => 'Promoción',
            self::SUPPORT => 'Soporte',
            self::COMPENSATION => 'Compensación',
        };
    }
}
