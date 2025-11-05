<?php

declare(strict_types=1);

namespace C3net\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use C3net\CoreBundle\State\TransactionTypeProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'Transaction',
    operations: [
        new Get(
            uriTemplate: '/transactions/types',
            provider: TransactionTypeProvider::class,
            extraProperties: ['openapi_context' => ['tags' => ['Transaction']]]
        ),
    ],
    normalizationContext: ['groups' => ['transaction_type:read']],
    paginationEnabled: false
)]
class TransactionTypeCollection
{
    public readonly int $total;

    /**
     * @param array<int, array{name: string, value: string, label: string}> $items
     */
    public function __construct(
        public readonly array $items = [],
    ) {
        $this->total = count($this->items);
    }

    /**
     * @return array<int, array{name: string, value: string, label: string}>
     */
    #[Groups(['transaction_type:read'])]
    public function getItems(): array
    {
        return $this->items;
    }

    #[Groups(['transaction_type:read'])]
    public function getTotal(): int
    {
        return $this->total;
    }
}
