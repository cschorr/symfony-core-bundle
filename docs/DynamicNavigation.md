# Dynamic Navigation System

## Overview

The admin sidebar navigation is now dynamically generated based on:
1. **Module Active Status**: Only active modules appear in navigation
2. **User Permissions**: Users only see modules they have read or write access to
3. **Admin Override**: Admin users (ROLE_ADMIN) see all active modules regardless of permissions

## How It Works

### 1. Module Management
- Modules can be activated/deactivated via the Module CRUD interface
- Inactive modules are hidden from navigation for ALL users
- Module names are automatically translated using the translation system

### 2. User Permissions
- Regular users only see modules they have permissions for
- At least READ permission is required for a module to appear in navigation
- Admin users bypass permission checks and see all active modules

### 3. Navigation Service
The `NavigationService` handles all navigation logic:

```php
// Get modules accessible to a user
$accessibleModules = $navigationService->getAccessibleModulesForUser($user);

// Check if user can access a specific module
$canAccess = $navigationService->canUserAccessModule($user, 'Company');

// Check if user is admin
$isAdmin = $navigationService->isUserAdmin($user);
```

## Configuration

### Adding New Modules

1. **Create the entity and CRUD controller**
2. **Add to entity mapping** in `NavigationService::getModuleEntityMapping()`:
```php
'YourModule' => \App\Entity\YourModule::class,
```

3. **Add icon mapping** in `NavigationService::getModuleIconMapping()`:
```php
'YourModule' => 'fas fa-your-icon',
```

4. **Create module record** in the database:
```php
$module = new Module();
$module->setName('Your Module Name');
$module->setCode('YourModule'); // Must match entity mapping key
$module->setText('Description of your module');
$module->setActive(true);
```

5. **Set user permissions** for the new module

### Module Status
- **Active**: Module appears in navigation (if user has permissions)
- **Inactive**: Module is hidden from navigation for ALL users

### User Types
- **Admin (ROLE_ADMIN)**: Sees all active modules
- **Regular User**: Sees only active modules with permissions

## Database Schema

### Modules Table
- `name`: Display name (translatable)
- `code`: Unique identifier matching entity class name
- `text`: Description
- `active`: Boolean status

### UserModulePermission Table
- `user_id`: Reference to user
- `module_id`: Reference to module
- `can_read`: Boolean read permission
- `can_write`: Boolean write permission

## Benefits

1. **Security**: Users only see what they're allowed to access
2. **Flexibility**: Modules can be disabled system-wide
3. **Scalability**: New modules automatically integrate
4. **User Experience**: Clean, personalized navigation
5. **Administration**: Easy module and permission management

## Testing

Run tests to verify functionality:
```bash
ddev exec ./bin/phpunit tests/Service/NavigationServiceTest.php
```

## Migration from Static Navigation

Old static navigation:
```php
yield MenuItem::linkToCrud('Users', 'fas fa-users', User::class);
```

New dynamic navigation:
- Automatically generated based on active modules and user permissions
- No manual menu item configuration needed
- Centralized icon and entity mapping configuration
