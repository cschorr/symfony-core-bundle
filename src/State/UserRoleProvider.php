<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\UserRoleCollection;
use App\Enum\UserRole;

class UserRoleProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $roles = [];
        
        foreach (UserRole::cases() as $role) {
            $roles[] = [
                'name' => $role->name,
                'value' => $role->value,
            ];
        }

        return new UserRoleCollection($roles);
    }
}