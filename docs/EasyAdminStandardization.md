# EasyAdmin CRUD Controller Standardization

This document outlines the comprehensive strategy for creating consistent, maintainable EasyAdmin CRUD controllers using our standardized approach.

## Overview

Our standardization system consists of several components working together:

- **StandardCrudControllerTrait**: Base trait providing common CRUD patterns
- **CrudSchemaBuilder**: Service for building field and tab configurations
- **EasyAdminFieldService**: Enhanced field service with schema support
- **EmbeddedTableService**: Service for rendering native EasyAdmin tables in tabs
- **MakeEasyAdminCrudCommand**: Console command for generating standardized controllers

## Quick Start

### Generate a New CRUD Controller

```bash
# Basic controller
php bin/console make:easyadmin-crud User

# Advanced controller with tabs
php bin/console make:easyadmin-crud Company --with-tabs

# Skip relationships
php bin/console make:easyadmin-crud Product --no-relationships
```

### Manual Implementation

```php
<?php

namespace App\Controller\Admin;

use App\Entity\Company;
use App\Service\CrudSchemaBuilder;
use App\Service\EasyAdminFieldService;
use App\Controller\Admin\Traits\TabCrudControllerTrait;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class CompanyCrudController extends AbstractCrudController
{
    use TabCrudControllerTrait;

    public function __construct(
        private CrudSchemaBuilder $schemaBuilder,
        private EasyAdminFieldService $fieldService
    ) {}

    public static function getEntityFqcn(): string
    {
        return Company::class;
    }

    protected function getFieldSchema(): array
    {
        return [
            $this->schemaBuilder->createField('id', 'id', 'ID', ['detail']),
            $this->schemaBuilder->createField('active', 'boolean', 'Active'),
            $this->schemaBuilder->createField('name', 'text', 'Company Name', ['index', 'detail', 'form'], [
                'required' => true,
                'linkToShow' => true
            ]),
            // ... more fields
        ];
    }

    protected function getTabSchema(): array
    {
        return [
            $this->schemaBuilder->createInfoTab('Company'),
            $this->schemaBuilder->createRelationshipTab(
                'users',
                'Users',
                [
                    ['property' => 'name', 'label' => 'Name'],
                    ['property' => 'email', 'label' => 'Email'],
                    ['property' => 'active', 'label' => 'Status']
                ]
            ),
        ];
    }
}
```

## Field Schema Configuration

### Basic Field Configuration

```php
$this->schemaBuilder->createField(
    'property',     // Entity property name
    'type',         // Field type (text, email, boolean, etc.)
    'Label',        // Display label
    ['index', 'detail', 'form'],  // Pages where field appears
    [               // Additional options
        'required' => true,
        'help' => 'Help text',
        'linkToShow' => true
    ]
);
```

### Supported Field Types

| Type | Description | Options |
|------|-------------|---------|
| `id` | ID field | Usually detail-only |
| `text` | Text field | `truncate`, `linkToShow` |
| `textarea` | Textarea field | `truncate` |
| `email` | Email field | Auto-validation |
| `telephone` | Phone field | Auto-formatting |
| `url` | URL field | Auto-validation |
| `boolean` | Boolean field | Renders as switch |
| `datetime` | Date/time field | `format` |
| `date` | Date only | `format` |
| `country` | Country selector | ISO codes |
| `association` | Entity relationship | `multiple`, `autocomplete`, `embedded_table` |
| `choice` | Select dropdown | `choices` array |
| `number` | Numeric field | Decimal support |
| `integer` | Integer field | Whole numbers |
| `money` | Currency field | Auto-formatting |
| `percent` | Percentage field | Auto-formatting |

### Page Visibility

```php
// Field appears on these pages
['index', 'detail', 'form']

// Common patterns
['index']                    // List view only
['detail']                   // Detail view only
['form']                     // Create/edit forms only
['index', 'detail']          // Visible but not editable
['detail', 'form']           // Hidden from list view
```

