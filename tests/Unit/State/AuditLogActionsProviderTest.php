<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\State;

use ApiPlatform\Metadata\Operation;
use C3net\CoreBundle\Repository\AuditLogsRepository;
use C3net\CoreBundle\State\AuditLogActionsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuditLogActionsProviderTest extends TestCase
{
    private AuditLogActionsProvider $provider;
    private AuditLogsRepository&MockObject $repository;
    private Operation&MockObject $operation;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(AuditLogsRepository::class);
        $this->operation = $this->createMock(Operation::class);
        $this->provider = new AuditLogActionsProvider($this->repository);
    }

    public function testProvideReturnsActionsList(): void
    {
        $repositoryResult = [
            ['action' => 'create'],
            ['action' => 'update'],
            ['action' => 'delete'],
            ['action' => 'view'],
        ];

        $expectedResult = ['create', 'update', 'delete', 'view'];

        $this->repository
            ->expects($this->once())
            ->method('findUniqueActions')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideReturnsEmptyArrayWhenNoActions(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findUniqueActions')
            ->willReturn([]);

        $result = $this->provider->provide($this->operation);

        $this->assertSame([], $result);
    }

    public function testProvideExtractsOnlyActionValues(): void
    {
        $repositoryResult = [
            ['action' => 'create', 'other_field' => 'ignored'],
            ['action' => 'update', 'another_field' => 'also_ignored'],
            ['action' => 'delete'],
        ];

        $expectedResult = ['create', 'update', 'delete'];

        $this->repository
            ->method('findUniqueActions')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideHandlesSingleAction(): void
    {
        $repositoryResult = [
            ['action' => 'create'],
        ];

        $expectedResult = ['create'];

        $this->repository
            ->method('findUniqueActions')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideHandlesComplexActionNames(): void
    {
        $repositoryResult = [
            ['action' => 'soft_delete'],
            ['action' => 'bulk_update'],
            ['action' => 'export-csv'],
            ['action' => 'import.xlsx'],
            ['action' => 'user:login'],
            ['action' => 'admin/approve'],
        ];

        $expectedResult = [
            'soft_delete',
            'bulk_update',
            'export-csv',
            'import.xlsx',
            'user:login',
            'admin/approve',
        ];

        $this->repository
            ->method('findUniqueActions')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideHandlesTypicalCRUDActions(): void
    {
        $repositoryResult = [
            ['action' => 'create'],
            ['action' => 'read'],
            ['action' => 'update'],
            ['action' => 'delete'],
            ['action' => 'list'],
            ['action' => 'show'],
            ['action' => 'edit'],
        ];

        $expectedResult = ['create', 'read', 'update', 'delete', 'list', 'show', 'edit'];

        $this->repository
            ->method('findUniqueActions')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideAcceptsOptionalParameters(): void
    {
        $this->repository
            ->method('findUniqueActions')
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

    public function testProvideHandlesLargeDataset(): void
    {
        // Generate a large dataset to test performance
        $repositoryResult = [];
        $expectedResult = [];

        for ($i = 1; $i <= 300; ++$i) {
            $action = "action_{$i}";
            $repositoryResult[] = ['action' => $action];
            $expectedResult[] = $action;
        }

        $this->repository
            ->method('findUniqueActions')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
        $this->assertCount(300, $result);
    }

    public function testProvideHandlesNullActionGracefully(): void
    {
        // This should not happen based on repository WHERE clause, but test defensive programming
        $repositoryResult = [
            ['action' => 'create'],
            ['action' => null], // This should be filtered by repository
            ['action' => 'update'],
        ];

        $expectedResult = ['create', null, 'update'];

        $this->repository
            ->method('findUniqueActions')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideHandlesEmptyActionString(): void
    {
        $repositoryResult = [
            ['action' => 'create'],
            ['action' => ''], // Empty string
            ['action' => 'update'],
        ];

        $expectedResult = ['create', '', 'update'];

        $this->repository
            ->method('findUniqueActions')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideReturnsNumericIndexedArray(): void
    {
        $repositoryResult = [
            ['action' => 'create'],
            ['action' => 'update'],
            ['action' => 'delete'],
        ];

        $this->repository
            ->method('findUniqueActions')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        // Verify it's a numerically indexed array
        $this->assertSame(['create', 'update', 'delete'], $result);
        $this->assertSame(0, array_key_first($result));
        $this->assertSame(2, array_key_last($result));
        $this->assertTrue(array_is_list($result));
    }

    public function testProvideWithAuditingSpecificActions(): void
    {
        $repositoryResult = [
            ['action' => 'insert'],
            ['action' => 'update'],
            ['action' => 'remove'],
            ['action' => 'associate'],
            ['action' => 'dissociate'],
            ['action' => 'soft_delete'],
            ['action' => 'restore'],
        ];

        $expectedResult = [
            'insert',
            'update',
            'remove',
            'associate',
            'dissociate',
            'soft_delete',
            'restore',
        ];

        $this->repository
            ->method('findUniqueActions')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
        $this->assertCount(7, $result);
    }

    public function testProvideHandlesCaseSensitiveActions(): void
    {
        $repositoryResult = [
            ['action' => 'CREATE'],
            ['action' => 'create'],
            ['action' => 'Create'],
            ['action' => 'UPDATE'],
            ['action' => 'update'],
        ];

        $expectedResult = ['CREATE', 'create', 'Create', 'UPDATE', 'update'];

        $this->repository
            ->method('findUniqueActions')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
        $this->assertCount(5, $result); // All should be preserved as they are case-sensitive different
    }
}
