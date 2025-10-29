<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Enum;

enum TransactionStatus: string
{
    case DRAFT = 'draft';
    case QUOTED = 'quoted';
    case ORDERED = 'ordered';
    case IN_PRODUCTION = 'in_production';
    case DELIVERED = 'delivered';
    case INVOICED = 'invoiced';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
    case DECLINED = 'declined';
    case ARCHIVED = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::QUOTED => 'Quoted',
            self::ORDERED => 'Ordered',
            self::IN_PRODUCTION => 'In Production',
            self::DELIVERED => 'Delivered',
            self::INVOICED => 'Invoiced',
            self::PAID => 'Paid',
            self::CANCELLED => 'Cancelled',
            self::DECLINED => 'Declined',
            self::ARCHIVED => 'Archived',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::QUOTED => 'info',
            self::ORDERED => 'primary',
            self::IN_PRODUCTION => 'warning',
            self::DELIVERED => 'success',
            self::INVOICED => 'info',
            self::PAID => 'success',
            self::CANCELLED => 'danger',
            self::DECLINED => 'danger',
            self::ARCHIVED => 'secondary',
        };
    }
}