### Field Options

```php
[
    'required' => true,              // Required field
    'help' => 'Help text',          // Field help text
    'linkToShow' => true,           // Link to detail view
    'truncate' => 50,               // Text truncation
    'format' => 'dd/MM/yyyy',       // Date format
    'multiple' => true,             // Multiple selection
    'autocomplete' => true,         // Autocomplete for associations
    'choices' => ['A' => 'Option A'] // Choice field options
]
```

## Tab Schema Configuration

### Information Tab

```php
$this->schemaBuilder->createInfoTab('Entity', [
    // Additional fields beyond standard ones
    $this->schemaBuilder->createField('description', 'textarea', 'Description', ['detail', 'form']),
]);
```

### Relationship Tab

```php
$this->schemaBuilder->createRelationshipTab(
    'users',                    // Property name
    'Users',                    // Tab label
    [                          // Table columns
        ['property' => 'name', 'label' => 'Name'],
        ['property' => 'email', 'label' => 'Email'],
        ['property' => 'active', 'label' => 'Status']
    ],
    true                       // Include form field
);
```

### Embedded Table Configuration

```php
$this->schemaBuilder->createEmbeddedTableField(
    'projects',
    'Projects',
    [
        ['property' => 'name', 'label' => 'Project Name'],
        ['property' => 'status', 'label' => 'Status'],
        ['property' => 'deadline', 'label' => 'Deadline']
    ],
    'Company Projects',
    'No projects assigned to this company'
);
```

## Standard Field Groups

### Standard Entity Fields

```php
// All entities should have these basic fields
$this->schemaBuilder->createStandardIndexFields('Entity', [
    // Additional custom fields
]);
```

### Address Fields

```php
...$this->schemaBuilder->createAddressFields(['detail', 'form'])
```

Includes: `street`, `zip`, `city`, `countryCode`

### Contact Fields

```php
...$this->schemaBuilder->createContactFields(['detail', 'form'])
```

Includes: `email`, `phone`, `cell`, `url`

## Best Practices

### 1. Controller Structure

```php
class EntityCrudController extends AbstractCrudController
{
    use StandardCrudControllerTrait;

    public function __construct(
        private CrudSchemaBuilder $schemaBuilder,
        private EasyAdminFieldService $fieldService
    ) {}

    // Required methods
    public static function getEntityFqcn(): string { /* ... */ }
    protected function getFieldSchema(): array { /* ... */ }
    
    // Optional methods
    protected function getTabSchema(): array { /* ... */ }
    public function configureCrud(Crud $crud): Crud { /* ... */ }
    public function configureActions(Actions $actions): Actions { /* ... */ }
}
```

### 2. Field Organization

```php
protected function getFieldSchema(): array
{
    return [
        // 1. Standard system fields
        $this->schemaBuilder->createField('id', 'id', 'ID', ['detail']),
        $this->schemaBuilder->createField('active', 'boolean', 'Active'),
        
        // 2. Primary identification fields
        $this->schemaBuilder->createField('name', 'text', 'Name', ['index', 'detail', 'form'], [
            'required' => true,
            'linkToShow' => true
        ]),
        
        // 3. Content fields
        // ... content fields
        
        // 4. Contact information
        ...$this->schemaBuilder->createContactFields(['detail', 'form']),
        
        // 5. Address information
        ...$this->schemaBuilder->createAddressFields(['detail', 'form']),
        
        // 6. Relationships
        // ... association fields
        
        // 7. Timestamps (automatically added by trait)
        $this->schemaBuilder->createField('createdAt', 'datetime', 'Created At', ['index', 'detail']),
        $this->schemaBuilder->createField('updatedAt', 'datetime', 'Updated At', ['detail']),
    ];
}
```

### 3. Tab Organization

