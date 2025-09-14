# Globe TV Backend

A comprehensive business and project management API built with Symfony and API Platform, featuring company management, project tracking, and real-time collaboration tools.

## 🚀 Quick Start

### Prerequisites
- Docker and Docker Compose
- Git

### Installation

1. **Clone and navigate to the project**:
   ```bash
   git clone <repository-url>
   cd globe-tv-backend
   ```

2. **Create environment file**:
   ```bash
   cp .env.local.example .env.local
   # Edit .env.local with your configuration
   ```

3. **Build and start containers**:
   ```bash
   docker compose build --no-cache
   docker compose up -d
   ```

4. **Install dependencies and initialize database**:
   ```bash
   docker compose exec php composer install
   docker compose exec php bin/kickstart.sh
   ```

5. **Verify installation**: Visit https://localhost/api

## ✨ Features

- **Dual API Support**: REST and GraphQL endpoints with automatic documentation
- **Authentication**: JWT-based auth with refresh tokens
- **Real-time Updates**: Mercure hub for live data synchronization
- **API-First Architecture**: Pure headless backend designed for decoupled applications
- **Entity Management**: Companies, contacts, projects with rich relationships
- **Permission System**: Role-based access control with JWT authentication
- **Audit Logging**: Complete change tracking for all entities

## 🏗️ Core Entities

- **Companies**: Business entities with contact information and address details
- **Contacts**: Individual people with position, department, and company relationships
- **Projects**: Trackable initiatives with status, timeline, and company associations
- **User Management**: Users with role-based access control and JWT authentication

## 🛠️ Development

### Docker Commands
```bash
# Start services
docker compose up -d

# View logs
docker compose logs -f php

# Execute PHP commands
docker compose exec php [command]

# Database access
docker compose exec database mariadb -u app -p!ChangeMe! app

# Stop services
docker compose down
```

### Database Management
```bash
# Rebuild database (current development phase)
docker compose exec php bin/kickstart.sh

# Direct database connection
docker compose exec database mariadb -u app -p!ChangeMe! app
```

### Code Quality & Testing
```bash
# Static analysis
docker compose exec php vendor/bin/phpstan analyse

# Code formatting
docker compose exec php vendor/bin/php-cs-fixer fix

# Run tests
docker compose exec php vendor/bin/phpunit

# Code refactoring
docker compose exec php vendor/bin/rector process
```

## 📚 API Documentation

### REST API
- **Endpoints**: https://localhost/api
- **OpenAPI Export**: `docker compose exec php bin/console api:openapi:export`
- **Features**: Pagination, filtering, sorting, embedded relationships

### GraphQL API
- **Endpoint**: https://localhost/api/graphql
- **Schema Export**: `docker compose exec php bin/console app:graphql:export`
- **Features**: Introspection, mutations, subscriptions

### Authentication
```bash
# Generate JWT keypair
docker compose exec php bin/console lexik:jwt:generate-keypair

# Obtain token via POST /api/auth
{
  "email": "user@example.com",
  "password": "password"
}
```

## 🏛️ Architecture

### Entity System
- **AbstractEntity**: Base class with UUID, timestamps, soft delete, audit trails
- **Trait Composition**: Reusable traits for common functionality (StringNameTrait, SetAddressTrait, etc.)
- **Relationships**: Bidirectional entity relationships with automatic synchronization

### Technology Stack
- **Backend**: Symfony 7 with API Platform 4
- **Database**: MariaDB 11.4.2
- **Server**: FrankenPHP 8.4 with built-in Mercure hub
- **Container**: Docker Compose development environment
- **Security**: JWT authentication, RBAC, CORS configuration

### Key Services
- **RelationshipSyncService**: Bidirectional entity relationship management
- **JWTUserService**: JWT token management and user authentication  
- **API Platform Processors**: Custom write operations and data validation

## 🚀 Deployment

### Environment Configuration
Create `.env.local` with production values:
```env
APP_SECRET=your-random-32-char-string
DATABASE_URL=mysql://user:pass@host:port/db?serverVersion=11.4.2-MariaDB
JWT_PASSPHRASE=your-jwt-passphrase
MERCURE_JWT_SECRET=your-mercure-secret
CORS_ALLOW_ORIGIN=^https://your-domain\.com$
```

### Container Access
- **API**: https://localhost (HTTPS with self-signed cert)
- **Database**: localhost:3307 (external), database:3306 (internal)
- **GraphQL**: https://localhost/api/graphql

## 🤝 Contributing

### Development Workflow
1. Create feature branch
2. Make changes following PSR-12 standards
3. Run quality checks: `vendor/bin/phpstan analyse`
4. Test changes: `vendor/bin/phpunit`
5. Submit pull request

### Code Standards
- PHP 8.4+ with strict types
- PSR-12 coding standards
- PHPStan level 8 analysis
- Comprehensive PHPUnit tests

## 📖 Documentation

For detailed development information, see [CLAUDE.md](./CLAUDE.md) which contains:
- Comprehensive setup instructions
- Container details and troubleshooting
- Architecture deep-dive
- Service descriptions
- Development best practices

## 🆘 Support

For issues and questions:
1. Check logs: `docker compose logs php`
2. Verify container status: `docker compose ps`
3. Review [CLAUDE.md](./CLAUDE.md) for detailed troubleshooting
4. Create an issue in the repository

---

Built with ❤️ using Symfony, API Platform, and modern PHP practices.