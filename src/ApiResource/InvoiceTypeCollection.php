<?php

declare(strict_types=1);

namespace C3net\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use C3net\CoreBundle\State\InvoiceTypeProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'Invoice',
    operations: [
        new Get(
            uriTemplate: '/invoices/types',
            provider: InvoiceTypeProvider::class,
            extraProperties: ['openapi_context' => ['tags' => ['Invoice']]]
        ),
    ],
    normalizationContext: ['groups' => ['invoice_type:read']],
    paginationEnabled: false
)]
class InvoiceTypeCollection
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
    #[Groups(['invoice_type:read'])]
    public function getItems(): array
    {
        return $this->items;
    }

    #[Groups(['invoice_type:read'])]
    public function getTotal(): int
    {
        return $this->total;
    }
}