```php
protected function getTabSchema(): array
{
    return [
        // Main information tab (required)
        $this->schemaBuilder->createInfoTab('Entity'),
        
        // Related entities (one tab per relationship)
        $this->schemaBuilder->createRelationshipTab('users', 'Users', [/* columns */]),
        $this->schemaBuilder->createRelationshipTab('projects', 'Projects', [/* columns */]),
        
        // Complex relationships might need custom tabs
        // $this->createCustomTab()
    ];
}
```

### 4. Naming Conventions

- Controllers: `{Entity}CrudController`
- Tab IDs: `{entity}_{purpose}_tab`
- Field properties: Use actual entity property names
- Labels: Human-readable, title case

### 5. Performance Considerations

- Use `linkToShow` sparingly (only for primary identification fields)
- Limit embedded table columns to essential information
- Use pagination for large datasets
- Consider lazy loading for heavy relationships

## Customization Examples

### Custom Field Processing

```php
public function configureFields(string $pageName): iterable
{
    $fields = parent::configureFields($pageName);
    
    // Custom modifications
    foreach ($fields as $field) {
        if ($field->getProperty() === 'status' && $pageName === Crud::PAGE_INDEX) {
            $field->renderAsChoice([
                'active' => 'Active',
                'inactive' => 'Inactive',
                'pending' => 'Pending'
            ]);
        }
    }
    
    return $fields;
}
```

### Custom Tab Logic

```php
protected function getTabSchema(): array
{
    $tabs = [
        $this->schemaBuilder->createInfoTab('Company'),
    ];
    
    // Conditional tabs based on user permissions
    if ($this->isGranted('ROLE_ADMIN')) {
        $tabs[] = $this->schemaBuilder->createRelationshipTab('users', 'Users', [
            ['property' => 'name', 'label' => 'Name'],
            ['property' => 'email', 'label' => 'Email']
        ]);
    }
    
    return $tabs;
}
```

## Service Configuration

### Register Services

```yaml
# config/services.yaml
services:
    App\Service\CrudSchemaBuilder:
        arguments:
            $fieldService: '@App\Service\EasyAdminFieldService'

    App\Service\EasyAdminFieldService:
        arguments:
            $translator: '@translator'
            $adminUrlGenerator: '@EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator'
            $embeddedTableService: '@App\Service\EmbeddedTableService'

    App\Service\EmbeddedTableService:
        arguments:
            $adminUrlGenerator: '@EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator'
```

## Migration Guide

### From Existing Controllers

1. Add the trait: `use StandardCrudControllerTrait;`
2. Add constructor dependencies
3. Replace `configureFields()` with `getFieldSchema()`
4. Optionally add `getTabSchema()` for tabs
5. Remove duplicated field creation code

### Example Migration

**Before:**
```php
public function configureFields(string $pageName): iterable
{
    return [
        IdField::new('id')->onlyOnDetail(),
        TextField::new('name')->setRequired(true),
        BooleanField::new('active'),
        // ... many more fields
    ];
}
```

**After:**
```php
protected function getFieldSchema(): array
{
    return [
        $this->schemaBuilder->createField('id', 'id', 'ID', ['detail']),
        $this->schemaBuilder->createField('name', 'text', 'Name', ['index', 'detail', 'form'], [
            'required' => true
        ]),
        $this->schemaBuilder->createField('active', 'boolean', 'Active'),
    ];
}
```

## Troubleshooting

### Common Issues

1. **Missing Dependencies**: Ensure all services are properly injected
2. **Tab Not Showing**: Check field schema includes 'detail' page
3. **Embedded Table Empty**: Verify relationship exists and has data
4. **Field Not Editable**: Check if 'form' is included in pages array

### Debug Mode

Enable debug mode to see generated field configurations:

```php
// In your controller
public function configureFields(string $pageName): iterable
{
    $fields = parent::configureFields($pageName);
    
    // Debug: dump field configuration
    if ($_ENV['APP_ENV'] === 'dev') {
        dump($this->getFieldSchema());
    }
    
    return $fields;
}
```
