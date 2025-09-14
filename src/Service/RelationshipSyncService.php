<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service to handle bidirectional entity relationships automatically.
 */
class RelationshipSyncService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Sync bidirectional one-to-many relationships.
     *
     * @param object $owningEntity       The entity that owns the relationship (e.g., Company)
     * @param string $collectionProperty The property name of the collection (e.g., 'employees')
     * @param string $inverseProperty    The property name on the inverse side (e.g., 'company')
     */
    public function syncOneToMany(
        object $owningEntity,
        string $collectionProperty,
        string $inverseProperty,
    ): void {
        $owningEntityClass = $owningEntity::class;
        $collection = $this->getPropertyValue($owningEntity, $collectionProperty);

        if (!$collection instanceof Collection) {
            return;
        }

        // Only sync if entity has an ID (not for new entities)
        if ($this->getEntityId($owningEntity)) {
            $this->removePreviousReferences($owningEntity, $owningEntityClass, $collection, $inverseProperty);
        }

        // Set reference for all current collection items
        foreach ($collection as $item) {
            $currentValue = $this->getPropertyValue($item, $inverseProperty);
            if ($currentValue !== $owningEntity) {
                $this->setPropertyValue($item, $inverseProperty, $owningEntity);
            }
        }
    }

    /**
     * Auto-detect and sync common bidirectional relationships.
     */
    public function autoSync(object $entity): void
    {
        $class = $entity::class;

        // Common relationship patterns
        $relationshipMappings = [
            'Company' => [
                'employees' => 'company',
                'projects' => 'client',
            ],
            'User' => [
                'projects' => 'assignee',
            ],
            // Add more entity mappings as needed
        ];

        $entityName = basename(str_replace('\\', '/', $class));

        if (isset($relationshipMappings[$entityName])) {
            foreach ($relationshipMappings[$entityName] as $collection => $inverse) {
                if ($this->hasProperty($entity, $collection)) {
                    $this->syncOneToMany($entity, $collection, $inverse);
                }
            }
        }
    }

    /**
     * @param Collection<int, object> $currentCollection
     */
    private function removePreviousReferences(object $owningEntity, string $owningEntityClass, Collection $currentCollection, string $inverseProperty): void
    {
        // Get entity class from collection to find previous items
        if ($currentCollection->isEmpty()) {
            return;
        }

        $firstItem = $currentCollection->first();
        if (false === $firstItem) {
            return;
        }

        $itemClass = $firstItem::class;

        // Find all items that were previously assigned to this entity
        /** @var class-string $itemClass */
        $repository = $this->entityManager->getRepository($itemClass);
        $previousItems = $repository->findBy([$inverseProperty => $owningEntity]);

        // Remove reference from items no longer in the collection
        foreach ($previousItems as $item) {
            if (!$currentCollection->contains($item)) {
                $this->setPropertyValue($item, $inverseProperty, null);
            }
        }
    }

    private function getPropertyValue(object $entity, string $property): mixed
    {
        $getter = 'get' . ucfirst($property);
        if (method_exists($entity, $getter)) {
            return $entity->$getter();
        }

        $isGetter = 'is' . ucfirst($property);
        if (method_exists($entity, $isGetter)) {
            return $entity->$isGetter();
        }

        throw new \InvalidArgumentException(sprintf("No getter method found for property '%s' on ", $property) . $entity::class);
    }

    private function setPropertyValue(object $entity, string $property, mixed $value): void
    {
        $setter = 'set' . ucfirst($property);
        if (method_exists($entity, $setter)) {
            $entity->$setter($value);

            return;
        }

        throw new \InvalidArgumentException(sprintf("No setter method found for property '%s' on ", $property) . $entity::class);
    }

    private function getEntityId(object $entity): mixed
    {
        if (method_exists($entity, 'getId')) {
            return $entity->getId();
        }

        return null;
    }

    private function hasProperty(object $entity, string $property): bool
    {
        $getter = 'get' . ucfirst($property);

        return method_exists($entity, $getter);
    }
}
