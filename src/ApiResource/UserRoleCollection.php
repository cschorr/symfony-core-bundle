<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\UserRoleProvider;

#[ApiResource(
    shortName: 'UserRoles',
    operations: [
        new Get(
            uriTemplate: '/permissions/roles',
            read: false,
            provider: UserRoleProvider::class
        ),
    ],
    paginationEnabled: false
)]
class UserRoleCollection
{
    public readonly int $total;

    /**
     * @param array<int, array{name: string, value: string}> $roles
     */
    public function __construct(
        public readonly array $roles = [],
    ) {
        $this->total = count($this->roles);
    }

    /**
     * @return array<int, array{name: string, value: string}>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getTotal(): int
    {
        return $this->total;
    }
}
