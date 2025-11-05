<?php

declare(strict_types=1);

namespace C3net\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use C3net\CoreBundle\State\OfferStatusProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'Offer',
    operations: [
        new Get(
            uriTemplate: '/offers/statuses',
            provider: OfferStatusProvider::class,
            extraProperties: ['openapi_context' => ['tags' => ['Offer']]]
        ),
    ],
    normalizationContext: ['groups' => ['offer_status:read']],
    paginationEnabled: false
)]
class OfferStatusCollection
{
    public readonly int $total;

    /**
     * @param array<int, array{name: string, value: string, label: string, badgeClass: string}> $items
     */
    public function __construct(
        public readonly array $items = [],
    ) {
        $this->total = count($this->items);
    }

    /**
     * @return array<int, array{name: string, value: string, label: string, badgeClass: string}>
     */
    #[Groups(['offer_status:read'])]
    public function getItems(): array
    {
        return $this->items;
    }

    #[Groups(['offer_status:read'])]
    public function getTotal(): int
    {
        return $this->total;
    }
}
