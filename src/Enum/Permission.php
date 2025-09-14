<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Enum;

enum Permission: string
{
    case READ = 'read';
    case WRITE = 'write';
    case EDIT = 'edit';
    case DELETE = 'delete';
}
