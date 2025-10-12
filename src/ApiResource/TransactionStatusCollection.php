<?php

declare(strict_types=1);

namespace C3net\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use C3net\CoreBundle\State\TransactionStatusProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'TransactionStatuses',
    operations: [
        new Get(
            uriTemplate: '/transaction-statuses',
            provider: TransactionStatusProvider::class
        ),
    ],
    normalizationContext: ['groups' => ['transaction_status:read']],
    paginationEnabled: false
)]
class TransactionStatusCollection
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
    #[Groups(['transaction_status:read'])]
    public function getItems(): array
    {
        return $this->items;
    }

    #[Groups(['transaction_status:read'])]
    public function getTotal(): int
    {
        return $this->total;
    }
}
