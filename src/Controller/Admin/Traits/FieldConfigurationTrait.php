<?php

namespace App\Controller\Admin\Traits;

/**
 * Trait for common field configuration patterns
 * Note: Requires the controller to have EasyAdminFieldService $fieldService property
 */
trait FieldConfigurationTrait
{
    /**
     * Get standard entity field configuration (ID, name, timestamps)
     */
    protected function getStandardEntityFields(string $entityLabel = 'Entity'): array
    {
        return [
            $this->fieldService->createIdField(),
            $this->fieldService->createNameField($entityLabel . ' Name'),
        ];
    }

    /**
     * Get address and communication field groups
     */
    protected function getContactFieldGroups(array $pages = ['detail', 'form']): array
    {
        $fields = [];
        $fields = array_merge($fields, $this->fieldService->createCommunicationFieldGroup($pages));
        $fields = array_merge($fields, $this->fieldService->createAddressFieldGroup($pages));
        return $fields;
    }

    /**
     * Get timestamp fields (created, updated)
     */
    protected function getTimestampFields(array $pages = ['detail']): array
    {
        return [
            $this->fieldService->createPanelConfig('timestamps_panel', 'Timestamps', $pages, 'fas fa-clock'),
            $this->fieldService->field('createdAt')
                ->type('datetime')
                ->label('Created At')
                ->pages($pages)
                ->build(),
            $this->fieldService->field('updatedAt')
                ->type('datetime')
                ->label('Updated At')
                ->pages($pages)
                ->build(),
        ];
    }

    /**
     * Get user association field with smart defaults
     */
    protected function getUserAssociationField(
        string $fieldName = 'user',
        string $label = 'User',
        array $pages = ['index', 'detail', 'form'],
        bool $multiple = false
    ): array {
        $field = $this->fieldService->field($fieldName)
            ->association(\App\Entity\User::class, fn($user) => $user->getEmail())
            ->label($label)
            ->pages($pages);

        if ($multiple) {
            $field->multiple()->countFormat($label);
        }

        return $field->build();
    }

    /**
     * Get company association field with smart defaults
     */
    protected function getCompanyAssociationField(
        string $fieldName = 'company',
        string $label = 'Company',
        array $pages = ['index', 'detail', 'form']
    ): array {
        return $this->fieldService->field($fieldName)
            ->association(\App\Entity\Company::class, 'name')
            ->label($label)
            ->pages($pages)
            ->build();
    }

    /**
     * Get status field with common options
     */
    protected function getStatusField(
        array $statusOptions = ['active' => 'Active', 'inactive' => 'Inactive'],
        array $pages = ['index', 'detail', 'form']
    ): array {
        return $this->fieldService->field('status')
            ->choices($statusOptions)
            ->label('Status')
            ->pages($pages)
            ->build();
    }

    /**
     * Create a complete CRUD field set for entities with contact info
     */
    protected function createContactEntityFields(string $entityLabel): array
    {
        $fields = [];
        
        // Standard fields
        $fields = array_merge($fields, $this->getStandardEntityFields($entityLabel));
        
        // Contact information
        $fields = array_merge($fields, $this->getContactFieldGroups());
        
        // Timestamps (if needed)
        if ($this->shouldIncludeTimestamps()) {
            $fields = array_merge($fields, $this->getTimestampFields());
        }
        
        return $fields;
    }

    /**
     * Override this in controllers to include timestamps
     */
    protected function shouldIncludeTimestamps(): bool
    {
        return false;
    }
}
