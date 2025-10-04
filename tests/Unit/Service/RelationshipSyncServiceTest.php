<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Service;

use C3net\CoreBundle\Service\RelationshipSyncService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RelationshipSyncServiceTest extends TestCase
{
    private RelationshipSyncService $service;
    private EntityManagerInterface&MockObject $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->service = new RelationshipSyncService($this->entityManager);
    }

    public function testConstructor(): void
    {
        $service = new RelationshipSyncService($this->entityManager);

        $this->assertInstanceOf(RelationshipSyncService::class, $service);
    }

    public function testSyncOneToManyWithNewEntity(): void
    {
        $owningEntity = new MockOwningEntity();
        $item1 = new MockItem();
        $item2 = new MockItem();

        $collection = new ArrayCollection([$item1, $item2]);
        $owningEntity->setItems($collection);

        // New entity (no ID), so no previous references to remove
        $this->service->syncOneToMany($owningEntity, 'items', 'owner');

        $this->assertSame($owningEntity, $item1->getOwner());
        $this->assertSame($owningEntity, $item2->getOwner());
    }

    public function testSyncOneToManyWithExistingEntity(): void
    {
        $owningEntity = new MockOwningEntity();
        $owningEntity->setId(1); // Existing entity

        $item1 = new MockItem();
        $item2 = new MockItem();
        $collection = new ArrayCollection([$item1, $item2]);
        $owningEntity->setItems($collection);

        // Mock repository to return previous items
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findBy')->willReturn([]);

        $this->entityManager
            ->method('getRepository')
            ->with(MockItem::class)
            ->willReturn($repository);

        $this->service->syncOneToMany($owningEntity, 'items', 'owner');

        $this->assertSame($owningEntity, $item1->getOwner());
        $this->assertSame($owningEntity, $item2->getOwner());
    }

    public function testSyncOneToManyRemovesPreviousReferences(): void
    {
        $owningEntity = new MockOwningEntity();
        $owningEntity->setId(1);

        $currentItem = new MockItem();
        $previousItem = new MockItem();
        $previousItem->setOwner($owningEntity);

        $collection = new ArrayCollection([$currentItem]);
        $owningEntity->setItems($collection);

        // Mock repository to return previous item that's no longer in collection
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findBy')
                  ->with(['owner' => $owningEntity])
                  ->willReturn([$previousItem]);

        $this->entityManager
            ->method('getRepository')
            ->with(MockItem::class)
            ->willReturn($repository);

        $this->service->syncOneToMany($owningEntity, 'items', 'owner');

        // Current item should have owner set
        $this->assertSame($owningEntity, $currentItem->getOwner());

        // Previous item should have owner removed
        $this->assertNull($previousItem->getOwner());
    }

    public function testSyncOneToManyWithEmptyCollection(): void
    {
        $owningEntity = new MockOwningEntity();
        $owningEntity->setItems(new ArrayCollection());

        // Should not throw exception with empty collection
        $this->service->syncOneToMany($owningEntity, 'items', 'owner');

        $this->assertCount(0, $owningEntity->getItems());
    }

    public function testSyncOneToManyWithNonCollectionProperty(): void
    {
        $owningEntity = new MockOwningEntity();
        $owningEntity->setNonCollection('not a collection');

        // Should return early when property is not a Collection
        $this->service->syncOneToMany($owningEntity, 'nonCollection', 'owner');

        // No exception should be thrown
        $this->assertTrue(true);
    }

    public function testAutoSyncWithCompanyEntity(): void
    {
        $company = new MockCompany();
        $employee = new MockEmployee();
        $project = new MockProject();

        $company->setEmployees(new ArrayCollection([$employee]));
        $company->setProjects(new ArrayCollection([$project]));

        $this->service->autoSync($company);

        $this->assertSame($company, $employee->getCompany());
        $this->assertSame($company, $project->getClient());
    }

    public function testAutoSyncWithUserEntity(): void
    {
        $user = new MockUser();
        $project = new MockProject();

        $user->setProjects(new ArrayCollection([$project]));

        $this->service->autoSync($user);

        $this->assertSame($user, $project->getAssignee());
    }

    public function testAutoSyncWithUnknownEntity(): void
    {
        $unknownEntity = new MockUnknownEntity();

        // Should not throw exception for unknown entity
        $this->service->autoSync($unknownEntity);

        $this->assertTrue(true);
    }

    public function testAutoSyncWithMissingProperty(): void
    {
        $company = new MockCompanyWithoutMethods();

        // Should not throw exception when property methods don't exist
        $this->service->autoSync($company);

        $this->assertTrue(true);
    }

    public function testGetPropertyValueWithGetter(): void
    {
        $entity = new MockEntityWithGetters();
        $entity->setValue('test value');

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getPropertyValue');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $entity, 'value');

        $this->assertSame('test value', $result);
    }

    public function testGetPropertyValueWithIsGetter(): void
    {
        $entity = new MockEntityWithGetters();
        $entity->setActive(true);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getPropertyValue');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $entity, 'active');

        $this->assertTrue($result);
    }

    public function testGetPropertyValueThrowsExceptionForMissingGetter(): void
    {
        $entity = new MockEntityWithGetters();

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getPropertyValue');
        $method->setAccessible(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("No getter method found for property 'nonexistent'");

        $method->invoke($this->service, $entity, 'nonexistent');
    }

    public function testSetPropertyValue(): void
    {
        $entity = new MockEntityWithGetters();

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('setPropertyValue');
        $method->setAccessible(true);

        $method->invoke($this->service, $entity, 'value', 'new value');

        $this->assertSame('new value', $entity->getValue());
    }

    public function testSetPropertyValueThrowsExceptionForMissingSetter(): void
    {
        $entity = new MockEntityWithGetters();

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('setPropertyValue');
        $method->setAccessible(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("No setter method found for property 'nonexistent'");

        $method->invoke($this->service, $entity, 'nonexistent', 'value');
    }

    public function testGetEntityIdWithIdMethod(): void
    {
        $entity = new MockEntityWithGetters();
        $entity->setId(123);

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getEntityId');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $entity);

        $this->assertSame(123, $result);
    }

    public function testGetEntityIdWithoutIdMethod(): void
    {
        $entity = new MockEntityWithoutId();

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getEntityId');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, $entity);

        $this->assertNull($result);
    }

    public function testHasProperty(): void
    {
        $entity = new MockEntityWithGetters();

        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('hasProperty');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->service, $entity, 'value'));
        $this->assertFalse($method->invoke($this->service, $entity, 'nonexistent'));
    }

    public function testCompleteWorkflow(): void
    {
        $company = new MockCompany();
        $company->setId(1);

        $employee1 = new MockEmployee();
        $employee2 = new MockEmployee();
        $previousEmployee = new MockEmployee();
        $previousEmployee->setCompany($company);

        $company->setEmployees(new ArrayCollection([$employee1, $employee2]));

        // Mock repository behavior
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findBy')
                  ->with(['company' => $company])
                  ->willReturn([$previousEmployee]);

        $this->entityManager
            ->method('getRepository')
            ->with(MockEmployee::class)
            ->willReturn($repository);

        $this->service->autoSync($company);

        // New employees should have company set
        $this->assertSame($company, $employee1->getCompany());
        $this->assertSame($company, $employee2->getCompany());

        // Previous employee should have company removed
        $this->assertNull($previousEmployee->getCompany());
    }
}

