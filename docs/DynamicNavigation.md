# Dynamic Navigation System

## Overview

The admin sidebar navigation is now dynamically generated based on:
1. **SystemEntity Active Status**: Only active systemEntitys appear in navigation
2. **User Permissions**: Users only see systemEntitys they have read or write access to
3. **Admin Override**: Admin users (ROLE_ADMIN) see all active systemEntitys regardless of permissions

## How It Works

### 1. SystemEntity Management
- SystemEntitys can be activated/deactivated via the SystemEntity CRUD interface
- Inactive systemEntitys are hidden from navigation for ALL users
- SystemEntity names are automatically translated using the translation system

### 2. User Permissions
- Regular users only see systemEntitys they have permissions for
- At least READ permission is required for a systemEntity to appear in navigation
- Admin users bypass permission checks and see all active systemEntitys

### 3. Navigation Service
The `NavigationService` handles all navigation logic:

```php
// Get systemEntitys accessible to a user
$accessibleSystemEntitys = $navigationService->getAccessibleSystemEntitysForUser($user);

// Check if user can access a specific module
$canAccess = $navigationService->canUserAccessSystemEntity($user, 'Company');

// Check if user is admin
$isAdmin = $navigationService->isUserAdmin($user);
```

## Configuration

### Adding New SystemEntitys

1. **Create the entity and CRUD controller**
2. **Add to entity mapping** in `NavigationService::getSystemEntityEntityMapping()`:
```php
'YourSystemEntity' => \App\Entity\YourSystemEntity::class,
```

3. **Add icon mapping** in `NavigationService::getSystemEntityIconMapping()`:
```php
'YourSystemEntity' => 'fas fa-your-icon',
```

4. **Create systemEntity record** in the database:
```php
$systemEntity = new SystemEntity();
$systemEntity->setName('Your DomainEntityPermission Name');
$systemEntity->setCode('YourSystemEntity'); // Must match entity mapping key
$systemEntity->setText('Description of your systemEntity');
$systemEntity->setActive(true);
```

5. **Set user permissions** for the new module

### SystemEntity Status
- **Active**: SystemEntity appears in navigation (if user has permissions)
- **Inactive**: SystemEntity is hidden from navigation for ALL users

### User Types
- **Admin (ROLE_ADMIN)**: Sees all active systemEntitys
- **Regular User**: Sees only active systemEntitys with permissions

## Database Schema

### SystemEntitys Table
- `name`: Display name (translatable)
- `code`: Unique identifier matching entity class name
- `text`: Description
- `active`: Boolean status

### UserSystemEntityPermission Table
- `user_id`: Reference to user
- `system_entity_id`: Reference to system entity
- `can_read`: Boolean read permission
- `can_write`: Boolean write permission

## Benefits

1. **Security**: Users only see what they're allowed to access
2. **Flexibility**: SystemEntitys can be disabled system-wide
3. **Scalability**: New systemEntitys automatically integrate
4. **User Experience**: Clean, personalized navigation
5. **Administration**: Easy systemEntity and permission management

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
- Automatically generated based on active systemEntitys and user permissions
- No manual menu item configuration needed
- Centralized icon and entity mapping configuration
