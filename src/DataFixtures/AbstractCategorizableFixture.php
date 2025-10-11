<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\AbstractEntity;
use C3net\CoreBundle\Entity\CategorizableEntity;
use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Enum\DomainEntityType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Abstract base fixture class for entities that support category assignment.
 *
 * Provides helper methods for:
 * - Persisting entities with automatic flushing
 * - Assigning categories to entities via the CategorizableEntity junction table
 * - Handling Mercure errors gracefully during fixture loading
 */
abstract class AbstractCategorizableFixture extends Fixture
{
    /**
     * Persist an entity and flush immediately with Mercure error handling.
     *
     * This is useful when you need the entity ID immediately after persisting
     * (e.g., for creating related entities or category assignments).
     */
    protected function persistAndFlush(ObjectManager $manager, object $entity): void
    {
        $manager->persist($entity);

        try {
            $manager->flush();
        } catch (\Exception $e) {
            // Ignore Mercure failures during fixture loading
            if (!$this->isMercureError($e)) {
                throw $e;
            }
        }
    }

    /**
     * Flush with Mercure error handling.
     *
     * Use this for final flushes at the end of fixture loading.
     */
    protected function flushSafely(ObjectManager $manager): void
    {
        try {
            $manager->flush();
        } catch (\Exception $e) {
            // Ignore Mercure failures during fixture loading
            if (!$this->isMercureError($e)) {
                throw $e;
            }
        }
    }

    /**
     * Assign a category to an entity using the CategorizableEntity junction table.
     *
     * @param AbstractEntity   $entity     The entity to assign the category to (must have an ID)
     * @param Category         $category   The category to assign
     * @param DomainEntityType $entityType The domain entity type
     */
    protected function assignCategory(
        ObjectManager $manager,
        AbstractEntity $entity,
        Category $category,
        DomainEntityType $entityType,
    ): void {
        if (null === $entity->getId()) {
            throw new \RuntimeException(sprintf('Entity must be persisted and have an ID before assigning categories. Entity class: %s', $entity::class));
        }

        $assignment = new CategorizableEntity();
        $assignment->setCategory($category);
        $assignment->setEntityType($entityType);
        $assignment->setEntityId($entity->getId()->toString());

        $manager->persist($assignment);
    }

    /**
     * Assign multiple categories to an entity.
     *
     * @param AbstractEntity   $entity     The entity to assign categories to
     * @param Category[]       $categories Array of categories to assign
     * @param DomainEntityType $entityType The domain entity type
     */
    protected function assignCategories(
        ObjectManager $manager,
        AbstractEntity $entity,
        array $categories,
        DomainEntityType $entityType,
    ): void {
        foreach ($categories as $category) {
            $this->assignCategory($manager, $entity, $category, $entityType);
        }
    }

    /**
     * Find a category by name or throw an exception.
     */
    protected function findCategoryByName(ObjectManager $manager, string $name): Category
    {
        $category = $manager->getRepository(Category::class)->findOneBy(['name' => $name]);

        if (!$category) {
            throw new \RuntimeException(sprintf('Category "%s" not found', $name));
        }

        return $category;
    }

    /**
     * Find multiple categories by names.
     *
     * @param string[] $names
     *
     * @return Category[]
     */
    protected function findCategoriesByNames(ObjectManager $manager, array $names): array
    {
        $categories = [];

        foreach ($names as $name) {
            $categories[] = $this->findCategoryByName($manager, $name);
        }

        return $categories;
    }

    /**
     * Check if an exception is a Mercure-related error.
     */
    private function isMercureError(\Exception $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'Failed to send an update')
            || str_contains($message, 'mercure')
            || str_contains($message, 'Mercure');
    }
}
