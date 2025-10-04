<?php

declare(strict_types=1);

namespace C3net\CoreBundle\ApiResource\AuditLog;

final readonly class ActionCollection
{
    /**
     * @param array<int, string> $actions
     */
    public function __construct(
        public array $actions,
    ) {
    }
}
