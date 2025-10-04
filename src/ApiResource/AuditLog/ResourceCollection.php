<?php

declare(strict_types=1);

namespace C3net\CoreBundle\ApiResource\AuditLog;

final readonly class ResourceCollection
{
    /**
     * @param array<int, string> $resources
     */
    public function __construct(
        public array $resources,
    ) {
    }
}
