<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use C3net\CoreBundle\ApiResource\DomainEntityTypeCollection;
use C3net\CoreBundle\Enum\DomainEntityType;

/**
 * @implements ProviderInterface<DomainEntityTypeCollection>
 */
class DomainEntityTypeProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $items = [];

        foreach (DomainEntityType::cases() as $type) {
            $items[] = [
                'name' => $type->name,
                'value' => $type->value,
            ];
        }

        return new DomainEntityTypeCollection($items);
    }
}
