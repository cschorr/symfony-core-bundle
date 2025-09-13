# GitHub Actions Workflows

This directory contains automated workflows for code quality, testing, and maintenance.

## Workflows Overview

### 🔍 `code-quality.yml`
**Triggers**: Push to main/develop, Pull Requests  
**Purpose**: Code quality and static analysis checks

**Composer Scripts Used**:
- `composer composer-validate` - Validate composer.json structure
- `composer security-audit` - Check for security vulnerabilities
- `composer cs-check` - PSR-12 code style validation (dry-run)
- `composer phpstan` - Static analysis at level 8
- `composer phpcs` - Additional code style checks
- `composer rector-check` - PHP upgrade suggestions (dry-run)

### 🧪 `integration-tests.yml`
**Triggers**: Push to main, Pull Requests  
**Purpose**: Comprehensive integration testing with Docker

**Features**:
- Docker container testing with BuildKit caching
- HTTP/HTTPS/Mercure endpoint reachability tests
- Database creation and migration testing
- `composer test` - PHPUnit test execution
- Doctrine schema validation
- Docker lint checks

### ⚡ `unit-tests.yml`
**Triggers**: Push to main, Pull Requests  
**Purpose**: Fast unit testing without Docker

**Features**:
- Native PHP 8.4 environment (lightweight)
- Fast SQLite-based testing
- Composer dependency caching
- `composer test` - PHPUnit execution

### 🚀 `deployment.yml`
**Triggers**: Manual workflow dispatch  
**Purpose**: Release management and deployment

## Workflow Configuration

### Consistency with Local Development
All workflows now use the same **composer scripts** that developers use locally, ensuring:
- ✅ **Identical tool configurations** between local and CI environments
- ✅ **Same command syntax** for developers and automated workflows  
- ✅ **Centralized script management** in `composer.json`
- ✅ **Easy maintenance** - update once, works everywhere

### Required Secrets
No additional secrets are required - workflows use the default `GITHUB_TOKEN`.

### Environment Variables
- `PHP_VERSION`: Dynamically set during matrix builds
- `STAGING_API_URL`: Optional for API documentation

### Docker Requirements
All workflows use the existing `docker-compose.yml` configuration:
- MariaDB 11.4.2 database
- FrankenPHP 8.4 web server
- All development dependencies

## Status Badges

Add these badges to your main README.md:

```markdown
[![Code Quality](https://github.com/cschorr/symfony-core-kickstarter/workflows/Code%20Quality/badge.svg)](https://github.com/cschorr/symfony-core-kickstarter/actions/workflows/code-quality.yml)
[![Integration Tests](https://github.com/cschorr/symfony-core-kickstarter/workflows/Integration%20Tests/badge.svg)](https://github.com/cschorr/symfony-core-kickstarter/actions/workflows/integration-tests.yml)
[![Unit Tests](https://github.com/cschorr/symfony-core-kickstarter/workflows/Unit%20Tests/badge.svg)](https://github.com/cschorr/symfony-core-kickstarter/actions/workflows/unit-tests.yml)
[![Deployment](https://github.com/cschorr/symfony-core-kickstarter/workflows/Deployment/badge.svg)](https://github.com/cschorr/symfony-core-kickstarter/actions/workflows/deployment.yml)
```

## Local Development

To run the same quality checks locally:

```bash
# Code style check
docker compose exec php vendor/bin/php-cs-fixer fix --dry-run --diff

# Static analysis
docker compose exec php vendor/bin/phpstan analyse

# Code standards
docker compose exec php vendor/bin/phpcs

# Tests
docker compose exec php vendor/bin/phpunit

# Security check
docker compose exec php composer audit
```

## Troubleshooting

### Common Issues

1. **Services not starting**: Ensure Docker is running and ports are available
2. **Database connection errors**: Wait for MariaDB to fully initialize
3. **Permission issues**: Check file permissions in the container
4. **Memory issues**: Increase Docker memory allocation for large codebases

### Debugging Workflows

- Check workflow logs in GitHub Actions tab
- Run commands locally using the same Docker setup
- Verify `docker-compose.yml` configuration matches CI environment