<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Enum;

enum ProjectPriority: string
{
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function getLabel(): string
    {
        return match ($this) {
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::CRITICAL => 'Critical',
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::MEDIUM => 'info',
            self::HIGH => 'primary',
            self::CRITICAL => 'danger',
        };
    }

    public function getSortOrder(): int
    {
        return match ($this) {
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::CRITICAL => 5,
        };
    }
}
