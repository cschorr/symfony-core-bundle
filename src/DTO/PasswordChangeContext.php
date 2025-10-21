<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DTO;

use C3net\CoreBundle\Entity\User;

readonly class PasswordChangeContext
{
    public function __construct(
        public string $ipAddress,
        public string $userAgent,
        public \DateTimeImmutable $timestamp,
        public bool $changedBySelf,
        public ?User $changedByUser = null,
    ) {
    }
}
