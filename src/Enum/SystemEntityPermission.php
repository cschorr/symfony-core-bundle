<?php

namespace App\Enum;

enum SystemEntityPermission: string
{
    case READ = 'read';
    case WRITE = 'write';
    case EDIT = 'edit';
    case DELETE = 'delete';
}
