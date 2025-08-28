# AbstractCrudController Usage Guide

## Overview

The `AbstractCrudController` provides centralized functionality for all EasyAdmin CRUD controllers in the application. It integrates permission checking using the `SystemEntityVoter` and provides common methods for managing entities with proper access control.

## Key Features

1. **Permission-based Access Control**: Automatically checks user permissions before allowing actions
2. **SystemEntity-based Security**: Each controller is associated with a specific system entity for permission checking
3. **Admin Override**: Admin users bypass permission checks and have full access
4. **Common CRUD Operations**: Standardized create, read, update, delete operations
5. **Helper Methods**: Utility methods for common tasks and entity management

## Permission Management

### Adding Permission Management to Controllers

For entities that need permission management (like User entities), the abstract controller provides built-in support:

```php
class UserCrudController extends AbstractCrudController
{
    protected function getSystemEntityName(): string
    {
        return 'Benutzer';
    }

    protected function hasPermissionManagement(): bool
    {
        return true; // Enable permission management
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm(),
            EmailField::new('email'),
            // ... other fields
        ];

        if ($pageName === Crud::PAGE_EDIT || $pageName === Crud::PAGE_NEW) {
            // Add your regular fields here
            
            // Add permission tab automatically (restores tab-based organization)
            $fields = $this->addPermissionTabToFields($fields);
        } else {
            // For index page, add permission summary
            $fields = $this->addPermissionSummaryField($fields);
        }

        return $fields;
    }
}
```

### Requirements for Permission Management

For an entity to support permission management, it needs:

1. **SystemEntity Permission Relationship**: The entity must have a relationship to `UserSystemEntityPermission`
2. **Required Methods**: `getSystemEntityPermissions()`, `addSystemEntityPermission()`, and `getSystemEntityPermissions()->clear()`
3. **Permission Entity**: The permission entity should have `setUser()`, `setSystemEntity()`, `setCanRead()`, `setCanWrite()`, etc.

### Permission Management Methods

The abstract controller provides these methods:

- `hasPermissionManagement()`: Override to return `true` for entities with permissions
- `addPermissionTabToFields($fields)`: Adds permission tab to form fields (restores tab organization)
- `createSystemEntityPermissionFields()`: Creates system entity permission form fields
- `addPermissionSummaryField($fields)`: Adds permission summary for index pages
- `handleSystemEntityPermissions($entity)`: Automatically handles permission saving

## Basic Usage

### 1. Extend AbstractCrudController

```php
<?php

namespace App\Controller\Admin;

use App\Entity\YourEntity;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class YourEntityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return YourEntity::class;
    }

    protected function getSystemEntityName(): string
    {
        return 'YourSystemEntityName'; // Must match the systemEntity name in your database
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            // ... other fields
        ];
    }
}
```

### 2. Required Implementation

Every concrete controller must implement:

- `getSystemEntityName()`: Returns the name of the systemEntity associated with this controller for permission checking

## Permission System

### Automatic Permission Checking

The abstract controller automatically checks permissions for:

- **INDEX/DETAIL**: Requires `read` permission
- **NEW/EDIT/DELETE**: Requires `write` permission

### Permission Methods

```php
// Check if user has specific permission
if ($this->hasPermission('read')) {
    // User has read access
}

// Enforce permission (throws exception if denied)
$this->checkPermission('write');

// Check if user is admin
if ($this->isAdmin()) {
    // User has admin role
}
```

### Custom Permission Logic

Override methods for custom permission logic:

```php
protected function canCreateEntity(): bool
{
    // Custom logic for entity creation
    return $this->hasPermission('write') && $this->someCustomCheck();
}

protected function canEditEntity($entity): bool
{
    // Custom logic for entity editing
    return $this->hasPermission('write') && $this->userOwnsEntity($entity);
}

protected function canDeleteEntity($entity): bool
{
    // Custom logic for entity deletion
    return $this->hasPermission('write') && !$this->hasRelatedData($entity);
}
```

## Configuration Methods

### Override CRUD Configuration

```php
public function configureCrud(Crud $crud): Crud
{
    return parent::configureCrud($crud)
        ->setPageTitle('index', 'Custom Title')
        ->setDefaultSort(['name' => 'ASC'])
        ->setPaginatorPageSize(50);
}
```

### Override Actions Configuration

```php
public function configureActions(Actions $actions): Actions
{
    $actions = parent::configureActions($actions);
    
    // Add custom actions or modify existing ones
    if ($this->isAdmin()) {
        $actions->add(Crud::PAGE_INDEX, Action::new('customAction')
            ->linkToRoute('admin_custom_action'));
    }
    
    return $actions;
}
```

## Helper Methods

