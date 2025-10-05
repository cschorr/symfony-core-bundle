<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use C3net\CoreBundle\ApiResource\AuditLog\ActionCollection;
use C3net\CoreBundle\Repository\AuditLogsRepository;

/**
 * @implements ProviderInterface<ActionCollection>
 */
class AuditLogActionsProvider implements ProviderInterface
{
    public function __construct(
        private readonly AuditLogsRepository $auditLogsRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $actions = $this->auditLogsRepository->findUniqueActions();

        // Extract just the action values
        $actionList = array_map(fn ($item) => $item['action'], $actions);

        return new ActionCollection($actionList);
    }
}
