<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentType: string
{
    case CI = 'CI';
    case NIT = 'NIT';
    case PASSPORT = 'PASAPORTE';
    case OTHER = 'OTRO';

    public function label(): string
    {
        return match ($this) {
            self::CI => 'Cédula de Identidad',
            self::NIT => 'NIT',
            self::PASSPORT => 'Pasaporte',
            self::OTHER => 'Otro',
        };
    }
}
