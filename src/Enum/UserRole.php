<?php

declare(strict_types=1);

namespace App\Enum;

enum UserRole: string
{
    case ROLE_USER = 'ROLE_USER';
    case ROLE_ADMIN = 'ROLE_ADMIN';
    case ROLE_MODERATOR = 'ROLE_MODERATOR';
    case ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    case ROLE_ALLOWED_TO_SWITCH = 'ROLE_ALLOWED_TO_SWITCH';

    case ROLE_MANAGER = 'ROLE_MANAGER';
    case ROLE_TEAMLEAD = 'ROLE_TEAMLEAD';
    case ROLE_FINANCE = 'ROLE_FINANCE';
    case ROLE_QUALITY = 'ROLE_QUALITY';
    case ROLE_PROJECT_MANAGEMENT = 'ROLE_PROJECT_MANAGEMENT';
    case ROLE_EDITOR = 'ROLE_EDITOR';
    case ROLE_EXTERNAL = 'ROLE_EXTERNAL';

    // Custom roles used in fixtures or features
    case ROLE_CONTENT_CREATOR = 'ROLE_CONTENT_CREATOR';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $r) => $r->value, self::cases());
    }
}
