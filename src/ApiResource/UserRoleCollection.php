<?php

declare(strict_types=1);

namespace C3net\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use C3net\CoreBundle\State\UserRoleProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'Permission',
    operations: [
        new Get(
            uriTemplate: '/permissions/roles',
            provider: UserRoleProvider::class,
            extraProperties: ['openapi_context' => ['tags' => ['Permission']]]
        ),
    ],
    normalizationContext: ['groups' => ['user_role:read']],
    paginationEnabled: false
)]
class UserRoleCollection
{
    public readonly int $total;

    /**
     * @param array<int, array{name: string, value: string}> $items
     */
    public function __construct(
        public readonly array $items = [],
    ) {
        $this->total = count($this->items);
    }

    /**
     * @return array<int, array{name: string, value: string}>
     */
    #[Groups(['user_role:read'])]
    public function getItems(): array
    {
        return $this->items;
    }

    #[Groups(['user_role:read'])]
    public function getTotal(): int
    {
        return $this->total;
    }
}
