<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderComplementProviderEnum: string
{
    case Client = 'client';
    case Business = 'business';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Cliente',
            self::Business => 'Negocio',
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

