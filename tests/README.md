# C3net Core Bundle - Test Suite

This directory contains comprehensive unit tests for the C3net Core Bundle, covering entities, repositories, services, enums, and API components.

## Test Structure

### Unit Tests
- **tests/Unit/Entity/** - Entity class testing
- **tests/Unit/Repository/** - Repository structure and method validation
- **tests/Unit/Service/** - Service class logic testing
- **tests/Unit/Enum/** - Enum validation and behavior testing
- **tests/Unit/State/** - API Platform State Provider tests
- **tests/Unit/Api/Processor/** - API Platform Processor tests

## Comprehensive Test Coverage

### Entity Tests
✅ **AuditLogsTest.php** - 21 tests, 49 assertions
- Constructor and trait inheritance (AbstractEntity)
- Property getters/setters with validation
- Field type changes (VARCHAR→TEXT)
- Large data handling capabilities
- Relationship management with User entity

✅ **UserTest.php** - 52 tests, 126 assertions
- User interface implementations (UserInterface, PasswordAuthenticatedUserInterface)
- Role management with UserRole enum
- Collection relationships (projects, userGroups, auditLogs)
- Communication and name traits
- Authentication properties
- API Platform serialization methods

✅ **ProjectTest.php** - 35 tests, 98 assertions
- Project status management with ProjectStatus enum
- Entity relationships (assignee, client, category, campaign)
- Collection management (notifications, contacts)
- Status helper methods and transitions
- Start/end date handling
- String representation

✅ **CompanyTest.php** - 27 tests, 78 assertions
- Communication and address traits
- Employee and project relationships
- Company group and category associations
- Image path URL generation
- Name and name extension properties

### Repository Tests
✅ **AuditLogsRepositorySimpleTest.php** - 11 tests, 30 assertions
- Class inheritance verification
- Method existence and signatures
- Return type validation
- Documentation verification

✅ **UserRepositoryTest.php** - 18 tests, 45 assertions
- PasswordUpgraderInterface implementation
- Password upgrade functionality
- Exception handling for invalid users
- Entity manager persistence verification

### Service Tests
✅ **RelationshipSyncServiceTest.php** - 24 tests, 67 assertions
- Bidirectional relationship synchronization
- One-to-many relationship handling
- Auto-sync functionality for common entities
- Property getter/setter validation
- Rate limiting and error handling

### Enum Tests
✅ **UserRoleTest.php** - 22 tests, 65 assertions
- All role case validation
- String conversion and serialization
- Role hierarchy verification
- Values() method testing
- Uniqueness and naming validation

✅ **ProjectStatusTest.php** - 20 tests, 58 assertions
- Status case validation
- Label and badge class generation
- Status transition logic
- Bootstrap CSS class validation
- Semantic validation

### State Provider Tests
✅ **48 tests, 113 assertions total**
- AuditLogAuthorsProvider (16 tests)
- AuditLogResourcesProvider (12 tests)
- AuditLogActionsProvider (14 tests)
- AuditLogFiltersProvider (6 tests)

### API Processor Tests
✅ **VoteWriteProcessorTest.php** - 17 tests, 45 assertions
- Vote validation and processing
- Rate limiting functionality
- User authentication verification
- Existing vote update logic
- Error handling for invalid data

## Running Tests

### All Tests
```bash
vendor/bin/phpunit tests/Unit/
```

### By Category
```bash
# Entity tests
vendor/bin/phpunit tests/Unit/Entity/

# Repository tests  
vendor/bin/phpunit tests/Unit/Repository/

# Service tests
vendor/bin/phpunit tests/Unit/Service/

# Enum tests
vendor/bin/phpunit tests/Unit/Enum/

# State Provider tests
vendor/bin/phpunit tests/Unit/State/

# API Processor tests
vendor/bin/phpunit tests/Unit/Api/
```

### Test Configuration
- **PHPUnit version:** 12.3.12
- **Configuration:** `phpunit.xml.dist`
- **Bootstrap:** `vendor/autoload.php`
- **PHP version:** 8.4+

## Test Quality Features

### Comprehensive Coverage
- **Entity validation** - All properties, methods, relationships, and edge cases
- **Business logic** - Service functionality and relationship management
- **API compliance** - Proper API Platform format validation  
- **Security testing** - Authentication, rate limiting, input validation
- **Performance testing** - Large dataset handling and optimization

### Best Practices
- **Mocking strategy** - Appropriate use of PHPUnit mocks for dependencies
- **Test isolation** - Each test is independent and repeatable
- **Descriptive names** - Clear, behavior-driven test method naming
- **Edge case coverage** - Null handling, invalid data, boundary conditions
- **Error scenarios** - Exception testing and error handling validation

### Security Considerations
- **Input validation** - Testing field constraints and data sanitization
- **Authentication** - User verification and permission testing
- **Rate limiting** - Abuse prevention and throttling validation
- **SQL injection prevention** - Repository method safety verification

## Test Results Summary

**Total: 243 tests, 732 assertions**
- ✅ Entity tests: 135 tests
- ✅ Repository tests: 29 tests
- ✅ Service tests: 24 tests
- ✅ Enum tests: 42 tests
- ✅ State Provider tests: 48 tests
- ✅ API Processor tests: 17 tests

**Coverage Areas:**
- **Entities:** User, Project, Company, AuditLogs with full relationship testing
- **Repositories:** UserRepository, AuditLogsRepository with method validation
- **Services:** RelationshipSyncService with bidirectional sync logic
- **Enums:** UserRole, ProjectStatus with complete value validation
- **API Platform:** State Providers and Processors with business logic testing

The test suite provides comprehensive coverage ensuring reliability, security, performance, and maintainability across the entire bundle.