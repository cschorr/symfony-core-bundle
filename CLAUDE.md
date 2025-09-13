# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Initial Setup

### Prerequisites
- Docker and Docker Compose installed
- Git repository cloned

### First-Time Setup
1. **Create environment file**: Copy `.env.local.example` to `.env.local` or create a new `.env.local` file with:
   ```bash
   # Symfony framework
   APP_SECRET=your-random-32-char-string

   # Database connection (Docker Compose)
   DATABASE_URL="mysql://app:!ChangeMe!@database:3306/app?serverVersion=11.4.2-MariaDB&charset=utf8mb4"

   # JWT Authentication (preserve existing values from .env if available)
   JWT_PASSPHRASE="your-existing-jwt-passphrase"

   # Mercure (preserve existing values from .env if available)
   MERCURE_JWT_SECRET="your-existing-mercure-secret"

   # Development CORS settings
   CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
   TRUSTED_HOSTS='^(localhost|php|127\.0\.0\.1)$'
   ```

2. **Build and start containers**:
   ```bash
   docker compose build --no-cache
   docker compose up -d
   ```

3. **Install PHP dependencies**:
   ```bash
   docker compose exec php composer install
   ```

4. **Initialize database**:
   ```bash
   docker compose exec php chmod +x bin/kickstart.sh  # Make executable if needed
   docker compose exec php bin/kickstart.sh
   ```

5. **Verify setup**: Visit https://localhost/api to see the API entry point

## Development Environment & Commands

### Project Structure
- **Standard Symfony structure**: All API Platform files are in the root directory (no `/api` subfolder)
- **Database**: MariaDB 11.4.2 (switched from PostgreSQL)
- **Web Server**: FrankenPHP 8.4 (upgraded from PHP-FPM 8.3)
- **Docker**: Development environment using Docker Compose with FrankenPHP

### Database Management
- **Always use `bin/kickstart.sh`** to rebuild the database during the current project phase
- Do **NOT** run migrations manually - use the kickstart script which recreates the database
- **Script permissions**: The kickstart script may need execute permissions: `docker compose exec php chmod +x bin/kickstart.sh`
- **Container access**: Database runs on internal port 3306, external port 3307
- **Internal connection** (container to container): `mysql://app:!ChangeMe!@database:3306/app?serverVersion=11.4.2-MariaDB&charset=utf8mb4`
- **External connection** (host to container): `mysql://app:!ChangeMe!@localhost:3307/app`
- **Direct database access**: `docker compose exec database mariadb -u app -p!ChangeMe! app`
- **Database GUI tools**: Connect to `localhost:3307` with credentials `app` / `!ChangeMe!`

### Docker Commands
- **Start services**: `docker compose up -d`
- **Stop services**: `docker compose down`
- **Build/rebuild**: `docker compose build --no-cache`
- **Restart services**: `docker compose restart`
- **View logs**: `docker compose logs [service_name]` (e.g., `php`, `database`)
- **Follow logs**: `docker compose logs -f php`
- **Execute PHP commands**: `docker compose exec php [command]`
- **Database access**: `docker compose exec database mariadb -u app -p!ChangeMe! app`
- **Container status**: `docker compose ps`

### Container Information
- **PHP Container**: `globe-backend-php` - FrankenPHP server with PHP 8.4
  - HTTPS: https://localhost:443
  - HTTP: http://localhost:80 (redirects to HTTPS)
- **Database Container**: `globe-backend-database` - MariaDB 11.4.2
  - Internal port: 3306 (container to container)
  - External port: 3307 (host to container access)

### Troubleshooting
- **PHP container restarting**: Check logs with `docker compose logs php` - usually missing dependencies or configuration issues
- **Permission denied on kickstart.sh**: Run `docker compose exec php chmod +x bin/kickstart.sh`
- **Database connection refused**: Ensure database container is healthy with `docker compose ps`
- **HTTPS certificate warnings**: Normal for development - FrankenPHP uses self-signed certificates

### Code Quality & Testing
- **PHPStan**: `docker compose exec php vendor/bin/phpstan analyse` (level 8, configured in `phpstan.dist.neon`)
- **PHP CS Fixer**: `docker compose exec php vendor/bin/php-cs-fixer fix` (PSR-12 standards)
- **PHP_CodeSniffer**: `docker compose exec php vendor/bin/phpcs` (PSR-12 standards)
- **PHPUnit**: `docker compose exec php vendor/bin/phpunit` (tests in `/tests` directory)
- **Rector**: `docker compose exec php vendor/bin/rector process` (configured in `rector.php`)

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

### Environment Configuration
- **Environment Files Loading Order** (later files override earlier ones):
  1. `.env` - Default values (committed to git)
  2. `.env.local` - Local overrides (NOT committed, in `.gitignore`)
  3. `.env.$APP_ENV` - Environment-specific defaults (e.g., `.env.dev`, committed)
  4. `.env.$APP_ENV.local` - Environment-specific local overrides (NOT committed)

- **Important**: The committed `.env` file contains secrets and should not be used directly for local development. Always create `.env.local` for local configuration.

- **Required Local Configuration**:
  - `APP_SECRET`: Generate with `openssl rand -base64 32`
  - `DATABASE_URL`: Use Docker Compose database connection
  - `JWT_PASSPHRASE`: Preserve from existing `.env` or generate new
  - `MERCURE_JWT_SECRET`: Preserve from existing `.env` or generate new

### Development Workflow
1. **Daily startup**:
   ```bash
   docker compose up -d
   ```

2. **View application**: https://localhost (auto-redirects from HTTP)

3. **API endpoints**: https://localhost/api (shows available endpoints)

4. **Database changes**: Always use `docker compose exec php bin/kickstart.sh` to rebuild

5. **View logs**: `docker compose logs -f php` to monitor application

6. **Stop environment**:
   ```bash
   docker compose down
   ```

7. **Clean rebuild** (if issues occur):
   ```bash
   docker compose down
   docker compose build --no-cache
   docker compose up -d
   docker compose exec php bin/kickstart.sh
   ```

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
- **FrankenPHP**: Modern PHP application server with built-in HTTP/2, HTTP/3, and Mercure support
- **Mercure Hub**: Real-time features with Server-Sent Events built into FrankenPHP
- GraphQL and REST API endpoints auto-generated from entities
- Custom processors for write operations (`CommentWriteProcessor`, `VoteDeleteProcessor`)
- JWT authentication with refresh tokens
- CORS configuration for API access
- **Caddy**: Automatic HTTPS with self-signed certificates for development

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