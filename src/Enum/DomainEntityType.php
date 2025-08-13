<?php

declare(strict_types=1);

namespace App\Enum;

enum DomainEntityType: string
{
    case Category = 'category';
    case Company = 'company';
    case CompanyGroup = 'company_group';
    case Contact = 'contact';
    case Notification = 'notification';
    case Project = 'project';
    case ProjectStatus = 'project_status';
    case Thread = 'thread';
    case User = 'user';
    case UserGroup = 'user_group';
}
