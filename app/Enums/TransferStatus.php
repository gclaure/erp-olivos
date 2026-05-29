<?php

declare(strict_types=1);

namespace App\Enums;

enum TransferStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';           // Pendiente de Aprobación
    case APPROVED = 'approved';         // Aprobada
    case REJECTED = 'rejected';         // Rechazada por el destino
    case PICKING = 'picking';           // En Preparación (Opcional, antes de despacho)
    case IN_TRANSIT = 'in_transit';     // Despachada / En Tránsito
    case COMPLETED = 'completed';       // Recepcionada / Finalizada
    case CANCELLED = 'cancelled';       // Cancelada por el origen

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Borrador',
            self::PENDING => 'Pendiente de Aprobación',
            self::APPROVED => 'Aprobada',
            self::REJECTED => 'Rechazada',
            self::PICKING => 'En Preparación',
            self::IN_TRANSIT => 'En Tránsito (Despachada)',
            self::COMPLETED => 'Completada',
            self::CANCELLED => 'Cancelada (Origen)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::PENDING => 'warning',
            self::APPROVED => 'emerald',
            self::REJECTED => 'negative',
            self::PICKING => 'info',
            self::IN_TRANSIT => 'primary',
            self::COMPLETED => 'slate',
            self::CANCELLED => 'negative',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'document-text',
            self::PENDING => 'clock',
            self::APPROVED => 'check-circle',
            self::REJECTED => 'x-circle',
            self::PICKING => 'shopping-cart',
            self::IN_TRANSIT => 'truck',
            self::COMPLETED => 'lock-closed',
            self::CANCELLED => 'archive-box-x-mark',
        };
    }
}
