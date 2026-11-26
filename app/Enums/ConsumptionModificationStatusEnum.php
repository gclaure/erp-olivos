<?php

declare(strict_types=1);

namespace App\Enums;

enum ConsumptionModificationStatusEnum: string
{
    case Pending = 'pendiente';
    case Approved = 'aprobada';
    case Applied = 'aplicada';
    case Rejected = 'rechazada';
    case Cancelled = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente de aprobación',
            self::Approved => 'Aprobada (esperando devolución)',
            self::Applied => 'Aplicada',
            self::Rejected => 'Rechazada',
            self::Cancelled => 'Cancelada',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Approved], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Applied, self::Rejected, self::Cancelled], true);
    }

    /**
     * @return array<int, string>
     */
    public static function openValues(): array
    {
        return [
            self::Pending->value,
            self::Approved->value,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function terminalValues(): array
    {
        return [
            self::Applied->value,
            self::Rejected->value,
            self::Cancelled->value,
        ];
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
