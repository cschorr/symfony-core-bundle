<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use C3net\CoreBundle\ApiResource\AuditLog\ResourceCollection;
use C3net\CoreBundle\Repository\AuditLogsRepository;

/**
 * @implements ProviderInterface<ResourceCollection>
 */
class AuditLogResourcesProvider implements ProviderInterface
{
    public function __construct(
        private readonly AuditLogsRepository $auditLogsRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $resources = $this->auditLogsRepository->findUniqueResources();

        // Extract just the resource values
        $resourceList = array_map(fn ($item) => $item['resource'], $resources);

        return new ResourceCollection($resourceList);
    }
}
