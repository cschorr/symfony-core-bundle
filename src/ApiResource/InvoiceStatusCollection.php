<?php

declare(strict_types=1);

namespace C3net\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use C3net\CoreBundle\State\InvoiceStatusProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'Invoice',
    operations: [
        new Get(
            uriTemplate: '/invoices/statuses',
            provider: InvoiceStatusProvider::class,
            extraProperties: ['openapi_context' => ['tags' => ['Invoice']]]
        ),
    ],
    normalizationContext: ['groups' => ['invoice_status:read']],
    paginationEnabled: false
)]
class InvoiceStatusCollection
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
    #[Groups(['invoice_status:read'])]
    public function getItems(): array
    {
        return $this->items;
    }

    #[Groups(['invoice_status:read'])]
    public function getTotal(): int
    {
        return $this->total;
    }
}
