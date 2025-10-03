<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Enum;

enum InvoiceType: string
{
    case FULL = 'full';
    case PARTIAL = 'partial';
    case DEPOSIT = 'deposit';
    case FINAL = 'final';
    case CREDIT_NOTE = 'credit_note';

    public function getLabel(): string
    {
        return match ($this) {
            self::FULL => 'Full Invoice',
            self::PARTIAL => 'Partial Invoice',
            self::DEPOSIT => 'Deposit',
            self::FINAL => 'Final Invoice',
            self::CREDIT_NOTE => 'Credit Note',
        };
    }
}
