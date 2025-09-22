<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use C3net\CoreBundle\Repository\AuditLogsRepository;

class AuditLogAuthorsProvider implements ProviderInterface
{
    public function __construct(
        private readonly AuditLogsRepository $auditLogsRepository
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $authors = $this->auditLogsRepository->findUniqueAuthors();

        // Format the response for easier frontend consumption
        return array_map(function ($author) {
            return [
                '@id' => '/api/users/' . $author['id'],
                '@type' => 'User',
                'id' => $author['id'],
                'email' => $author['email'],
                'firstname' => $author['firstname'],
                'lastname' => $author['lastname'],
                'fullname' => trim(($author['firstname'] ?? '') . ' ' . ($author['lastname'] ?? '')),
            ];
        }, $authors);
    }
}