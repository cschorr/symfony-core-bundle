<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use C3net\CoreBundle\ApiResource\AuditLog\AuthorCollection;
use C3net\CoreBundle\ApiResource\AuditLog\AuthorSummary;
use C3net\CoreBundle\Repository\AuditLogsRepository;

/**
 * @implements ProviderInterface<AuthorCollection>
 */
class AuditLogAuthorsProvider implements ProviderInterface
{
    public function __construct(
        private readonly AuditLogsRepository $auditLogsRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $authors = $this->auditLogsRepository->findUniqueAuthors();

        // Map to AuthorSummary value objects
        $authorSummaries = array_map(fn($author) => new AuthorSummary(
            id: (string) $author['id'],
            email: $author['email'],
            firstname: $author['firstname'],
            lastname: $author['lastname'],
            fullname: trim(($author['firstname'] ?? '') . ' ' . ($author['lastname'] ?? '')),
        ), $authors);

        return new AuthorCollection($authorSummaries);
    }
}
