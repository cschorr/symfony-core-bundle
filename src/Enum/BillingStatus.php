<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Enum;

enum BillingStatus: string
{
    case NOT_BILLED = 'not_billed';
    case BILLED = 'billed';
    case PAID = 'paid';

    public function getLabel(): string
    {
        return match ($this) {
            self::NOT_BILLED => 'Not Billed',
            self::BILLED => 'Billed',
            self::PAID => 'Paid',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::NOT_BILLED => 'secondary',
            self::BILLED => 'warning',
            self::PAID => 'success',
        };
    }
}
