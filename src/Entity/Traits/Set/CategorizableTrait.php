<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Entity\Traits\Set;

use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Enum\DomainEntityType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Trait for entities that can have multiple categories via polymorphic junction table.
 *
 * This trait provides READ-ONLY methods for category access.
 * For category management (add/remove), use CategoryAssignmentService.
 *
 * @see \C3net\CoreBundle\Service\CategoryAssignmentService
 */
trait CategorizableTrait
{
    /**
     * Cached categories collection to avoid repeated queries.
     *
     * Note: This cache is populated externally (e.g., by API Platform data providers)
     * and may not reflect real-time database state in all contexts.
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
     * This is a READ-ONLY operation. Categories are loaded from cache if available,
     * or return an empty collection for unpersisted entities.
     *
     * To modify categories, use CategoryAssignmentService:
     * $categoryAssignmentService->addCategory($entity->getId()->toString(), DomainEntityType::Company, $category);
     *
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        if (null !== $this->categoriesCache) {
            return $this->categoriesCache;
        }

        // Unpersisted entities have no categories
        if (null === $this->getId()) {
            $this->categoriesCache = new ArrayCollection();

            return $this->categoriesCache;
        }

        // Return empty collection - categories should be loaded externally
        // (e.g., by API Platform data providers or explicit service calls)
        $this->categoriesCache = new ArrayCollection();

        return $this->categoriesCache;
    }

    /**
     * Set categories collection (for external loading by services/data providers).
     *
     * Accepts both Collection and array for API Platform compatibility.
     *
     * @param Collection<int, Category>|array<int, Category> $categories
     */
    public function setCategories(Collection|array $categories): void
    {
        if (is_array($categories)) {
            $this->categoriesCache = new ArrayCollection($categories);
        } else {
            $this->categoriesCache = $categories;
        }
    }

    /**
     * Check if this entity has a specific category.
     */
    public function hasCategory(Category $category): bool
    {
        return $this->getCategories()->contains($category);
    }

    /**
     * Clear the categories cache.
     *
     * Useful when categories have been modified externally and need to be reloaded.
     */
    public function clearCategoriesCache(): void
    {
        $this->categoriesCache = null;
    }
}
