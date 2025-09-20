# C3net Core Bundle

A comprehensive Symfony bundle providing business and project management functionality with API Platform integration.

## Features

### Core Entities
- **User Management**: Complete user system with roles, permissions, and groups
- **Company & Contact Management**: Business relationship tracking
- **Project Management**: Project lifecycle with assignments and tracking
- **Category System**: Flexible categorization for entities
- **Comment System**: Threaded commenting with voting
- **Audit Logging**: Complete change tracking for all entities

### Technical Features
- **API Platform Integration**: Auto-generated REST and GraphQL APIs
- **JWT Authentication**: Secure token-based authentication with refresh tokens
- **Advanced Security**: Role-based access control with custom voters
- **Entity Traits**: Reusable traits for common functionality (UUID, timestamps, soft delete, etc.)
- **EasyAdmin Integration**: Administrative interface for entity management
- **Internationalization**: Full translation support
- **Real-time Features**: Mercure integration for live updates

## Installation

Install the bundle via Composer:

```bash
composer require c3net/core-bundle
```

## Configuration

Add the bundle to your `config/bundles.php`:

```php
<?php

return [
    // ... other bundles
    C3net\CoreBundle\C3netCoreBundle::class => ['all' => true],
];
```

### Basic Configuration

Create `config/packages/c3net_core.yaml`:

```yaml
c3net_core:
    api_platform:
        enable_swagger: true
        title: 'My API'
        version: '1.0.0'
        description: 'My API description'
    
    jwt:
        enabled: true
        ttl: 3600
        algorithm: 'RS256'
    
    audit:
        enabled: true
        ignored_columns: ['createdAt', 'updatedAt']
    
    entity_traits:
        auto_uuid: true
        auto_timestamps: true
        auto_soft_delete: true
        auto_blameable: true
    
    cors:
        enabled: true
        allow_origin: '^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

### Database Setup

Run the migrations:

```bash
php bin/console doctrine:migrations:migrate
```

Load the fixtures (optional):

```bash
php bin/console doctrine:fixtures:load
```

## Usage

### API Endpoints

The bundle automatically exposes API endpoints for all entities:

- `GET /api/users/me` - Get current user information
- `GET /api/companies` - List companies
- `GET /api/projects` - List projects
- `GET /api/contacts` - List contacts
- And many more...

### GraphQL

GraphQL endpoint is available at `/api/graphql` with full schema introspection.

### Authentication

Use JWT authentication:

```bash
# Login
curl -X POST /api/auth \
  -H "Content-Type: application/json" \
  -d '{"username":"user@example.com","password":"password"}'

# Use the returned token
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" /api/users/me
```

## Entity System

### Core Traits

All entities can use these traits:

- `UuidTrait`: UUID primary keys
- `BoolActiveTrait`: Active/inactive status
- `BlameableEntity`: Created/updated by user tracking
- `StringNameTrait`, `StringCodeTrait`, `StringNotesTrait`: Common string fields
- Set traits: `SetAddressTrait`, `SetCommunicationTrait` for complex field groups

### Custom Entities

Extend `AbstractEntity` for new entities:

```php
<?php

namespace App\Entity;

use C3net\CoreBundle\Entity\AbstractEntity;
use C3net\CoreBundle\Entity\Traits\StringNameTrait;

class MyEntity extends AbstractEntity
{
    use StringNameTrait;
    
    // Your custom properties and methods
}
```

## Security

### User Roles

Built-in user roles:
- `ROLE_USER`: Basic authenticated user
- `ROLE_ADMIN`: Administrative access
- `ROLE_SUPER_ADMIN`: Full system access

### Permissions

Fine-grained permissions per entity with voter system.

## Development

### Running Tests

```bash
vendor/bin/phpunit
```

### Code Quality

```bash
# PHP CS Fixer
vendor/bin/php-cs-fixer fix

# PHPStan
vendor/bin/phpstan analyse

# All checks
composer check-all
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

This bundle is released under the MIT License. See the bundled LICENSE file for details.

## Support

For support, please contact info@c3net.de or create an issue on GitHub.## Submodule Test
