<?php

declare(strict_types=1);

namespace C3net\CoreBundle\ApiResource\AuditLog;

final readonly class FilterOptions
{
    /**
     * @param array<int, AuthorSummary> $authors
     * @param array<int, string> $resources
     * @param array<int, string> $actions
     */
    public function __construct(
        public array $authors,
        public array $resources,
        public array $actions,
    ) {
    }
}
