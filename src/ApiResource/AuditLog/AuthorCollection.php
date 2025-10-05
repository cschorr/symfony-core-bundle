<?php

declare(strict_types=1);

namespace C3net\CoreBundle\ApiResource\AuditLog;

final readonly class AuthorCollection
{
    /**
     * @param array<int, AuthorSummary> $authors
     */
    public function __construct(
        public array $authors,
    ) {
    }
}
