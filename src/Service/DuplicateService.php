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
        
        // For duplication, we want to keep associations to pre-fill forms
        // So we don't skip associations like we would for normal entity operations
        
        return false;
    }

    /**
     * Process the value for duplication (handle special cases)
     */
    private function processDuplicatedValue($value, string $property, object $originalEntity)
    {
        // Handle collections - copy the existing entities (don't create empty collection)
        if ($value instanceof \Doctrine\Common\Collections\Collection) {
            // Create a new ArrayCollection and copy all entities from the original collection
            $newCollection = new \Doctrine\Common\Collections\ArrayCollection();
            
            // Copy all entities from the original collection
            foreach ($value as $entity) {
                $newCollection->add($entity);
            }
            
            return $newCollection;
        }
        
        // Handle date objects - create new instances
        if ($value instanceof \DateTimeInterface) {
            return clone $value;
        }
        
        // Handle other objects - keep reference (don't deep clone)
        if (is_object($value) && !$value instanceof \DateTimeInterface) {
            return $value;
        }
        
        return $value;
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
