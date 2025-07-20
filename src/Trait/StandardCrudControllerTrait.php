<?php

namespace App\Trait;

use App\Service\CrudSchemaBuilder;
use App\Service\EasyAdminFieldService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;

/**
 * Comprehensive trait for standardized CRUD controller patterns
 */
trait StandardCrudControllerTrait
{
    protected CrudSchemaBuilder $schemaBuilder;
    protected EasyAdminFieldService $fieldService;

    /**
     * Abstract method to be implemented by controllers
     * Define your field schema here
     */
    abstract protected function getFieldSchema(): array;

    /**
     * Optional method for tab-based detail views
     * Override in controllers that need tabs
     */
    protected function getTabSchema(): array
    {
        return [];
    }

    /**
     * Configure fields using the field schema
     */
    public function configureFields(string $pageName): iterable
    {
        $fieldSchema = $this->getFieldSchema();
        
        // Handle tab structure for detail page
        if ($pageName === Crud::PAGE_DETAIL && method_exists($this, 'getTabSchema')) {
            $tabSchema = $this->getTabSchema();
            if (!empty($tabSchema)) {
                return $this->buildTabStructure($tabSchema, $pageName);
            }
        }
        
        // Regular field processing for other pages
        $fields = [];
        foreach ($fieldSchema as $config) {
            if ($this->shouldIncludeField($config, $pageName)) {
                $field = $this->fieldService->createFieldFromSchema($config);
                if ($field) {
                    $fields[] = $field;
                }
            }
        }
        
        // Apply custom field modifications if method exists
        if (method_exists($this, 'customizeFields')) {
            $fields = $this->customizeFields($fields, $pageName);
        }
        
        return $fields;
    }

    /**
     * Build tab structure for detail view
     */
    private function buildTabStructure(array $tabSchema, string $pageName): iterable
    {
        $allFields = [];
        
        foreach ($tabSchema as $tabConfig) {
            // Add tab divider
            $allFields[] = FormField::addTab($tabConfig['label']);
            
            // Add fields for this tab
            foreach ($tabConfig['fields'] as $fieldConfig) {
                if ($this->shouldIncludeField($fieldConfig, $pageName)) {
                    $field = $this->fieldService->createFieldFromSchema($fieldConfig);
                    if ($field) {
                        $allFields[] = $field;
                    }
                }
            }
        }
        
        return $allFields;
    }

    /**
     * Determine if field should be included for the current page
     */
    private function shouldIncludeField(array $fieldConfig, string $pageName): bool
    {
        if (!isset($fieldConfig['pages'])) {
            return true;
        }
        
        $schemaPages = $fieldConfig['pages'];
        $crudPages = $this->mapCrudPageToSchemaPages($pageName);
        
        return !empty(array_intersect($schemaPages, $crudPages));
    }

    /**
     * Map CRUD page names to schema page names
     */
    private function mapCrudPageToSchemaPages(string $crudPage): array
    {
        return match ($crudPage) {
            Crud::PAGE_INDEX => ['index'],
            Crud::PAGE_DETAIL => ['detail'],
            Crud::PAGE_NEW => ['form', 'new'],
            Crud::PAGE_EDIT => ['form', 'edit'],
            default => ['index', 'detail', 'form']
        };
    }

    /**
     * Helper method to create standard entity configuration
     */
    protected function getStandardEntityConfig(string $entityName): array
    {
        return [
            // Standard system fields
            $this->schemaBuilder->createField('id', 'id', 'ID', ['detail']),
            $this->schemaBuilder->createField('active', 'boolean', 'Active'),
            $this->schemaBuilder->createField('name', 'text', $entityName . ' Name', ['index', 'detail', 'form'], [
                'required' => true,
                'linkToShow' => true
            ]),
            
            // Standard timestamps
            $this->schemaBuilder->createField('createdAt', 'datetime', 'Created At', ['index', 'detail']),
            $this->schemaBuilder->createField('updatedAt', 'datetime', 'Updated At', ['detail']),
        ];
    }

    /**
     * Helper method to create address field group
     */
    protected function getAddressFieldConfig(array $pages = ['detail', 'form']): array
    {
        return $this->schemaBuilder->createAddressFields($pages);
    }

    /**
     * Helper method to create contact field group
     */
    protected function getContactFieldConfig(array $pages = ['detail', 'form']): array
    {
        return $this->schemaBuilder->createContactFields($pages);
    }

    /**
     * Quick method to create a standard information tab
     */
    protected function createInfoTab(string $entityName, array $additionalFields = []): array
    {
        return $this->schemaBuilder->createInfoTab($entityName, $additionalFields);
    }

    /**
     * Quick method to create a relationship tab
     */
    protected function createRelationshipTab(
        string $property,
        string $label,
        array $columns,
        bool $includeFormField = true
    ): array {
        return $this->schemaBuilder->createRelationshipTab($property, $label, $columns, $includeFormField);
    }
}
