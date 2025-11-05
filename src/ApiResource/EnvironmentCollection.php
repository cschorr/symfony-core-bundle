<?php

declare(strict_types=1);

namespace C3net\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use C3net\CoreBundle\State\EnvironmentProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'System',
    operations: [
        new Get(
            uriTemplate: '/system/environments',
            provider: EnvironmentProvider::class,
            extraProperties: ['openapi_context' => ['tags' => ['System']]]
        ),
    ],
    normalizationContext: ['groups' => ['environment:read']],
    paginationEnabled: false
)]
class EnvironmentCollection
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
    #[Groups(['environment:read'])]
    public function getItems(): array
    {
        return $this->items;
    }

    #[Groups(['environment:read'])]
    public function getTotal(): int
    {
        return $this->total;
    }
}
