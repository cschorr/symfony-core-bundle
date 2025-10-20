<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Repository;

use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepositoryTest extends TestCase
{
    private UserRepository $repository;
    private ManagerRegistry&MockObject $managerRegistry;
    private EntityManagerInterface&MockObject $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        // Mock ClassMetadata to prevent initialization errors
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->name = User::class;

        $this->entityManager
            ->method('getClassMetadata')
            ->with(User::class)
            ->willReturn($classMetadata);

        $this->managerRegistry
            ->method('getManagerForClass')
            ->with(User::class)
            ->willReturn($this->entityManager);

        $this->repository = new UserRepository($this->managerRegistry);
    }

    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(UserRepository::class));
    }

    public function testRepositoryInheritsFromServiceEntityRepository(): void
    {
        $this->assertInstanceOf(
            \Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::class,
            $this->repository
        );
    }

    public function testRepositoryImplementsPasswordUpgraderInterface(): void
    {
        $this->assertInstanceOf(PasswordUpgraderInterface::class, $this->repository);
    }

    public function testConstructorSetsCorrectEntityClass(): void
    {
        // Verify that repository is constructed with correct entity class
        $reflection = new \ReflectionClass($this->repository);

        // Access the private/protected property to verify entity class
        $parentReflection = $reflection->getParentClass();
        $this->assertNotFalse($parentReflection);
        $this->assertSame(
            \Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::class,
            $parentReflection->getName()
        );

        // Verify repository works with User entity
        $this->assertInstanceOf(UserRepository::class, $this->repository);
    }

    public function testUpgradePasswordWithValidUser(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $newPassword = 'newHashedPassword123';

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->repository->upgradePassword($user, $newPassword);

        $this->assertSame($newPassword, $user->getPassword());
    }

    public function testUpgradePasswordThrowsExceptionForInvalidUser(): void
    {
        $invalidUser = new MockInvalidUser();

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage('Instances of "' . MockInvalidUser::class . '" are not supported.');

        $this->repository->upgradePassword($invalidUser, 'newPassword');
    }

    public function testUpgradePasswordDoesNotCallPersistForInvalidUser(): void
    {
        $invalidUser = new MockInvalidUser();

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $this->entityManager
            ->expects($this->never())
            ->method('flush');

        try {
            $this->repository->upgradePassword($invalidUser, 'newPassword');
        } catch (UnsupportedUserException $e) {
            // Expected exception
        }
    }

    public function testRepositoryHasCorrectMethods(): void
    {
        $reflection = new \ReflectionClass(UserRepository::class);

        $this->assertTrue($reflection->hasMethod('upgradePassword'));

        $upgradePasswordMethod = $reflection->getMethod('upgradePassword');
        $this->assertTrue($upgradePasswordMethod->isPublic());
        $this->assertCount(2, $upgradePasswordMethod->getParameters());
    }

    public function testUpgradePasswordMethodSignature(): void
    {
        $reflection = new \ReflectionClass(UserRepository::class);
        $method = $reflection->getMethod('upgradePassword');

        $parameters = $method->getParameters();

        $this->assertSame('user', $parameters[0]->getName());
        $this->assertSame('newHashedPassword', $parameters[1]->getName());

        $this->assertSame(
            PasswordAuthenticatedUserInterface::class,
            $parameters[0]->getType()?->getName()
        );
        $this->assertSame('string', $parameters[1]->getType()?->getName());
        $this->assertSame('void', $method->getReturnType()?->getName());
    }

    public function testPasswordUpgraderInterfaceImplementation(): void
    {
        $reflection = new \ReflectionClass(UserRepository::class);
        $interfaces = $reflection->getInterfaceNames();

        $this->assertContains(PasswordUpgraderInterface::class, $interfaces);
    }

    public function testRepositoryCanBeUsedWithSymfonySecurity(): void
    {
        // Test that the repository implements the correct interface for Symfony Security
        $this->assertInstanceOf(PasswordUpgraderInterface::class, $this->repository);

        // Test that upgradePassword method exists and is callable
        $this->assertTrue(method_exists($this->repository, 'upgradePassword'));
        $this->assertTrue(is_callable([$this->repository, 'upgradePassword']));
    }

    public function testUpgradePasswordPersistsChanges(): void
    {
        $user = new User();
        $user->setEmail('persistence@example.com');
        $originalPassword = 'oldPassword';
        $newPassword = 'newHashedPassword456';

        $user->setPassword($originalPassword);

        // Verify persist and flush are called in correct order
        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (User $persistedUser) use ($user) {
                return $persistedUser === $user;
            }));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->repository->upgradePassword($user, $newPassword);

        // Verify password was actually changed
        $this->assertSame($newPassword, $user->getPassword());
        $this->assertNotSame($originalPassword, $user->getPassword());
    }

    public function testUpgradePasswordHandlesComplexPasswords(): void
    {
        $user = new User();
        $user->setEmail('complex@example.com');

        // Test with complex password including special characters
        $complexPassword = '$2y$13$abcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->repository->upgradePassword($user, $complexPassword);

        $this->assertSame($complexPassword, $user->getPassword());
    }

    public function testUpgradePasswordWorksWithExistingUser(): void
    {
        $user = new User();
        $user->setEmail('existing@example.com');
        $user->setPassword('existingPassword');

        // Simulate an existing user with existing data
        $user->setNameFirst('John');
        $user->setNameLast('Doe');
        $user->setActive(true);

        $newPassword = 'upgradedPassword789';

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($user);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->repository->upgradePassword($user, $newPassword);

        // Verify only password changed, other data intact
        $this->assertSame($newPassword, $user->getPassword());
        $this->assertSame('John', $user->getNameFirst());
        $this->assertSame('Doe', $user->getNameLast());
        $this->assertTrue($user->isActive());
    }

    public function testRepositoryInheritanceChain(): void
    {
        $reflection = new \ReflectionClass(UserRepository::class);

        // Test inheritance chain
        $this->assertTrue($reflection->isSubclassOf(\Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository::class));

        // Test that it has access to inherited methods
        $this->assertTrue(method_exists($this->repository, 'find'));
        $this->assertTrue(method_exists($this->repository, 'findAll'));
        $this->assertTrue(method_exists($this->repository, 'findBy'));
        $this->assertTrue(method_exists($this->repository, 'findOneBy'));
    }
}

// Mock class for testing invalid user type
class MockInvalidUser implements PasswordAuthenticatedUserInterface
{
    public function getPassword(): ?string
    {
        return 'mockPassword';
    }
}