### Entity Management

```php
// Get current user with proper type checking
$user = $this->getCurrentUser();

// Get systemEntitys accessible to current user
$accessibleSystemEntitys = $this->getAccessibleSystemEntitys();

// Get entity label for display
$label = $this->getEntityLabel($entity);
```

### Lifecycle Hooks

Override these methods for custom logic:

```php
protected function beforePersist($entity): void
{
    // Logic before creating new entity
    parent::beforePersist($entity);
    $entity->setCreatedBy($this->getCurrentUser());
}

protected function beforeUpdate($entity): void
{
    // Logic before updating entity
    parent::beforeUpdate($entity);
    $entity->setUpdatedBy($this->getCurrentUser());
}

protected function beforeDelete($entity): void
{
    // Logic before deleting entity
    parent::beforeDelete($entity);
    $this->logDeletion($entity);
}
```

## Advanced Examples

### Complex Permission Controller

```php
class ProjectCrudController extends AbstractCrudController
{
    protected function getSystemEntityName(): string
    {
        return 'Projekte';
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        
        // Add custom action for project approval (admin only)
        if ($this->isAdmin()) {
            $approveAction = Action::new('approve', 'Approve')
                ->linkToRoute('admin_project_approve', function ($entity) {
                    return ['id' => $entity->getId()];
                });
            $actions->add(Crud::PAGE_DETAIL, $approveAction);
        }
        
        return $actions;
    }

    protected function canEditEntity($entity): bool
    {
        // Users can only edit their own projects or if they're admin
        $user = $this->getCurrentUser();
        return $this->isAdmin() || 
               ($this->hasPermission('write') && $entity->getOwner() === $user);
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            // ... other fields
        ];

        // Add user-specific fields based on permissions
        if ($this->hasPermission('write')) {
            $fields[] = AssociationField::new('assignedUsers')
                ->setQueryBuilder(function ($queryBuilder) {
                    // Only show users from accessible systemEntitys
                    $accessibleSystemEntitys = $this->getAccessibleSystemEntitys();
                    if (!empty($accessibleSystemEntitys)) {
                        $queryBuilder->where('entity.systemEntity IN (:systemEntitys)')
                                   ->setParameter('systemEntitys', $accessibleSystemEntitys);
                    }
                    return $queryBuilder;
                });
        }

        return $fields;
    }
}
```

### Read-Only Controller

```php
class ReportCrudController extends AbstractCrudController
{
    protected function getSystemEntityName(): string
    {
        return 'Berichte';
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        
        // Disable all write operations (even for admins)
        $actions
            ->disable(Action::NEW)
            ->disable(Action::EDIT)
            ->disable(Action::DELETE)
            ->disable(Action::BATCH_DELETE);
            
        return $actions;
    }

    protected function canCreateEntity(): bool
    {
        return false; // Reports are generated, not created manually
    }

    protected function canEditEntity($entity): bool
    {
        return false; // Reports are read-only
    }

    protected function canDeleteEntity($entity): bool
    {
        return false; // Reports cannot be deleted
    }
}
```

## Best Practices

1. **SystemEntity Names**: Ensure systemEntity names in `getSystemEntityName()` match exactly with names in your database
2. **Permission Granularity**: Use `read` for viewing operations and `write` for modifications
3. **Admin Override**: Remember that admins bypass most permission checks
4. **Custom Logic**: Override permission methods for entity-specific business rules
5. **Error Handling**: The abstract controller handles permission errors gracefully
6. **Performance**: Permission checks are cached per request for better performance

## Migration from Standard Controllers

To migrate existing EasyAdmin controllers:

1. Change `extends AbstractCrudController` to `extends AbstractCrudController`
2. Add the required `getSystemEntityName()` method
3. Remove manual permission checks (now handled automatically)
4. Update constructor to include the required dependencies (handled by parent)
5. Test all CRUD operations to ensure proper permission enforcement

## Troubleshooting

### Common Issues

1. **SystemEntity Not Found**: Ensure the systemEntity name exists in your database
2. **Permission Denied**: Check that users have proper permissions assigned
3. **Admin Access**: Verify admin users have `ROLE_ADMIN` in their roles array
4. **Constructor Issues**: Don't override constructor without calling parent constructor

### Debug Permission Issues

```php
// Add temporary debug code to check permissions
public function index(AdminContext $context): Response
{
    dump([
        'systemEntity' => $this->getSystemEntityName(),
        'hasRead' => $this->hasPermission('read'),
        'hasWrite' => $this->hasPermission('write'),
        'isAdmin' => $this->isAdmin(),
        'user' => $this->getCurrentUser()?->getEmail(),
    ]);
    
    return parent::index($context);
}
```
