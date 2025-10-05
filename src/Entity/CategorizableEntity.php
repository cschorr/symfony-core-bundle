<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity;

use C3net\CoreBundle\Enum\DomainEntityType;
use C3net\CoreBundle\Repository\CategorizableEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Junction entity for polymorphic category assignments.
 *
 * This entity is NOT exposed via API Platform - categories are managed
 * through their parent entities' category collections.
 */
#[ORM\Entity(repositoryClass: CategorizableEntityRepository::class)]
#[ORM\Table(name: 'categorizable_entity')]
#[ORM\UniqueConstraint(name: 'categorizable_unique', columns: ['category_id', 'entity_type', 'entity_id'])]
#[ORM\Index(name: 'idx_entity_lookup', columns: ['entity_type', 'entity_id'])]
#[ORM\Index(name: 'idx_category_lookup', columns: ['category_id'])]
class CategorizableEntity extends AbstractEntity
{
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Category $category = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false, enumType: DomainEntityType::class)]
    private ?DomainEntityType $entityType = null;

    #[ORM\Column(type: Types::STRING, length: 36, nullable: false)]
    private ?string $entityId = null;

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getEntityType(): ?DomainEntityType
    {
        return $this->entityType;
    }

    public function setEntityType(?DomainEntityType $entityType): static
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

    public function setEntityId(?string $entityId): static
    {
        $this->entityId = $entityId;

        return $this;
    }
}
