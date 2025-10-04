<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\State;

use ApiPlatform\Metadata\Operation;
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

    public function testProvideReturnsResourcesList(): void
    {
        $repositoryResult = [
            ['resource' => 'User'],
            ['resource' => 'Project'],
            ['resource' => 'Company'],
            ['resource' => 'Category'],
        ];

        $expectedResult = ['User', 'Project', 'Company', 'Category'];

        $this->repository
            ->expects($this->once())
            ->method('findUniqueResources')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideReturnsEmptyArrayWhenNoResources(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findUniqueResources')
            ->willReturn([]);

        $result = $this->provider->provide($this->operation);

        $this->assertSame([], $result);
    }

    public function testProvideExtractsOnlyResourceValues(): void
    {
        $repositoryResult = [
            ['resource' => 'User', 'other_field' => 'ignored'],
            ['resource' => 'Project', 'another_field' => 'also_ignored'],
            ['resource' => 'Company'],
        ];

        $expectedResult = ['User', 'Project', 'Company'];

        $this->repository
            ->method('findUniqueResources')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideHandlesSingleResource(): void
    {
        $repositoryResult = [
            ['resource' => 'User'],
        ];

        $expectedResult = ['User'];

        $this->repository
            ->method('findUniqueResources')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideHandlesComplexResourceNames(): void
    {
        $repositoryResult = [
            ['resource' => 'App\\Entity\\User'],
            ['resource' => 'My\\Custom\\Entity\\Project'],
            ['resource' => 'Company_V2'],
            ['resource' => 'Category-Type'],
        ];

        $expectedResult = [
            'App\\Entity\\User',
            'My\\Custom\\Entity\\Project',
            'Company_V2',
            'Category-Type',
        ];

        $this->repository
            ->method('findUniqueResources')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideAcceptsOptionalParameters(): void
    {
        $this->repository
            ->method('findUniqueResources')
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

        for ($i = 1; $i <= 500; ++$i) {
            $resource = "Resource{$i}";
            $repositoryResult[] = ['resource' => $resource];
            $expectedResult[] = $resource;
        }

        $this->repository
            ->method('findUniqueResources')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
        $this->assertCount(500, $result);
    }

    public function testProvideHandlesNullResourceGracefully(): void
    {
        // This should not happen based on repository WHERE clause, but test defensive programming
        $repositoryResult = [
            ['resource' => 'User'],
            ['resource' => null], // This should be filtered by repository
            ['resource' => 'Project'],
        ];

        $expectedResult = ['User', null, 'Project'];

        $this->repository
            ->method('findUniqueResources')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideHandlesEmptyResourceString(): void
    {
        $repositoryResult = [
            ['resource' => 'User'],
            ['resource' => ''], // Empty string
            ['resource' => 'Project'],
        ];

        $expectedResult = ['User', '', 'Project'];

        $this->repository
            ->method('findUniqueResources')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProvideReturnsNumericIndexedArray(): void
    {
        $repositoryResult = [
            ['resource' => 'User'],
            ['resource' => 'Project'],
            ['resource' => 'Company'],
        ];

        $this->repository
            ->method('findUniqueResources')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        // Verify it's a numerically indexed array
        $this->assertSame(['User', 'Project', 'Company'], $result);
        $this->assertSame(0, array_key_first($result));
        $this->assertSame(2, array_key_last($result));
        $this->assertTrue(array_is_list($result));
    }

    public function testProvideWithTypicalEntityNames(): void
    {
        $repositoryResult = [
            ['resource' => 'User'],
            ['resource' => 'Project'],
            ['resource' => 'Company'],
            ['resource' => 'Category'],
            ['resource' => 'UserGroup'],
            ['resource' => 'CompanyGroup'],
            ['resource' => 'Contact'],
            ['resource' => 'Campaign'],
        ];

        $expectedResult = [
            'User',
            'Project',
            'Company',
            'Category',
            'UserGroup',
            'CompanyGroup',
            'Contact',
            'Campaign',
        ];

        $this->repository
            ->method('findUniqueResources')
            ->willReturn($repositoryResult);

        $result = $this->provider->provide($this->operation);

        $this->assertSame($expectedResult, $result);
        $this->assertCount(8, $result);
    }
}
