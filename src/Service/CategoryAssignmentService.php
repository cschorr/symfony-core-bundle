<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Service;

use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Entity\CategorizableEntity;
use C3net\CoreBundle\Enum\DomainEntityType;
use C3net\CoreBundle\Repository\CategorizableEntityRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service to manage category assignments for entities.
 */
class CategoryAssignmentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CategorizableEntityRepository $repository
    ) {
    }

    /**
     * Add a category to an entity.
     */
    public function addCategory(
        string $entityId,
        DomainEntityType $entityType,
        Category $category
    ): void {
        // Check if assignment already exists
        $existing = $this->repository->findByEntityAndCategory(
            $entityId,
            $entityType,
            $category
        );

        if (null === $existing) {
            $assignment = new CategorizableEntity();
            $assignment->setCategory($category);
            $assignment->setEntityType($entityType);
            $assignment->setEntityId($entityId);

            $this->entityManager->persist($assignment);
            $this->entityManager->flush();
        }
    }

    /**
     * Remove a category from an entity.
     */
    public function removeCategory(
        string $entityId,
        DomainEntityType $entityType,
        Category $category
    ): void {
        $assignment = $this->repository->findByEntityAndCategory(
            $entityId,
            $entityType,
            $category
        );

        if (null !== $assignment) {
            $this->entityManager->remove($assignment);
            $this->entityManager->flush();
        }
    }
}
