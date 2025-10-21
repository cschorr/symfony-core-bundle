<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use C3net\CoreBundle\Entity\Traits\Single\StringNameTrait;
use C3net\CoreBundle\Entity\Traits\Tree\NestedTreeTrait;
use C3net\CoreBundle\Enum\DomainEntityType;
use C3net\CoreBundle\Repository\CategoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[Gedmo\Tree(type: 'nested')]
#[ORM\Table(name: 'categories')]
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ApiResource(
    shortName: 'Category',
    description: 'Nested categories.',
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
    paginationEnabled: true,
    paginationItemsPerPage: 30,
    paginationMaximumItemsPerPage: 100
)]
#[ApiFilter(
    filterClass: OrderFilter::class,
    properties: [
        'title' => 'ASC',
        'year' => 'DESC',
    ],
)]
class Category extends AbstractEntity
{
    use StringNameTrait;
    use NestedTreeTrait;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $icon = null;

    public function __construct()
    {
        parent::__construct();
        $this->initializeTreeCollections();
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get all entity IDs of a specific type that have this category.
     *
     * @return array<int, string>
     */
    public function getEntitiesByType(DomainEntityType $entityType): array
    {
        $repository = $this->getCategorizableEntityRepository();

        return $repository->findEntitiesByCategory($this, $entityType);
    }

    /**
     * Get the CategorizableEntity repository.
     */
    private function getCategorizableEntityRepository(): \C3net\CoreBundle\Repository\CategorizableEntityRepository
    {
        global $kernel;
        if ($kernel instanceof \Symfony\Component\HttpKernel\KernelInterface) {
            $container = $kernel->getContainer();
            /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
            $doctrine = $container->get('doctrine');

            /* @var \C3net\CoreBundle\Repository\CategorizableEntityRepository */
            return $doctrine->getRepository(CategorizableEntity::class);
        }

        throw new \RuntimeException('Cannot access CategorizableEntityRepository: Symfony kernel not available');
    }
}
