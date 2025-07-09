<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DuplicateService
{
    private EntityManagerInterface $entityManager;
    private PropertyAccessorInterface $propertyAccessor;
    private PropertyInfoExtractorInterface $propertyInfo;
    private TranslatorInterface $translator;

    public function __construct(
        EntityManagerInterface $entityManager,
        PropertyAccessorInterface $propertyAccessor,
        PropertyInfoExtractorInterface $propertyInfo,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->propertyAccessor = $propertyAccessor;
        $this->propertyInfo = $propertyInfo;
        $this->translator = $translator;
    }

    /**
     * Duplicate an entity with all its properties except ID and unique constraints
     */
    public function duplicate(object $entity): object
    {
        $entityClass = get_class($entity);
        $metadata = $this->entityManager->getClassMetadata($entityClass);
        
        // Create a new instance of the same class
        $duplicatedEntity = new $entityClass();
        
        // Get all properties of the entity
        $properties = $this->propertyInfo->getProperties($entityClass) ?? [];
        
        foreach ($properties as $property) {
            // Skip ID and other fields that should not be duplicated
            if ($this->shouldSkipProperty($property, $metadata)) {
                continue;
            }
            
            try {
                // Get the value from the original entity
                $value = $this->propertyAccessor->getValue($entity, $property);
                
                // Handle special cases for duplication
                $duplicatedValue = $this->processDuplicatedValue($value, $property, $entity);
                
                // Set the value on the duplicated entity
                if ($duplicatedValue !== null || $this->propertyAccessor->isWritable($duplicatedEntity, $property)) {
                    $this->propertyAccessor->setValue($duplicatedEntity, $property, $duplicatedValue);
                }
            } catch (\Exception $e) {
                // Skip properties that cannot be accessed or set
                continue;
            }
        }
        
        // Handle special naming for duplicated entities
        $this->handleDuplicateNaming($duplicatedEntity, $entity);
        
        // Ensure we return an object
        if (!is_object($duplicatedEntity)) {
            throw new \RuntimeException('DuplicateService failed to create object, got: ' . gettype($duplicatedEntity));
        }
        
        return $duplicatedEntity;
    }

    /**
     * Check if a property should be skipped during duplication
     */
    private function shouldSkipProperty(string $property, $metadata): bool
    {
        // Skip ID field
        if (in_array($property, $metadata->getIdentifier())) {
            return true;
        }
        
        // Skip timestamp fields that should be auto-generated
        $timestampFields = ['createdAt', 'updatedAt', 'created_at', 'updated_at'];
        if (in_array($property, $timestampFields)) {
            return true;
        }
        
        // Skip version fields for optimistic locking
        if ($property === 'version') {
            return true;
        }
        
        // Skip certain relationship properties that could cause issues
        // For example, skip inverse side of OneToMany relationships that might cause conflicts
        if ($metadata->hasAssociation($property)) {
            $associationMapping = $metadata->getAssociationMapping($property);
            
            // For OneToMany relationships, we might want to skip the inverse side
            // to avoid issues with bidirectional relationships during duplication
            if ($associationMapping['type'] === \Doctrine\ORM\Mapping\ClassMetadata::ONE_TO_MANY) {
                // For now, we'll include OneToMany relationships but handle them carefully
                // They will be processed by processDuplicatedValue
            }
        }
        
        return false;
    }

    /**
     * Process the value for duplication (handle special cases)
     */
    private function processDuplicatedValue($value, string $property, object $originalEntity)
    {
        // Handle collections - for duplication, we usually want to preserve existing relationships
        if ($value instanceof \Doctrine\Common\Collections\Collection) {
            // Create a new ArrayCollection
            $newCollection = new \Doctrine\Common\Collections\ArrayCollection();
            
            // For duplication, we want to preserve the relationships to existing entities
            // However, we need to be careful about bidirectional relationships
            foreach ($value as $entity) {
                if (is_object($entity)) {
                    $managedEntity = $this->ensureManagedEntity($entity);
                    if ($managedEntity) {
                        $newCollection->add($managedEntity);
                    }
                }
            }
            
            return $newCollection;
        }
        
        // Handle single entity relationships - ensure they are managed
        if (is_object($value) && !$value instanceof \DateTimeInterface) {
            return $this->ensureManagedEntity($value);
        }
        
        // Handle date objects - create new instances
        if ($value instanceof \DateTimeInterface) {
            return clone $value;
        }
        
        return $value;
    }

    /**
     * Ensure an entity is managed by the EntityManager
     */
    private function ensureManagedEntity($entity)
    {
        if (!is_object($entity)) {
            return $entity;
        }
        
        // If the entity is already managed, return it
        if ($this->entityManager->contains($entity)) {
            return $entity;
        }
        
        try {
            // Try to get the entity class and ID
            $entityClass = get_class($entity);
            
            // Handle Doctrine proxies - get the real class name
            if (strpos($entityClass, 'Proxies\\__CG__\\') === 0) {
                $entityClass = substr($entityClass, strlen('Proxies\\__CG__\\'));
            }
            
            // Get the metadata for the entity
            $metadata = $this->entityManager->getClassMetadata($entityClass);
            
            // Try to get the identifier values
            $identifier = $metadata->getIdentifierValues($entity);
            
            if (!empty($identifier)) {
                // Find the managed entity by ID
                $managedEntity = $this->entityManager->find($entityClass, $identifier);
                if ($managedEntity) {
                    return $managedEntity;
                }
            }
            
            // If we couldn't find a managed entity, return the original entity
            // It's better to return the original than fail completely
            return $entity;
            
        } catch (\Exception $e) {
            // If anything fails, return the original entity
            // This ensures we don't break the duplication process
            return $entity;
        }
    }

    /**
     * Handle special naming for duplicated entities
     */
    private function handleDuplicateNaming(object $duplicatedEntity, object $originalEntity): void
    {
        // Common name/title fields to append "Copy" to
        $nameFields = ['name', 'title', 'label', 'displayName'];
        
        foreach ($nameFields as $field) {
            if ($this->propertyAccessor->isReadable($originalEntity, $field) && 
                $this->propertyAccessor->isWritable($duplicatedEntity, $field)) {
                
                $originalValue = $this->propertyAccessor->getValue($originalEntity, $field);
                if ($originalValue && is_string($originalValue)) {
                    $copyText = $this->translator->trans('Copy');
                    $duplicatedValue = $originalValue . ' (' . $copyText . ')';
                    $this->propertyAccessor->setValue($duplicatedEntity, $field, $duplicatedValue);
                    break; // Only update the first name field found
                }
            }
        }
    }

    /**
     * Persist the duplicated entity
     */
    public function persistDuplicate(object $duplicatedEntity): void
    {
        $this->entityManager->persist($duplicatedEntity);
        $this->entityManager->flush();
    }

    /**
     * Duplicate and persist an entity in one operation
     */
    public function duplicateAndPersist(object $entity): object
    {
        $duplicatedEntity = $this->duplicate($entity);
        $this->persistDuplicate($duplicatedEntity);
        
        return $duplicatedEntity;
    }
}
