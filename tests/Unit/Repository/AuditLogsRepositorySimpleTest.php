<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Repository;

use C3net\CoreBundle\Entity\AuditLogs;
use C3net\CoreBundle\Repository\AuditLogsRepository;
use PHPUnit\Framework\TestCase;

class AuditLogsRepositorySimpleTest extends TestCase
{
    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(AuditLogsRepository::class));
    }

    public function testRepositoryInheritsFromServiceEntityRepository(): void
    {
        $reflection = new \ReflectionClass(AuditLogsRepository::class);

        $this->assertTrue(
            $reflection->isSubclassOf(\Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::class)
        );
    }

    public function testRepositoryHasRequiredMethods(): void
    {
        $reflection = new \ReflectionClass(AuditLogsRepository::class);

        $this->assertTrue($reflection->hasMethod('findUniqueAuthors'));
        $this->assertTrue($reflection->hasMethod('findUniqueResources'));
        $this->assertTrue($reflection->hasMethod('findUniqueActions'));
    }

    public function testFindUniqueAuthorsMethodSignature(): void
    {
        $reflection = new \ReflectionClass(AuditLogsRepository::class);
        $method = $reflection->getMethod('findUniqueAuthors');

        $this->assertTrue($method->isPublic());
        $this->assertSame('array', $method->getReturnType()?->getName());
        $this->assertCount(0, $method->getParameters());
    }

    public function testFindUniqueResourcesMethodSignature(): void
    {
        $reflection = new \ReflectionClass(AuditLogsRepository::class);
        $method = $reflection->getMethod('findUniqueResources');

        $this->assertTrue($method->isPublic());
        $this->assertSame('array', $method->getReturnType()?->getName());
        $this->assertCount(0, $method->getParameters());
    }

    public function testFindUniqueActionsMethodSignature(): void
    {
        $reflection = new \ReflectionClass(AuditLogsRepository::class);
        $method = $reflection->getMethod('findUniqueActions');

        $this->assertTrue($method->isPublic());
        $this->assertSame('array', $method->getReturnType()?->getName());
        $this->assertCount(0, $method->getParameters());
    }

    public function testRepositoryMethodsHaveProperDocumentation(): void
    {
        $reflection = new \ReflectionClass(AuditLogsRepository::class);

        $authorsMethod = $reflection->getMethod('findUniqueAuthors');
        $resourcesMethod = $reflection->getMethod('findUniqueResources');
        $actionsMethod = $reflection->getMethod('findUniqueActions');

        $this->assertStringContainsString('Get all unique authors', $authorsMethod->getDocComment() ?: '');
        $this->assertStringContainsString('Get all unique resources', $resourcesMethod->getDocComment() ?: '');
        $this->assertStringContainsString('Get all unique actions', $actionsMethod->getDocComment() ?: '');
    }

    public function testRepositoryConstructorAcceptsManagerRegistry(): void
    {
        $reflection = new \ReflectionClass(AuditLogsRepository::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertCount(1, $constructor->getParameters());

        $param = $constructor->getParameters()[0];
        $this->assertSame('registry', $param->getName());
    }

    public function testEntityClassConstant(): void
    {
        // Test that the repository is designed for AuditLogs entity
        $reflection = new \ReflectionClass(AuditLogsRepository::class);

        // Check if there's a way to determine the entity class
        // This is typically done through the parent constructor call
        $this->assertTrue($reflection->hasMethod('getEntityName') || $reflection->hasMethod('getClassName'));
    }

    public function testRepositoryMethodsReturnArray(): void
    {
        $reflection = new \ReflectionClass(AuditLogsRepository::class);

        $methods = [
            'findUniqueAuthors',
            'findUniqueResources',
            'findUniqueActions',
        ];

        foreach ($methods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $returnType = $method->getReturnType();

            $this->assertNotNull($returnType);
            $this->assertSame('array', $returnType->getName());
        }
    }

    public function testRepositoryMethodsAreNotStatic(): void
    {
        $reflection = new \ReflectionClass(AuditLogsRepository::class);

        $methods = [
            'findUniqueAuthors',
            'findUniqueResources',
            'findUniqueActions',
        ];

        foreach ($methods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertFalse($method->isStatic(), "Method {$methodName} should not be static");
        }
    }
}