// Mock classes for testing
class MockOwningEntity
{
    private ?int $id = null;
    private Collection $items;
    private mixed $nonCollection = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function setItems(Collection $items): void
    {
        $this->items = $items;
    }

    public function getNonCollection(): mixed
    {
        return $this->nonCollection;
    }

    public function setNonCollection(mixed $value): void
    {
        $this->nonCollection = $value;
    }
}

class MockItem
{
    private ?MockOwningEntity $owner = null;

    public function getOwner(): ?MockOwningEntity
    {
        return $this->owner;
    }

    public function setOwner(?MockOwningEntity $owner): void
    {
        $this->owner = $owner;
    }
}

class MockCompany
{
    private ?int $id = null;
    private Collection $employees;
    private Collection $projects;

    public function __construct()
    {
        $this->employees = new ArrayCollection();
        $this->projects = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getEmployees(): Collection
    {
        return $this->employees;
    }

    public function setEmployees(Collection $employees): void
    {
        $this->employees = $employees;
    }

    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function setProjects(Collection $projects): void
    {
        $this->projects = $projects;
    }
}

class MockEmployee
{
    private ?MockCompany $company = null;

    public function getCompany(): ?MockCompany
    {
        return $this->company;
    }

    public function setCompany(?MockCompany $company): void
    {
        $this->company = $company;
    }
}

class MockProject
{
    private ?MockCompany $client = null;
    private ?MockUser $assignee = null;

    public function getClient(): ?MockCompany
    {
        return $this->client;
    }

    public function setClient(?MockCompany $client): void
    {
        $this->client = $client;
    }

    public function getAssignee(): ?MockUser
    {
        return $this->assignee;
    }

    public function setAssignee(?MockUser $assignee): void
    {
        $this->assignee = $assignee;
    }
}

class MockUser
{
    private Collection $projects;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
    }

    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function setProjects(Collection $projects): void
    {
        $this->projects = $projects;
    }
}

class MockUnknownEntity
{
    // Entity not in relationship mappings
}

class MockCompanyWithoutMethods
{
    // Company without the expected methods
}

class MockEntityWithGetters
{
    private mixed $value = null;
    private bool $active = false;
    private ?int $id = null;

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }
}

class MockEntityWithoutId
{
    // Entity without getId method
}
