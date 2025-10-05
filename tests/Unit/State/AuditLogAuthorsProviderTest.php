<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\State;

use ApiPlatform\Metadata\Operation;
use C3net\CoreBundle\ApiResource\AuditLog\AuthorCollection;
use C3net\CoreBundle\ApiResource\AuditLog\AuthorSummary;
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
                'author_id' => '1',
                'id' => '1',
                'email' => 'john@example.com',
                'firstname' => 'John',
                'lastname' => 'Doe',
            ],
            [
                'author_id' => '2',
                'id' => '2',
                'email' => 'jane@example.com',
                'firstname' => 'Jane',
                'lastname' => 'Smith',
            ],
        ];

        $this->repository
            ->expects($this->once())
            ->method('findUniqueAuthors')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertInstanceOf(AuthorCollection::class, $result);
        $this->assertCount(2, $result->authors);

        $this->assertInstanceOf(AuthorSummary::class, $result->authors[0]);
        $this->assertSame('1', $result->authors[0]->id);
        $this->assertSame('john@example.com', $result->authors[0]->email);
        $this->assertSame('John', $result->authors[0]->firstname);
        $this->assertSame('Doe', $result->authors[0]->lastname);
        $this->assertSame('John Doe', $result->authors[0]->fullname);
        $this->assertSame('/api/users/1', $result->authors[0]->getIri());
        $this->assertSame('User', $result->authors[0]->getType());

        $this->assertInstanceOf(AuthorSummary::class, $result->authors[1]);
        $this->assertSame('2', $result->authors[1]->id);
        $this->assertSame('jane@example.com', $result->authors[1]->email);
        $this->assertSame('Jane', $result->authors[1]->firstname);
        $this->assertSame('Smith', $result->authors[1]->lastname);
        $this->assertSame('Jane Smith', $result->authors[1]->fullname);
    }

    public function testProvideReturnsEmptyCollectionWhenNoAuthors(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findUniqueAuthors')
            ->willReturn([]);

        $result = $this->provider->provide($this->operation);

        $this->assertInstanceOf(AuthorCollection::class, $result);
        $this->assertCount(0, $result->authors);
    }

    public function testProvideHandlesNullFirstname(): void
    {
        $repositoryResult = [
            [
                'author_id' => '1',
                'id' => '1',
                'email' => 'john@example.com',
                'firstname' => null,
                'lastname' => 'Doe',
            ],
        ];

        $this->repository
            ->expects($this->once())
            ->method('findUniqueAuthors')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertInstanceOf(AuthorCollection::class, $result);
        $this->assertCount(1, $result->authors);
        $this->assertSame('Doe', $result->authors[0]->fullname);
    }

    public function testProvideHandlesNullLastname(): void
    {
        $repositoryResult = [
            [
                'author_id' => '1',
                'id' => '1',
                'email' => 'john@example.com',
                'firstname' => 'John',
                'lastname' => null,
            ],
        ];

        $this->repository
            ->expects($this->once())
            ->method('findUniqueAuthors')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertInstanceOf(AuthorCollection::class, $result);
        $this->assertCount(1, $result->authors);
        $this->assertSame('John', $result->authors[0]->fullname);
    }

    public function testProvideHandlesBothNullNames(): void
    {
        $repositoryResult = [
            [
                'author_id' => '1',
                'id' => '1',
                'email' => 'john@example.com',
                'firstname' => null,
                'lastname' => null,
            ],
        ];

        $this->repository
            ->expects($this->once())
            ->method('findUniqueAuthors')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertInstanceOf(AuthorCollection::class, $result);
        $this->assertCount(1, $result->authors);
        $this->assertSame('', $result->authors[0]->fullname);
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

        $this->assertInstanceOf(AuthorCollection::class, $result);
    }

    public function testProvideImplementsCorrectInterface(): void
    {
        $this->assertInstanceOf(
            \ApiPlatform\State\ProviderInterface::class,
            $this->provider
        );
    }
}
