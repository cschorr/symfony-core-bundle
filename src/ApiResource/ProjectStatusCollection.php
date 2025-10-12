<?php

declare(strict_types=1);

namespace C3net\CoreBundle\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use C3net\CoreBundle\State\ProjectStatusProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    shortName: 'ProjectStatuses',
    operations: [
        new Get(
            uriTemplate: '/project-statuses',
            provider: ProjectStatusProvider::class
        ),
    ],
    normalizationContext: ['groups' => ['project_status:read']],
    paginationEnabled: false
)]
class ProjectStatusCollection
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
    #[Groups(['project_status:read'])]
    public function getItems(): array
    {
        return $this->items;
    }

    #[Groups(['project_status:read'])]
    public function getTotal(): int
    {
        return $this->total;
    }
}
