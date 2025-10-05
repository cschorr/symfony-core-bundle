<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity\Traits\Set;

use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Entity\CategorizableEntity;
use C3net\CoreBundle\Enum\DomainEntityType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Trait for entities that can have multiple categories via polymorphic junction table.
 *
 * This trait provides methods to manage categories through the CategorizableEntity junction.
 * The actual relationship is stored polymorphically, so no Doctrine annotations are needed here.
 */
trait CategorizableTrait
{
    /**
     * Cached categories collection to avoid repeated queries.
     *
     * @var Collection<int, Category>|null
     */
    private ?Collection $categoriesCache = null;

    /**
     * Get the entity type for categorization.
     */
    abstract protected function getCategorizableEntityType(): DomainEntityType;

    /**
     * Get all categories assigned to this entity.
     *
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        if (null !== $this->categoriesCache) {
            return $this->categoriesCache;
        }

        // Entity must be persisted to have categories
        if (null === $this->getId()) {
            $this->categoriesCache = new ArrayCollection();

            return $this->categoriesCache;
        }

        // Try to load from repository if available
        try {
            $repository = $this->getCategorizableEntityRepository();
            $this->categoriesCache = $repository->findCategoriesByEntity(
                $this->getId()->toString(),
                $this->getCategorizableEntityType()
            );
        } catch (\RuntimeException) {
            // Kernel not available (e.g., during fixture loading), return empty collection
            $this->categoriesCache = new ArrayCollection();
        }

        return $this->categoriesCache;
    }

    /**
     * Add a category to this entity.
     *
     * Note: This method uses global kernel access and may not work in all contexts.
     * For fixtures, create CategorizableEntity directly using EntityManager.
     */
    public function addCategory(Category $category): static
    {
        // Cannot add categories to unpersisted entities
        if (null === $this->getId()) {
            return $this;
        }

        $repository = $this->getCategorizableEntityRepository();
        $entityManager = $repository->getEntityManager();

        // Check if assignment already exists
        $existing = $repository->findByEntityAndCategory(
            $this->getId()->toString(),
            $this->getCategorizableEntityType(),
            $category
        );

        if (null === $existing) {
            $assignment = new CategorizableEntity();
            $assignment->setCategory($category);
            $assignment->setEntityType($this->getCategorizableEntityType());
            $assignment->setEntityId($this->getId()->toString());

            $entityManager->persist($assignment);
            $entityManager->flush();

            // Clear cache
            $this->categoriesCache = null;
        }

        return $this;
    }

    /**
     * Remove a category from this entity.
     */
    public function removeCategory(Category $category): static
    {
        if (null === $this->getId()) {
            return $this;
        }

        $repository = $this->getCategorizableEntityRepository();
        $entityManager = $repository->getEntityManager();

        $assignment = $repository->findByEntityAndCategory(
            $this->getId()->toString(),
            $this->getCategorizableEntityType(),
            $category
        );

        if (null !== $assignment) {
            $entityManager->remove($assignment);
            $entityManager->flush();

            // Clear cache
            $this->categoriesCache = null;
        }

        return $this;
    }

    /**
     * Check if this entity has a specific category.
     */
    public function hasCategory(Category $category): bool
    {
        return $this->getCategories()->contains($category);
    }

    /**
     * Clear the categories cache (useful after external changes).
     */
    public function clearCategoriesCache(): void
    {
        $this->categoriesCache = null;
    }

    /**
     * Get the CategorizableEntity repository.
     * This method uses Symfony's service container access pattern.
     *
     * @phpstan-ignore-next-line
     */
    private function getCategorizableEntityRepository(): \C3net\CoreBundle\Repository\CategorizableEntityRepository
    {
        // Access via global $kernel if available (for entities)
        global $kernel;
        if ($kernel instanceof \Symfony\Component\HttpKernel\KernelInterface) {
            $container = $kernel->getContainer();
            /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
            $doctrine = $container->get('doctrine');

            return $doctrine->getRepository(CategorizableEntity::class);
        }

        throw new \RuntimeException('Cannot access CategorizableEntityRepository: Symfony kernel not available');
    }
}
