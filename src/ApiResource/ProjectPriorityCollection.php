<?php

declare(strict_types=1);

namespace C3net\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use C3net\CoreBundle\State\ProjectPriorityProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'Project',
    operations: [
        new Get(
            uriTemplate: '/projects/priorities',
            provider: ProjectPriorityProvider::class,
            extraProperties: ['openapi_context' => ['tags' => ['Project']]]
        ),
    ],
    normalizationContext: ['groups' => ['project_priority:read']],
    paginationEnabled: false
)]
class ProjectPriorityCollection
{
    public readonly int $total;

    /**
     * @param array<int, array{name: string, value: string, label: string, badgeClass: string, sortOrder: int}> $items
     */
    public function __construct(
        public readonly array $items = [],
    ) {
        $this->total = count($this->items);
    }

    /**
     * @return array<int, array{name: string, value: string, label: string, badgeClass: string, sortOrder: int}>
     */
    #[Groups(['project_priority:read'])]
    public function getItems(): array
    {
        return $this->items;
    }

    #[Groups(['project_priority:read'])]
    public function getTotal(): int
    {
        return $this->total;
    }
}
