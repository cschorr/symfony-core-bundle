<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Enum;

enum ProjectPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';
    case CRITICAL = 'critical';

    public function getLabel(): string
    {
        return match ($this) {
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::URGENT => 'Urgent',
            self::CRITICAL => 'Critical',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::LOW => 'secondary',
            self::MEDIUM => 'info',
            self::HIGH => 'primary',
            self::URGENT => 'warning',
            self::CRITICAL => 'danger',
        };
    }

    public function getSortOrder(): int
    {
        return match ($this) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::URGENT => 4,
            self::CRITICAL => 5,
        };
    }
}
