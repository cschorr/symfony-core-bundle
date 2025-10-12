<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use C3net\CoreBundle\ApiResource\ProjectPriorityCollection;
use C3net\CoreBundle\Enum\ProjectPriority;

/**
 * @implements ProviderInterface<ProjectPriorityCollection>
 */
class ProjectPriorityProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $items = [];

        foreach (ProjectPriority::cases() as $priority) {
            $items[] = [
                'name' => $priority->name,
                'value' => $priority->value,
                'label' => $priority->getLabel(),
                'badgeClass' => $priority->getBadgeClass(),
                'sortOrder' => $priority->getSortOrder(),
            ];
        }

        return new ProjectPriorityCollection($items);
    }
}
