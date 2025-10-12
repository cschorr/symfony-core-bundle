<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use C3net\CoreBundle\ApiResource\ProjectStatusCollection;
use C3net\CoreBundle\Enum\ProjectStatus;

/**
 * @implements ProviderInterface<ProjectStatusCollection>
 */
class ProjectStatusProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $items = [];

        foreach (ProjectStatus::cases() as $status) {
            $items[] = [
                'name' => $status->name,
                'value' => $status->value,
                'label' => $status->getLabel(),
                'badgeClass' => $status->getBadgeClass(),
            ];
        }

        return new ProjectStatusCollection($items);
    }
}
