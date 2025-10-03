<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Enum;

enum PaymentStatus: string
{
    case UNPAID = 'unpaid';
    case PARTIAL = 'partial';
    case PAID = 'paid';
    case OVERDUE = 'overdue';

    public function getLabel(): string
    {
        return match ($this) {
            self::UNPAID => 'Unpaid',
            self::PARTIAL => 'Partially Paid',
            self::PAID => 'Paid',
            self::OVERDUE => 'Overdue',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::UNPAID => 'secondary',
            self::PARTIAL => 'warning',
            self::PAID => 'success',
            self::OVERDUE => 'danger',
        };
    }
}
