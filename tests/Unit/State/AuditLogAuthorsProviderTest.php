<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\State;

use ApiPlatform\Metadata\Operation;
use C3net\CoreBundle\Repository\AuditLogsRepository;
use C3net\CoreBundle\State\AuditLogAuthorsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuditLogAuthorsProviderTest extends TestCase
{
    private AuditLogAuthorsProvider $provider;
    private AuditLogsRepository&MockObject $repository;
    private Operation&MockObject $operation;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(AuditLogsRepository::class);
        $this->operation = $this->createMock(Operation::class);
        $this->provider = new AuditLogAuthorsProvider($this->repository);
    }

    public function testProvideReturnsFormattedAuthors(): void
    {
        $repositoryResult = [
            [
                'author_id' => 1,
                'id' => 1,
                'email' => 'john@example.com',
                'firstname' => 'John',
                'lastname' => 'Doe'
            ],
            [
                'author_id' => 2,
                'id' => 2,
                'email' => 'jane@example.com',
                'firstname' => 'Jane',
                'lastname' => 'Smith'
            ]
        ];

        $expectedResult = [
            [
                '@id' => '/api/users/1',
                '@type' => 'User',
                'id' => 1,
                'email' => 'john@example.com',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'fullname' => 'John Doe'
            ],
            [
                '@id' => '/api/users/2',
                '@type' => 'User',
                'id' => 2,
                'email' => 'jane@example.com',
                'firstname' => 'Jane',
                'lastname' => 'Smith',
                'fullname' => 'Jane Smith'
            ]
        ];

        $this->repository
            ->expects($this->once())
            ->method('findUniqueAuthors')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideReturnsEmptyArrayWhenNoAuthors(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findUniqueAuthors')
            ->willReturn([]);

        $result = $this->provider->provide($this->operation);

        $this->assertSame([], $result);
    }

    public function testProvideHandlesNullFirstname(): void
    {
        $repositoryResult = [
            [
                'author_id' => 1,
                'id' => 1,
                'email' => 'john@example.com',
                'firstname' => null,
                'lastname' => 'Doe'
            ]
        ];

        $expectedResult = [
            [
                '@id' => '/api/users/1',
                '@type' => 'User',
                'id' => 1,
                'email' => 'john@example.com',
                'firstname' => null,
                'lastname' => 'Doe',
                'fullname' => 'Doe'
            ]
        ];

        $this->repository
            ->expects($this->once())
            ->method('findUniqueAuthors')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideHandlesNullLastname(): void
    {
        $repositoryResult = [
            [
                'author_id' => 1,
                'id' => 1,
                'email' => 'john@example.com',
                'firstname' => 'John',
                'lastname' => null
            ]
        ];

        $expectedResult = [
            [
                '@id' => '/api/users/1',
                '@type' => 'User',
                'id' => 1,
                'email' => 'john@example.com',
                'firstname' => 'John',
                'lastname' => null,
                'fullname' => 'John'
            ]
        ];

        $this->repository
            ->expects($this->once())
            ->method('findUniqueAuthors')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideHandlesBothNullNames(): void
    {
        $repositoryResult = [
            [
                'author_id' => 1,
                'id' => 1,
                'email' => 'john@example.com',
                'firstname' => null,
                'lastname' => null
            ]
        ];

        $expectedResult = [
            [
                '@id' => '/api/users/1',
                '@type' => 'User',
                'id' => 1,
                'email' => 'john@example.com',
                'firstname' => null,
                'lastname' => null,
                'fullname' => ''
            ]
        ];

        $this->repository
            ->expects($this->once())
            ->method('findUniqueAuthors')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideHandlesEmptyNames(): void
    {
        $repositoryResult = [
            [
                'author_id' => 1,
                'id' => 1,
                'email' => 'john@example.com',
                'firstname' => '',
                'lastname' => ''
            ]
        ];

        $expectedResult = [
            [
                '@id' => '/api/users/1',
                '@type' => 'User',
                'id' => 1,
                'email' => 'john@example.com',
                'firstname' => '',
                'lastname' => '',
                'fullname' => ''
            ]
        ];

        $this->repository
            ->expects($this->once())
            ->method('findUniqueAuthors')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideGeneratesCorrectApiPlatformFormat(): void
    {
        $repositoryResult = [
            [
                'author_id' => 123,
                'id' => 123,
                'email' => 'test@example.com',
                'firstname' => 'Test',
                'lastname' => 'User'
            ]
        ];

        $this->repository
            ->method('findUniqueAuthors')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $author = $result[0];
        
        // Check API Platform format
        $this->assertArrayHasKey('@id', $author);
        $this->assertArrayHasKey('@type', $author);
        $this->assertSame('/api/users/123', $author['@id']);
        $this->assertSame('User', $author['@type']);
        
        // Check all expected fields are present
        $this->assertArrayHasKey('id', $author);
        $this->assertArrayHasKey('email', $author);
        $this->assertArrayHasKey('firstname', $author);
        $this->assertArrayHasKey('lastname', $author);
        $this->assertArrayHasKey('fullname', $author);
    }

    public function testProvideAcceptsOptionalParameters(): void
    {
        $this->repository
            ->method('findUniqueAuthors')
            ->willReturn([]);

        // Test with all optional parameters
        $result = $this->provider->provide(
            $this->operation,
            ['id' => 123], // uriVariables
            ['some' => 'context'] // context
        );

        $this->assertSame([], $result);
    }

    public function testProvideImplementsCorrectInterface(): void
    {
        $this->assertInstanceOf(
            \ApiPlatform\State\ProviderInterface::class,
            $this->provider
        );
    }

    public function testProvideTrimsFullnameCorrectly(): void
    {
        $repositoryResult = [
            [
                'author_id' => 1,
                'id' => 1,
                'email' => 'test@example.com',
                'firstname' => '  John  ',
                'lastname' => '  Doe  '
            ]
        ];

        $this->repository
            ->method('findUniqueAuthors')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        // The trim() call should handle extra spaces in firstname/lastname
        $this->assertSame('John     Doe', $result[0]['fullname']);
    }

    public function testProvideHandlesLargeDataset(): void
    {
        // Generate a large dataset to test performance
        $repositoryResult = [];
        $expectedResult = [];

        for ($i = 1; $i <= 1000; $i++) {
            $repositoryResult[] = [
                'author_id' => $i,
                'id' => $i,
                'email' => "user{$i}@example.com",
                'firstname' => "First{$i}",
                'lastname' => "Last{$i}"
            ];

            $expectedResult[] = [
                '@id' => "/api/users/{$i}",
                '@type' => 'User',
                'id' => $i,
                'email' => "user{$i}@example.com",
                'firstname' => "First{$i}",
                'lastname' => "Last{$i}",
                'fullname' => "First{$i} Last{$i}"
            ];
        }

        $this->repository
            ->method('findUniqueAuthors')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
        $this->assertCount(1000, $result);
    }
}