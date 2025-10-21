<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use C3net\CoreBundle\ApiResource\AuditLog\AuthorSummary;
use C3net\CoreBundle\ApiResource\AuditLog\FilterOptions;
use C3net\CoreBundle\Repository\AuditLogsRepository;

/**
 * @implements ProviderInterface<FilterOptions>
 */
class AuditLogFiltersProvider implements ProviderInterface
{
    public function __construct(
        private readonly AuditLogsRepository $auditLogsRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $authors = $this->auditLogsRepository->findUniqueAuthors();
        $resources = $this->auditLogsRepository->findUniqueResources();
        $actions = $this->auditLogsRepository->findUniqueActions();

        // Map authors to AuthorSummary value objects
        $authorSummaries = array_map(fn($author) => new AuthorSummary(
            id: (string) $author['id'],
            email: $author['email'],
            firstname: $author['firstname'],
            lastname: $author['lastname'],
            fullname: trim(($author['firstname'] ?? '') . ' ' . ($author['lastname'] ?? '')),
        ), $authors);

        // Extract resources and actions
        $resourceList = array_map(fn ($item) => $item['resource'], $resources);
        $actionList = array_map(fn ($item) => $item['action'], $actions);

        return new FilterOptions(
            authors: $authorSummaries,
            resources: $resourceList,
            actions: $actionList,
        );
    }
}
