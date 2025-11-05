<?php

declare(strict_types=1);

namespace C3net\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use C3net\CoreBundle\State\PermissionProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'Permission',
    operations: [
        new Get(
            uriTemplate: '/permissions/types',
            provider: PermissionProvider::class,
            extraProperties: ['openapi_context' => ['tags' => ['Permission']]]
        ),
    ],
    normalizationContext: ['groups' => ['permission:read']],
    paginationEnabled: false
)]
class PermissionCollection
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
    #[Groups(['permission:read'])]
    public function getItems(): array
    {
        return $this->items;
    }

    #[Groups(['permission:read'])]
    public function getTotal(): int
    {
        return $this->total;
    }
}
