<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\State;

use ApiPlatform\Metadata\Operation;
use C3net\CoreBundle\ApiResource\AuditLog\AuthorSummary;
use C3net\CoreBundle\ApiResource\AuditLog\FilterOptions;
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

        $this->assertInstanceOf(FilterOptions::class, $result);

        // Check authors
        $this->assertCount(2, $result->authors);
        $this->assertInstanceOf(AuthorSummary::class, $result->authors[0]);
        $this->assertSame('1', $result->authors[0]->id);
        $this->assertSame('john@example.com', $result->authors[0]->email);
        $this->assertSame('John Doe', $result->authors[0]->fullname);

        // Check resources
        $this->assertSame(['User', 'Project', 'Company'], $result->resources);

        // Check actions
        $this->assertSame(['create', 'update', 'delete'], $result->actions);
    }

    public function testProvideHandlesEmptyData(): void
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

        $this->assertInstanceOf(FilterOptions::class, $result);
        $this->assertCount(0, $result->authors);
        $this->assertCount(0, $result->resources);
        $this->assertCount(0, $result->actions);
    }

    public function testProvideImplementsCorrectInterface(): void
    {
        $this->assertInstanceOf(
            \ApiPlatform\State\ProviderInterface::class,
            $this->provider
        );
    }
}
