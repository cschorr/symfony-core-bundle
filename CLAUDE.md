# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Environment & Commands

### Database Management
- **Always use `bin/kickstart.sh`** to rebuild the database during the current project phase
- Do **NOT** run migrations manually - use the kickstart script which recreates the database
- The project uses DDEV for development environment

### Code Quality & Testing
- **PHPStan**: `vendor/bin/phpstan analyse` (level 8, configured in `phpstan.dist.neon`)
- **PHP CS Fixer**: `vendor/bin/php-cs-fixer fix` (PSR-12 standards)
- **PHP_CodeSniffer**: `vendor/bin/phpcs` (PSR-12 standards)
- **PHPUnit**: `vendor/bin/phpunit` (tests in `/tests` directory)
- **Rector**: `vendor/bin/rector process` (configured in `rector.php`)

### API Documentation
- **OpenAPI/Swagger**: `bin/console api:openapi:export` - Generates dynamic REST API documentation
  - Outputs OpenAPI 3.0 specification in JSON format
  - Includes all API Platform resources, operations, and schemas
  - Can be saved to file: `bin/console api:openapi:export > openapi.json`
  - View in browser: `bin/console api:openapi:export --yaml` for YAML format

- **GraphQL Schema**: `bin/console app:graphql:export` - Generates dynamic GraphQL schema documentation
  - Outputs complete GraphQL schema definition language (SDL)
  - Includes all types, queries, mutations, and subscriptions
  - Can be saved to file: `bin/console app:graphql:export > schema.graphql`
  - Auto-generated from API Platform resources and configurations

### Asset Management
- Uses **Symfony Asset Mapper** (not Webpack Encore)
- No need to run `asset-map:compile` in dev environment
- Assets are automatically handled via importmap

## Architecture Overview

### Entity System
- **AbstractEntity**: Base class with common traits (UUID, timestamps, soft delete, blameable, active status)
- **Trait-based composition**: Entities use granular traits for common functionality
  - `UuidTrait`: UUID primary keys
  - `BoolActiveTrait`: Active/inactive status
  - `BlameableEntity`: Created/updated by user tracking
  - `StringNameTrait`, `StringCodeTrait`, `StringNotesTrait`: Common string fields
  - Set traits: `SetAddressTrait`, `SetCommunicationTrait` for complex field groups

### Admin Interface (EasyAdmin v4)
- **AbstractCrudController**: Custom base controller extending EasyAdmin's AbstractCrudController
- **Permission-based access**: Domain entity permissions with voter system
- **Duplicate functionality**: Built-in entity duplication with `DuplicateService`
- **Automatic relationship syncing**: Handles bidirectional entity relationships
- **Translation support**: All UI elements are translatable

### API Platform Integration
- GraphQL and REST API endpoints auto-generated from entities
- Custom processors for write operations (`CommentWriteProcessor`, `VoteDeleteProcessor`)
- JWT authentication with refresh tokens
- CORS configuration for API access

### Key Services
- **PermissionService**: Manages domain entity permissions
- **DuplicateService**: Deep copying of entities with relationship handling  
- **NavigationService**: Dynamic admin menu generation
- **LocaleService**: Internationalization support

### Security & Authentication
- JWT-based API authentication
- Role-based access control (RBAC)
- Domain entity voter for fine-grained permissions
- User groups with permission inheritance

## Important Notes

- **EasyAdmin state management**: Pay attention to EasyAdmin's internal state when modifying admin controllers
- **Entity relationships**: Use `RelationshipSyncService` for bidirectional relationship management
- **Doctrine lifecycle**: AbstractEntity handles timestamp initialization in constructor
- **Soft delete**: All entities inherit soft delete functionality via Gedmo
- **Audit logging**: Damienharper/auditor-bundle tracks entity changes
- **Error handling**: Foreign key constraint violations are handled gracefully in admin interface