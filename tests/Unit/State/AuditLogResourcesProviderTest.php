<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\State;

use ApiPlatform\Metadata\Operation;
use C3net\CoreBundle\ApiResource\AuditLog\ResourceCollection;
use C3net\CoreBundle\Repository\AuditLogsRepository;
use C3net\CoreBundle\State\AuditLogResourcesProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuditLogResourcesProviderTest extends TestCase
{
    private AuditLogResourcesProvider $provider;
    private AuditLogsRepository&MockObject $repository;
    private Operation&MockObject $operation;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(AuditLogsRepository::class);
        $this->operation = $this->createMock(Operation::class);
        $this->provider = new AuditLogResourcesProvider($this->repository);
    }

    public function testProvideReturnsResourceCollection(): void
    {
        $repositoryResult = [
            ['resource' => 'User'],
            ['resource' => 'Project'],
            ['resource' => 'Company'],
            ['resource' => 'Category'],
        ];

        $this->repository
            ->expects($this->once())
            ->method('findUniqueResources')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertSame(['User', 'Project', 'Company', 'Category'], $result->resources);
    }

    public function testProvideReturnsEmptyCollectionWhenNoResources(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findUniqueResources')
            ->willReturn([]);

        $result = $this->provider->provide($this->operation);

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertSame([], $result->resources);
    }

    public function testProvideExtractsOnlyResourceValues(): void
    {
        $repositoryResult = [
            ['resource' => 'User', 'other_field' => 'ignored'],
            ['resource' => 'Project', 'another_field' => 'also_ignored'],
            ['resource' => 'Company'],
        ];

        $this->repository
            ->method('findUniqueResources')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertInstanceOf(ResourceCollection::class, $result);
        $this->assertSame(['User', 'Project', 'Company'], $result->resources);
    }

    public function testProvideImplementsCorrectInterface(): void
    {
        $this->assertInstanceOf(
            \ApiPlatform\State\ProviderInterface::class,
            $this->provider
        );
    }
}
