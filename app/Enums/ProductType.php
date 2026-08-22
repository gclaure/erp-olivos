<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductType: string
{
    case RAW_MATERIAL = 'materia_prima';
    case SUPPLY = 'insumo';

    public function label(): string
    {
        return match ($this) {
            self::RAW_MATERIAL => 'Materia Prima',
            self::SUPPLY => 'Insumo',
        };
    }

    public function isInventoriable(): bool
    {
        return $this === self::RAW_MATERIAL;
    }

    public function requiresKardex(): bool
    {
        return $this === self::RAW_MATERIAL;
    }

    public function requiresMinStock(): bool
    {
        return $this === self::RAW_MATERIAL;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
