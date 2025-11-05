<?php

declare(strict_types=1);

namespace C3net\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use C3net\CoreBundle\State\DocumentTypeProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'Document',
    operations: [
        new Get(
            uriTemplate: '/documents/types',
            provider: DocumentTypeProvider::class,
            extraProperties: ['openapi_context' => ['tags' => ['Document']]]
        ),
    ],
    normalizationContext: ['groups' => ['document_type:read']],
    paginationEnabled: false
)]
class DocumentTypeCollection
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
    #[Groups(['document_type:read'])]
    public function getItems(): array
    {
        return $this->items;
    }

    #[Groups(['document_type:read'])]
    public function getTotal(): int
    {
        return $this->total;
    }
}
