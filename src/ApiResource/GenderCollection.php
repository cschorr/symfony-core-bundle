<?php

declare(strict_types=1);

namespace C3net\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use C3net\CoreBundle\State\GenderProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'Genders',
    operations: [
        new Get(
            uriTemplate: '/genders',
            provider: GenderProvider::class
        ),
    ],
    normalizationContext: ['groups' => ['gender:read']],
    paginationEnabled: false
)]
class GenderCollection
{
    public readonly int $total;

    /**
     * @param array<int, array{name: string, value: string, label: string, pronoun: string, salutation: string}> $items
     */
    public function __construct(
        public readonly array $items = [],
    ) {
        $this->total = count($this->items);
    }

    /**
     * @return array<int, array{name: string, value: string, label: string, pronoun: string, salutation: string}>
     */
    #[Groups(['gender:read'])]
    public function getItems(): array
    {
        return $this->items;
    }

    #[Groups(['gender:read'])]
    public function getTotal(): int
    {
        return $this->total;
    }
}
