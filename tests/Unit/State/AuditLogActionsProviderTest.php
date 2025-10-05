<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\State;

use ApiPlatform\Metadata\Operation;
use C3net\CoreBundle\ApiResource\AuditLog\ActionCollection;
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

    public function testProvideReturnsActionCollection(): void
    {
        $repositoryResult = [
            ['action' => 'create'],
            ['action' => 'update'],
            ['action' => 'delete'],
            ['action' => 'view'],
        ];

        $this->repository
            ->expects($this->once())
            ->method('findUniqueActions')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertInstanceOf(ActionCollection::class, $result);
        $this->assertSame(['create', 'update', 'delete', 'view'], $result->actions);
    }

    public function testProvideReturnsEmptyCollectionWhenNoActions(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findUniqueActions')
            ->willReturn([]);

        $result = $this->provider->provide($this->operation);

        $this->assertInstanceOf(ActionCollection::class, $result);
        $this->assertSame([], $result->actions);
    }

    public function testProvideExtractsOnlyActionValues(): void
    {
        $repositoryResult = [
            ['action' => 'create', 'other_field' => 'ignored'],
            ['action' => 'update', 'another_field' => 'also_ignored'],
            ['action' => 'delete'],
        ];

        $this->repository
            ->method('findUniqueActions')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertInstanceOf(ActionCollection::class, $result);
        $this->assertSame(['create', 'update', 'delete'], $result->actions);
    }

    public function testProvideImplementsCorrectInterface(): void
    {
        $this->assertInstanceOf(
            \ApiPlatform\State\ProviderInterface::class,
            $this->provider
        );
    }
}
