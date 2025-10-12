<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use C3net\CoreBundle\ApiResource\PermissionCollection;
use C3net\CoreBundle\Enum\Permission;

/**
 * @implements ProviderInterface<PermissionCollection>
 */
class PermissionProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $items = [];

        foreach (Permission::cases() as $permission) {
            $items[] = [
                'name' => $permission->name,
                'value' => $permission->value,
            ];
        }

        return new PermissionCollection($items);
    }
}
