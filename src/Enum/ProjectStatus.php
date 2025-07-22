<?php

namespace App\Enum;

enum ProjectStatus: int
{
    case PLANNING = 0;
    case IN_PROGRESS = 1;
    case ON_HOLD = 2;
    case COMPLETED = 3;
    case CANCELLED = 4;

    public function getLabel(): string
    {
        return match($this) {
            self::PLANNING => 'Planning',
            self::IN_PROGRESS => 'In Progress',
            self::ON_HOLD => 'On Hold',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getBadgeClass(): string
    {
        return match($this) {
            self::PLANNING => 'secondary',
            self::IN_PROGRESS => 'primary',
            self::ON_HOLD => 'warning',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }
}
