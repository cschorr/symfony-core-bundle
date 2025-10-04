<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Enum;

enum TransactionType: string
{
    case QUOTE = 'quote';
    case ORDER = 'order';
    case SERVICE = 'service';
    case RETAINER = 'retainer';
    case PROJECT = 'project';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::QUOTE => 'Quote',
            self::ORDER => 'Order',
            self::SERVICE => 'Service',
            self::RETAINER => 'Retainer',
            self::PROJECT => 'Project',
            self::OTHER => 'Other',
        };
    }
}
