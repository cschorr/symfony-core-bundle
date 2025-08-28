<?php

declare(strict_types=1);

namespace App\Controller\Admin\Traits;

/**
 * Trait for common field configuration patterns
 * Note: Requires the controller to have EasyAdminFieldService $fieldService property.
 */
trait FieldConfigurationTrait
{
    /**
     * Get standard entity field configuration (Active, ID, name, timestamps)
     * Active field is first for better visibility in index view.
     */
    protected function getStandardEntityFields(string $entityLabel = 'Entity'): array
    {
        return [
            ...$this->getActiveField(), // Active field first, enabled for all views by default
            $this->fieldService->createIdField(),
            $this->fieldService->createNameField($entityLabel . ' Name'),
        ];
    }

    /**
     * Get address and communication field groups.
     */
    protected function getContactFieldGroups(array $pages = ['detail', 'form']): array
    {
        $fields = [];
        $fields = array_merge($fields, $this->fieldService->createCommunicationFieldGroup($pages));
        $fields = array_merge($fields, $this->fieldService->createAddressFieldGroup($pages));

        return $fields;
    }

    /**
     * Get timestamp fields (created, updated).
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
     * Get user association field with smart defaults.
     */
    protected function getUserAssociationField(
        string $fieldName = 'user',
        string $label = 'User',
        array $pages = ['index', 'detail', 'form'],
        bool $multiple = false,
    ): array {
        $field = $this->fieldService->field($fieldName)
            ->association(\App\Entity\User::class, fn ($user) => $user->getEmail())
            ->label($label)
            ->pages($pages);

        if ($multiple) {
            $field->multiple()->countFormat($label);
        }

        return $field->build();
    }

    /**
     * Get company association field with smart defaults.
     */
    protected function getCompanyAssociationField(
        string $fieldName = 'company',
        string $label = 'Company',
        array $pages = ['index', 'detail', 'form'],
    ): array {
        return $this->fieldService->field($fieldName)
            ->association(\App\Entity\Company::class, 'name')
            ->label($label)
            ->pages($pages)
            ->build();
    }

    /**
     * Get status field with common options.
     */
    protected function getStatusField(
        array $statusOptions = ['active' => 'Active', 'inactive' => 'Inactive'],
        array $pages = ['index', 'detail', 'form'],
    ): array {
        return $this->fieldService->field('status')
            ->choices($statusOptions)
            ->label('Status')
            ->pages($pages)
            ->build();
    }

    /**
     * Create a complete CRUD field set for entities with contact info.
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
            return array_merge($fields, $this->getTimestampFields());
        }

        return $fields;
    }

    /**
     * Override this in controllers to include timestamps.
     */
    protected function shouldIncludeTimestamps(): bool
    {
        return false;
    }

    /**
     * Get user/employee related fields.
     */
    protected function getUserFields(array $pages = ['detail', 'form']): array
    {
        return [
            $this->fieldService->field('email')
                ->type('email')
                ->label('Email Address')
                ->pages($pages)
                ->build(),
            $this->fieldService->field('roles')
                ->type('choice')
                ->label('Roles')
                ->choices([
                    'User' => 'ROLE_USER',
                    'Admin' => 'ROLE_ADMIN',
                ])
                ->multiple(true)
                ->pages($pages)
                ->build(),
        ];
    }

    /**
     * Get project-related fields.
     */
    protected function getProjectFields(array $pages = ['detail', 'form']): array
    {
        return [
            $this->fieldService->field('description')
                ->type('textarea')
                ->label('Description')
                ->pages($pages)
                ->build(),
            $this->fieldService->field('startDate')
                ->type('date')
                ->label('Start Date')
                ->pages($pages)
                ->build(),
            $this->fieldService->field('endDate')
                ->type('date')
                ->label('End Date')
                ->pages($pages)
                ->build(),
            $this->fieldService->field('status')
                ->type('choice')
                ->label('Status')
                ->choices([
                    'Planning' => 'planning',
                    'Active' => 'active',
                    'On Hold' => 'on_hold',
                    'Completed' => 'completed',
                    'Cancelled' => 'cancelled',
                ])
                ->pages($pages)
                ->build(),
        ];
    }

    /**
     * Get common association fields.
     */
    protected function getAssociationFields(array $associations, array $pages = ['detail', 'form']): array
    {
        $fields = [];

        foreach ($associations as $association => $config) {
            $fieldBuilder = $this->fieldService->field($association)
                ->type('association')
                ->pages($pages);

            if (isset($config['label'])) {
                $fieldBuilder->label($config['label']);
            }

            if (isset($config['autocomplete']) && $config['autocomplete']) {
                $fieldBuilder->autocomplete(true);
            }

            if (isset($config['multiple']) && $config['multiple']) {
                $fieldBuilder->multiple(true);
            }

            $fields[] = $fieldBuilder->build();
        }

        return $fields;
    }

    /**
     * Get notes/description field.
     */
    protected function getNotesField(array $pages = ['detail', 'form'], string $label = 'Notes'): array
    {
        return [
            $this->fieldService->field('notes')
                ->type('textarea')
                ->label($label)
                ->pages($pages)
                ->build(),
        ];
    }

    /**
     * Get active/status field for all pages by default.
     */
    protected function getActiveField(array $pages = ['index', 'detail', 'form']): array
    {
        return [
            $this->fieldService->field('active')
                ->type('boolean')
                ->label('Active')
                ->pages($pages)
                ->build(),
        ];
    }

    /**
     * Get active field specifically for first position in index view.
     *
     * @deprecated Use getActiveField() instead - it now defaults to all pages
     */
    protected function getActiveFieldFirst(array $pages = ['index', 'detail', 'form']): array
    {
        return $this->getActiveField($pages);
    }

    /**
     * Create a complete basic entity configuration.
     */
    protected function getBasicEntityConfiguration(string $entityLabel, array $additionalFields = []): array
    {
        $config = [
            ...$this->getStandardEntityFields($entityLabel), // Already includes active field first
            ...$additionalFields,
            ...$this->getNotesField(),
            ...$this->getTimestampFields(),
        ];

        return $config;
    }

    /**
     * Create a panel/tab structure for form organization.
     */
    protected function createFormStructure(array $sections, array $pages = ['form']): array
    {
        $fields = [];

        foreach ($sections as $sectionName => $sectionConfig) {
            $icon = $sectionConfig['icon'] ?? 'fas fa-folder';
            $isTab = $sectionConfig['type'] ?? 'panel' === 'tab';

            if ($isTab) {
                $fields[] = $this->fieldService->createTabConfig($sectionName, $sectionConfig['label'] ?? $sectionName);
            } else {
                $fields[] = $this->fieldService->createPanelConfig($sectionName, $sectionConfig['label'] ?? $sectionName, $pages, $icon);
            }

            if (isset($sectionConfig['fields'])) {
                $fields = array_merge($fields, $sectionConfig['fields']);
            }
        }

        return $fields;
    }
}
