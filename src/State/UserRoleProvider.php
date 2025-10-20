<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use C3net\CoreBundle\ApiResource\UserRoleCollection;
use C3net\CoreBundle\Enum\UserRole;

/**
 * @implements ProviderInterface<UserRoleCollection>
 */
class UserRoleProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $items = [];

        foreach (UserRole::cases() as $role) {
            $items[] = [
                'name' => $role->name,
                'value' => $role->value,
            ];
        }

        return new UserRoleCollection($items);
    }
}
