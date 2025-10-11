<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Service;

use C3net\CoreBundle\Entity\CategorizableEntity;
use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Enum\DomainEntityType;
use C3net\CoreBundle\Repository\CategorizableEntityRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service to manage category assignments for entities.
 *
 * This service provides the recommended way to add/remove categories from entities,
 * replacing the deprecated addCategory()/removeCategory() methods in CategorizableTrait.
 */
class CategoryAssignmentService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CategorizableEntityRepository $repository,
    ) {
    }

    /**
     * Add a category to an entity.
     *
     * @param string           $entityId   The UUID of the entity
     * @param DomainEntityType $entityType The type of entity
     * @param Category         $category   The category to assign
     *
     * @throws \InvalidArgumentException If entityId is invalid
     */
    public function addCategory(
        string $entityId,
        DomainEntityType $entityType,
        Category $category,
    ): void {
        // Validate inputs
        if (empty($entityId)) {
            throw new \InvalidArgumentException('Entity ID cannot be empty');
        }

        if (!$this->isValidUuid($entityId)) {
            throw new \InvalidArgumentException(sprintf('Invalid UUID format: %s', $entityId));
        }

        // Note: We cannot validate entity existence due to polymorphic nature
        // The entity class is determined at runtime by DomainEntityType
        // Foreign key constraints at DB level will prevent invalid assignments

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
     *
     * @param string           $entityId   The UUID of the entity
     * @param DomainEntityType $entityType The type of entity
     * @param Category         $category   The category to remove
     *
     * @throws \InvalidArgumentException If entityId is invalid
     */
    public function removeCategory(
        string $entityId,
        DomainEntityType $entityType,
        Category $category,
    ): void {
        // Validate inputs
        if (empty($entityId)) {
            throw new \InvalidArgumentException('Entity ID cannot be empty');
        }

        if (!$this->isValidUuid($entityId)) {
            throw new \InvalidArgumentException(sprintf('Invalid UUID format: %s', $entityId));
        }

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

    /**
     * Remove all categories from an entity.
     *
     * @param string           $entityId   The UUID of the entity
     * @param DomainEntityType $entityType The type of entity
     */
    public function removeAllCategories(string $entityId, DomainEntityType $entityType): void
    {
        $this->repository->removeAllByEntity($entityId, $entityType);
    }

    /**
     * Validate UUID format.
     */
    private function isValidUuid(string $uuid): bool
    {
        return 1 === preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid);
    }
}
