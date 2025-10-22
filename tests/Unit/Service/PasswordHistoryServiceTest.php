<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Service;

use C3net\CoreBundle\Entity\PasswordHistory;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Exception\PasswordReusedException;
use C3net\CoreBundle\Repository\PasswordHistoryRepository;
use C3net\CoreBundle\Service\PasswordHistoryService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PasswordHistoryServiceTest extends TestCase
{
    private PasswordHistoryService $service;
    private PasswordHistoryRepository&MockObject $repository;
    private EntityManagerInterface&MockObject $entityManager;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PasswordHistoryRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->entityManager
            ->method('getRepository')
            ->with(PasswordHistory::class)
            ->willReturn($this->repository);

        $this->service = new PasswordHistoryService(
            $this->entityManager
        );
    }

    public function testServiceExists(): void
    {
        $this->assertTrue(class_exists(PasswordHistoryService::class));
    }

    public function testValidatePasswordNotReusedWithNoHistory(): void
    {
        $user = $this->createUser();
        $plainPassword = 'NewPassword123!';

        $this->repository
            ->expects($this->once())
            ->method('findByUser')
            ->with($user, 10)
            ->willReturn([]);

        $this->service->validatePasswordNotReused($user, $plainPassword);

        // No exception should be thrown
        $this->assertTrue(true);
    }

    public function testValidatePasswordNotReusedWithDifferentPasswords(): void
    {
        $user = $this->createUser();
        $plainPassword = 'NewPassword123!';

        $historyEntry1 = $this->createPasswordHistory($user, password_hash('OldPassword1', PASSWORD_BCRYPT));
        $historyEntry2 = $this->createPasswordHistory($user, password_hash('OldPassword2', PASSWORD_BCRYPT));

        $this->repository
            ->expects($this->once())
            ->method('findByUser')
            ->with($user, 10)
            ->willReturn([$historyEntry1, $historyEntry2]);

        $this->service->validatePasswordNotReused($user, $plainPassword);

        // No exception should be thrown
        $this->assertTrue(true);
    }

    public function testValidatePasswordNotReusedThrowsExceptionWhenPasswordReused(): void
    {
        $user = $this->createUser();
        $plainPassword = 'ReusedPassword123!';
        $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

        $historyEntry = $this->createPasswordHistory($user, $hashedPassword);

        $this->repository
            ->expects($this->once())
            ->method('findByUser')
            ->with($user, 10)
            ->willReturn([$historyEntry]);

        $this->expectException(PasswordReusedException::class);
        $this->expectExceptionMessage('This password was used recently. Please choose a different password.');

        $this->service->validatePasswordNotReused($user, $plainPassword);
    }

    public function testValidatePasswordNotReusedChecksAllHistoryEntries(): void
    {
        $user = $this->createUser();
        $plainPassword = 'ReusedPassword123!';
        $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

        // Create 5 history entries, with the reused password in the middle
        $historyEntries = [
            $this->createPasswordHistory($user, password_hash('OldPassword1', PASSWORD_BCRYPT)),
            $this->createPasswordHistory($user, password_hash('OldPassword2', PASSWORD_BCRYPT)),
            $this->createPasswordHistory($user, $hashedPassword), // Reused password
            $this->createPasswordHistory($user, password_hash('OldPassword4', PASSWORD_BCRYPT)),
            $this->createPasswordHistory($user, password_hash('OldPassword5', PASSWORD_BCRYPT)),
        ];

        $this->repository
            ->expects($this->once())
            ->method('findByUser')
            ->with($user, 10)
            ->willReturn($historyEntries);

        $this->expectException(PasswordReusedException::class);

        $this->service->validatePasswordNotReused($user, $plainPassword);
    }

    public function testStorePasswordHashCreatesNewEntry(): void
    {
        $user = $this->createUser();
        $passwordHash = '$2y$13$hashedpassword';

        $this->repository
            ->expects($this->once())
            ->method('deleteOldest')
            ->with($user, 10);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (PasswordHistory $entry) use ($user, $passwordHash) {
                return $entry->getUser() === $user
                    && $entry->getPasswordHash() === $passwordHash
                    && $entry->getCreatedAt() instanceof \DateTimeImmutable;
            }));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->storePasswordHash($user, $passwordHash);
    }

    public function testStorePasswordHashAlwaysCallsDeleteOldest(): void
    {
        $user = $this->createUser();
        $passwordHash = '$2y$13$newhashedpassword';

        // The service always calls deleteOldest after storing
        $this->repository
            ->expects($this->once())
            ->method('deleteOldest')
            ->with($user, 10);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(PasswordHistory::class));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->storePasswordHash($user, $passwordHash);
    }

    public function testCleanupOldHistoryDeletesExpiredEntries(): void
    {
        $user = $this->createUser();
        $expiryDate = new \DateTimeImmutable('-365 days');

        $this->repository
            ->expects($this->once())
            ->method('deleteOlderThan')
            ->with(
                $this->callback(function (\DateTimeImmutable $date) use ($expiryDate) {
                    // Allow 1 second difference due to timing
                    return abs($date->getTimestamp() - $expiryDate->getTimestamp()) <= 1;
                }),
                $user
            )
            ->willReturn(15);

        $deletedCount = $this->service->cleanupOldHistory($user);
        $this->assertSame(15, $deletedCount);
    }

    public function testCleanupOldHistoryReturnsZeroWhenNoEntriesDeleted(): void
    {
        $user = $this->createUser();

        $this->repository
            ->expects($this->once())
            ->method('deleteOlderThan')
            ->willReturn(0);

        $deletedCount = $this->service->cleanupOldHistory($user);
        $this->assertSame(0, $deletedCount);
    }

    public function testGetHistoryCountReturnsCorrectCount(): void
    {
        $user = $this->createUser();

        $this->repository
            ->expects($this->once())
            ->method('countByUser')
            ->with($user)
            ->willReturn(7);

        $count = $this->service->getHistoryCount($user);
        $this->assertSame(7, $count);
    }

    public function testValidatePasswordNotReusedWithEmptyPassword(): void
    {
        $user = $this->createUser();
        $plainPassword = '';

        // Should still check history even with empty password
        $this->repository
            ->expects($this->once())
            ->method('findByUser')
            ->with($user, 10)
            ->willReturn([]);

        $this->service->validatePasswordNotReused($user, $plainPassword);

        $this->assertTrue(true);
    }

    public function testStorePasswordHashWithVeryLongHash(): void
    {
        $user = $this->createUser();
        // Argon2 hashes can be longer than bcrypt
        $passwordHash = '$argon2id$v=19$m=65536,t=4,p=1$' . str_repeat('a', 200);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (PasswordHistory $entry) use ($passwordHash) {
                return $entry->getPasswordHash() === $passwordHash;
            }));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->storePasswordHash($user, $passwordHash);
    }

    public function testPasswordHistoryServiceConstants(): void
    {
        $reflection = new \ReflectionClass(PasswordHistoryService::class);

        // Verify constants exist
        $this->assertTrue($reflection->hasConstant('PASSWORD_HISTORY_LIMIT'));
        $this->assertTrue($reflection->hasConstant('PASSWORD_HISTORY_MAX_AGE_DAYS'));

        // Verify constant values
        $this->assertSame(10, $reflection->getConstant('PASSWORD_HISTORY_LIMIT'));
        $this->assertSame(365, $reflection->getConstant('PASSWORD_HISTORY_MAX_AGE_DAYS'));
    }

    public function testServiceMethodsExist(): void
    {
        $reflection = new \ReflectionClass(PasswordHistoryService::class);

        $this->assertTrue($reflection->hasMethod('validatePasswordNotReused'));
        $this->assertTrue($reflection->hasMethod('storePasswordHash'));
        $this->assertTrue($reflection->hasMethod('cleanupOldHistory'));
        $this->assertTrue($reflection->hasMethod('getHistoryCount'));
    }

    public function testValidatePasswordNotReusedMethodSignature(): void
    {
        $reflection = new \ReflectionClass(PasswordHistoryService::class);
        $method = $reflection->getMethod('validatePasswordNotReused');

        $parameters = $method->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertSame('user', $parameters[0]->getName());
        $this->assertSame('plainPassword', $parameters[1]->getName());
        $this->assertSame('void', $method->getReturnType()?->getName());
    }

    public function testStorePasswordHashMethodSignature(): void
    {
        $reflection = new \ReflectionClass(PasswordHistoryService::class);
        $method = $reflection->getMethod('storePasswordHash');

        $parameters = $method->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertSame('user', $parameters[0]->getName());
        $this->assertSame('passwordHash', $parameters[1]->getName());
        $this->assertSame('void', $method->getReturnType()?->getName());
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('$2y$13$hashedpassword');

        // Use reflection to set the ID since it's normally set by Doctrine
        // The id property is defined in UuidTrait, so we need to get it from the parent class
        $reflection = new \ReflectionClass($user);

        // Search through class hierarchy to find the 'id' property (defined in trait)
        $property = null;
        while ($reflection && !$property) {
            try {
                $property = $reflection->getProperty('id');
            } catch (\ReflectionException $e) {
                $reflection = $reflection->getParentClass();
            }
        }

        if ($property) {
            $property->setAccessible(true);
            $property->setValue($user, \Symfony\Component\Uid\Uuid::v7());
        }

        return $user;
    }

    private function createPasswordHistory(User $user, string $passwordHash): PasswordHistory
    {
        $history = new PasswordHistory();

        // Use reflection to set private properties
        $reflection = new \ReflectionClass($history);

        $userProperty = $reflection->getProperty('user');
        $userProperty->setAccessible(true);
        $userProperty->setValue($history, $user);

        $hashProperty = $reflection->getProperty('passwordHash');
        $hashProperty->setAccessible(true);
        $hashProperty->setValue($history, $passwordHash);

        $createdAtProperty = $reflection->getProperty('createdAt');
        $createdAtProperty->setAccessible(true);
        $createdAtProperty->setValue($history, new \DateTimeImmutable());

        return $history;
    }
}
