<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\State;

use ApiPlatform\Metadata\Operation;
use C3net\CoreBundle\Repository\AuditLogsRepository;
use C3net\CoreBundle\State\AuditLogFiltersProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuditLogFiltersProviderTest extends TestCase
{
    private AuditLogFiltersProvider $provider;
    private AuditLogsRepository&MockObject $repository;
    private Operation&MockObject $operation;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(AuditLogsRepository::class);
        $this->operation = $this->createMock(Operation::class);
        $this->provider = new AuditLogFiltersProvider($this->repository);
    }

    public function testProvideReturnsCombinedFilters(): void
    {
        $authorsResult = [
            [
                'author_id' => 1,
                'id' => 1,
                'email' => 'john@example.com',
                'firstname' => 'John',
                'lastname' => 'Doe',
            ],
            [
                'author_id' => 2,
                'id' => 2,
                'email' => 'jane@example.com',
                'firstname' => 'Jane',
                'lastname' => 'Smith',
            ],
        ];

        $resourcesResult = [
            ['resource' => 'User'],
            ['resource' => 'Project'],
            ['resource' => 'Company'],
        ];

        $actionsResult = [
            ['action' => 'create'],
            ['action' => 'update'],
            ['action' => 'delete'],
        ];

        $expectedResult = [
            'authors' => [
                [
                    '@id' => '/api/users/1',
                    '@type' => 'User',
                    'id' => 1,
                    'email' => 'john@example.com',
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'fullname' => 'John Doe',
                ],
                [
                    '@id' => '/api/users/2',
                    '@type' => 'User',
                    'id' => 2,
                    'email' => 'jane@example.com',
                    'firstname' => 'Jane',
                    'lastname' => 'Smith',
                    'fullname' => 'Jane Smith',
                ],
            ],
            'resources' => ['User', 'Project', 'Company'],
            'actions' => ['create', 'update', 'delete'],
        ];

        $this->repository
            ->expects($this->once())
            ->method('findUniqueAuthors')
            ->willReturn($authorsResult);

        $this->repository
            ->expects($this->once())
            ->method('findUniqueResources')
            ->willReturn($resourcesResult);

        $this->repository
            ->expects($this->once())
            ->method('findUniqueActions')
            ->willReturn($actionsResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideReturnsEmptyFiltersWhenNoData(): void
    {
        $this->repository
            ->method('findUniqueAuthors')
            ->willReturn([]);

        $this->repository
            ->method('findUniqueResources')
            ->willReturn([]);

        $this->repository
            ->method('findUniqueActions')
            ->willReturn([]);

        $expectedResult = [
            'authors' => [],
            'resources' => [],
            'actions' => [],
        ];

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideHandlesPartialData(): void
    {
        $authorsResult = [
            [
                'author_id' => 1,
                'id' => 1,
                'email' => 'john@example.com',
                'firstname' => 'John',
                'lastname' => 'Doe',
            ],
        ];

        $this->repository
            ->method('findUniqueAuthors')
            ->willReturn($authorsResult);

        $this->repository
            ->method('findUniqueResources')
            ->willReturn([]); // Empty resources

        $this->repository
            ->method('findUniqueActions')
            ->willReturn([['action' => 'create']]); // Only one action

        $expectedResult = [
            'authors' => [
                [
                    '@id' => '/api/users/1',
                    '@type' => 'User',
                    'id' => 1,
                    'email' => 'john@example.com',
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'fullname' => 'John Doe',
                ],
            ],
            'resources' => [],
            'actions' => ['create'],
        ];

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideHandlesNullNamesInAuthors(): void
    {
        $authorsResult = [
            [
                'author_id' => 1,
                'id' => 1,
                'email' => 'john@example.com',
                'firstname' => null,
                'lastname' => null,
            ],
        ];

        $this->repository
            ->method('findUniqueAuthors')
            ->willReturn($authorsResult);

        $this->repository
            ->method('findUniqueResources')
            ->willReturn([]);

        $this->repository
            ->method('findUniqueActions')
            ->willReturn([]);

        $result = $this->provider->provide($this->operation);

        $expectedAuthor = $result['authors'][0];
        $this->assertSame('', $expectedAuthor['fullname']);
        $this->assertNull($expectedAuthor['firstname']);
        $this->assertNull($expectedAuthor['lastname']);
    }

    public function testProvideFormatsAuthorsCorrectly(): void
    {
        $authorsResult = [
            [
                'author_id' => 123,
                'id' => 123,
                'email' => 'test@example.com',
                'firstname' => 'Test',
                'lastname' => 'User',
            ],
        ];

        $this->repository
            ->method('findUniqueAuthors')
            ->willReturn($authorsResult);

        $this->repository
            ->method('findUniqueResources')
            ->willReturn([]);

        $this->repository
            ->method('findUniqueActions')
            ->willReturn([]);

        $result = $this->provider->provide($this->operation);

        $author = $result['authors'][0];

        // Verify API Platform format
        $this->assertArrayHasKey('@id', $author);
        $this->assertArrayHasKey('@type', $author);
        $this->assertSame('/api/users/123', $author['@id']);
        $this->assertSame('User', $author['@type']);

        // Verify all expected fields
        $this->assertArrayHasKey('id', $author);
        $this->assertArrayHasKey('email', $author);
        $this->assertArrayHasKey('firstname', $author);
        $this->assertArrayHasKey('lastname', $author);
        $this->assertArrayHasKey('fullname', $author);

        $this->assertSame(123, $author['id']);
        $this->assertSame('test@example.com', $author['email']);
        $this->assertSame('Test', $author['firstname']);
        $this->assertSame('User', $author['lastname']);
        $this->assertSame('Test User', $author['fullname']);
    }

    public function testProvideAcceptsOptionalParameters(): void
    {
        $this->repository
            ->method('findUniqueAuthors')
            ->willReturn([]);

        $this->repository
            ->method('findUniqueResources')
            ->willReturn([]);

        $this->repository
            ->method('findUniqueActions')
            ->willReturn([]);

        // Test with all optional parameters
        $result = $this->provider->provide(
            $this->operation,
            ['id' => 123], // uriVariables
            ['some' => 'context'] // context
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('authors', $result);
        $this->assertArrayHasKey('resources', $result);
        $this->assertArrayHasKey('actions', $result);
    }

    public function testProvideImplementsCorrectInterface(): void
    {
        $this->assertInstanceOf(
            \ApiPlatform\State\ProviderInterface::class,
            $this->provider
        );
    }

    public function testProvideCallsAllRepositoryMethods(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findUniqueAuthors')
            ->willReturn([]);

        $this->repository
            ->expects($this->once())
            ->method('findUniqueResources')
            ->willReturn([]);

        $this->repository
            ->expects($this->once())
            ->method('findUniqueActions')
            ->willReturn([]);

        $this->provider->provide($this->operation);
    }

    public function testProvideReturnsCorrectStructure(): void
    {
        $this->repository
            ->method('findUniqueAuthors')
            ->willReturn([]);

        $this->repository
            ->method('findUniqueResources')
            ->willReturn([]);

        $this->repository
            ->method('findUniqueActions')
            ->willReturn([]);

        $result = $this->provider->provide($this->operation);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey('authors', $result);
        $this->assertArrayHasKey('resources', $result);
        $this->assertArrayHasKey('actions', $result);

        $this->assertIsArray($result['authors']);
        $this->assertIsArray($result['resources']);
        $this->assertIsArray($result['actions']);
    }

    public function testProvideHandlesLargeDatasets(): void
    {
        // Generate large datasets
        $authorsResult = [];
        $resourcesResult = [];
        $actionsResult = [];

        for ($i = 1; $i <= 100; ++$i) {
            $authorsResult[] = [
                'author_id' => $i,
                'id' => $i,
                'email' => "user{$i}@example.com",
                'firstname' => "First{$i}",
                'lastname' => "Last{$i}",
            ];
        }

        for ($i = 1; $i <= 50; ++$i) {
            $resourcesResult[] = ['resource' => "Resource{$i}"];
            $actionsResult[] = ['action' => "action{$i}"];
        }

        $this->repository
            ->method('findUniqueAuthors')
            ->willReturn($authorsResult);

        $this->repository
            ->method('findUniqueResources')
            ->willReturn($resourcesResult);

        $this->repository
            ->method('findUniqueActions')
            ->willReturn($actionsResult);

        $result = $this->provider->provide($this->operation);

        $this->assertCount(100, $result['authors']);
        $this->assertCount(50, $result['resources']);
        $this->assertCount(50, $result['actions']);
    }

    public function testProvideHandlesComplexNames(): void
    {
        $authorsResult = [
            [
                'author_id' => 1,
                'id' => 1,
                'email' => 'user@example.com',
                'firstname' => '  John  ',
                'lastname' => '  Doe  ',
            ],
        ];

        $resourcesResult = [
            ['resource' => 'App\\Entity\\User'],
            ['resource' => 'Custom-Resource_Type'],
        ];

        $actionsResult = [
            ['action' => 'soft_delete'],
            ['action' => 'bulk:update'],
            ['action' => 'export/csv'],
        ];

        $this->repository
            ->method('findUniqueAuthors')
            ->willReturn($authorsResult);

        $this->repository
            ->method('findUniqueResources')
            ->willReturn($resourcesResult);

        $this->repository
            ->method('findUniqueActions')
            ->willReturn($actionsResult);

        $result = $this->provider->provide($this->operation);

        // Check that spaces in names are handled by trim()
        $this->assertSame('John     Doe', $result['authors'][0]['fullname']);

        // Check that complex resource and action names are preserved
        $this->assertContains('App\\Entity\\User', $result['resources']);
        $this->assertContains('Custom-Resource_Type', $result['resources']);
        $this->assertContains('soft_delete', $result['actions']);
        $this->assertContains('bulk:update', $result['actions']);
        $this->assertContains('export/csv', $result['actions']);
    }
}
