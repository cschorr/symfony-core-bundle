<?php

declare(strict_types=1);

namespace C3net\CoreBundle\ApiResource\AuditLog;

final readonly class AuthorSummary
{
    public function __construct(
        public string $id,
        public string $email,
        public ?string $firstname,
        public ?string $lastname,
        public string $fullname,
    ) {
    }

    public function getIri(): string
    {
        return '/api/users/' . $this->id;
    }

    public function getType(): string
    {
        return 'User';
    }
}
