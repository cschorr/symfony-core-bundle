<?php

declare(strict_types=1);

namespace C3net\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use C3net\CoreBundle\State\DomainEntityTypeProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'DomainEntityTypes',
    operations: [
        new Get(
            uriTemplate: '/domain-entity-types',
            provider: DomainEntityTypeProvider::class
        ),
    ],
    normalizationContext: ['groups' => ['domain_entity_type:read']],
    paginationEnabled: false
)]
class DomainEntityTypeCollection
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
    #[Groups(['domain_entity_type:read'])]
    public function getItems(): array
    {
        return $this->items;
    }

    #[Groups(['domain_entity_type:read'])]
    public function getTotal(): int
    {
        return $this->total;
    }
}
