<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Enum;

enum DomainEntityType: string
{
    case Campaign = 'campaign';
    case Category = 'category';
    case Company = 'company';
    case CompanyGroup = 'company_group';
    case Contact = 'contact';
    case Document = 'document';
    case Invoice = 'invoice';
    case Notification = 'notification';
    case Offer = 'offer';
    case Project = 'project';
    case ProjectStatus = 'project_status';
    case Thread = 'thread';
    case Transaction = 'transaction';
    case User = 'user';
    case UserGroup = 'user_group';
}
