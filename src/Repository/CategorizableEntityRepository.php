<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Repository;

use C3net\CoreBundle\Entity\CategorizableEntity;
use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Enum\DomainEntityType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategorizableEntity>
 */
class CategorizableEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorizableEntity::class);
    }

    /**
     * Find all categories for a specific entity.
     *
     * @return Collection<int, Category>
     */
    public function findCategoriesByEntity(string $entityId, DomainEntityType $entityType): Collection
    {
        $result = $this->createQueryBuilder('ce')
            ->select('c')
            ->join('ce.category', 'c')
            ->where('ce.entityId = :entityId')
            ->andWhere('ce.entityType = :entityType')
            ->setParameter('entityId', $entityId)
            ->setParameter('entityType', $entityType)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        return new ArrayCollection($result);
    }

    /**
     * Find a specific category assignment.
     */
    public function findByEntityAndCategory(
        string $entityId,
        DomainEntityType $entityType,
        Category $category,
    ): ?CategorizableEntity {
        return $this->createQueryBuilder('ce')
            ->where('ce.entityId = :entityId')
            ->andWhere('ce.entityType = :entityType')
            ->andWhere('ce.category = :category')
            ->setParameter('entityId', $entityId)
            ->setParameter('entityType', $entityType)
            ->setParameter('category', $category)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all entities of a specific type that have a given category.
     *
     * @return array<int, string> Array of entity IDs
     */
    public function findEntitiesByCategory(Category $category, DomainEntityType $entityType): array
    {
        $result = $this->createQueryBuilder('ce')
            ->select('ce.entityId')
            ->where('ce.category = :category')
            ->andWhere('ce.entityType = :entityType')
            ->setParameter('category', $category)
            ->setParameter('entityType', $entityType)
            ->getQuery()
            ->getResult();

        return array_column($result, 'entityId');
    }

    /**
     * Remove all category assignments for an entity.
     */
    public function removeAllByEntity(string $entityId, DomainEntityType $entityType): void
    {
        $this->createQueryBuilder('ce')
            ->delete()
            ->where('ce.entityId = :entityId')
            ->andWhere('ce.entityType = :entityType')
            ->setParameter('entityId', $entityId)
            ->setParameter('entityType', $entityType)
            ->getQuery()
            ->execute();
    }
}
