<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Service;

use C3net\CoreBundle\Entity\PasswordResetToken;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Repository\PasswordResetTokenRepository;
use C3net\CoreBundle\Repository\UserRepository;
use C3net\CoreBundle\Service\PasswordResetService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetServiceTest extends TestCase
{
    private PasswordResetService $service;
    private PasswordResetTokenRepository&MockObject $tokenRepository;
    private UserRepository&MockObject $userRepository;
    private EntityManagerInterface&MockObject $entityManager;
    private UserPasswordHasherInterface&MockObject $passwordHasher;
    private \Psr\Log\LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->tokenRepository = $this->createMock(PasswordResetTokenRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->logger = $this->createMock(\Psr\Log\LoggerInterface::class);

        $this->service = new PasswordResetService(
            $this->entityManager,
            $this->tokenRepository,
            $this->userRepository,
            $this->passwordHasher,
            $this->logger
        );
    }

    public function testServiceExists(): void
    {
        $this->assertTrue(class_exists(PasswordResetService::class));
    }

    public function testCanRequestResetReturnsTrueWhenUnderRateLimit(): void
    {
        $email = 'test@example.com';

        $this->tokenRepository
            ->expects($this->once())
            ->method('countRecentRequests')
            ->with($email, $this->anything())
            ->willReturn(2); // Under limit of 3

        $result = $this->service->canRequestReset($email);

        $this->assertTrue($result);
    }

    public function testCanRequestResetReturnsFalseWhenOverRateLimit(): void
    {
        $email = 'test@example.com';

        $this->tokenRepository
            ->expects($this->once())
            ->method('countRecentRequests')
            ->with($email, $this->anything())
            ->willReturn(3); // At limit

        $result = $this->service->canRequestReset($email);

        $this->assertFalse($result);
    }

    public function testFindUserByEmailReturnsUserWhenExists(): void
    {
        $email = 'test@example.com';
        $user = $this->createUser($email);

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($user);

        $result = $this->service->findUserByEmail($email);

        $this->assertSame($user, $result);
    }

    public function testFindUserByEmailReturnsNullWhenNotExists(): void
    {
        $email = 'nonexistent@example.com';

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn(null);

        $result = $this->service->findUserByEmail($email);

        $this->assertNull($result);
    }

    public function testCreateResetTokenGeneratesValidToken(): void
    {
        $user = $this->createUser('test@example.com');
        $request = $this->createRequest();

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(PasswordResetToken::class));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $plainToken = $this->service->createResetToken($user, $request);

        // Token should be a 64-character hex string (32 bytes = 64 hex chars)
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/i', $plainToken);
    }

    public function testValidateTokenReturnsTokenWhenValid(): void
    {
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);
        $user = $this->createUser('test@example.com');

        $resetToken = $this->createMock(PasswordResetToken::class);
        $resetToken->method('getUser')->willReturn($user);
        $resetToken->method('getExpiresAt')->willReturn(new \DateTimeImmutable('+30 minutes'));
        $resetToken->method('getUsedAt')->willReturn(null);
        $resetToken->method('isValid')->willReturn(true);

        $this->tokenRepository
            ->expects($this->once())
            ->method('findValidToken')
            ->with($tokenHash)
            ->willReturn($resetToken);

        $result = $this->service->validateToken($plainToken);

        $this->assertSame($resetToken, $result);
    }

    public function testValidateTokenReturnsNullWhenTokenNotFound(): void
    {
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);

        $this->tokenRepository
            ->expects($this->once())
            ->method('findValidToken')
            ->with($tokenHash)
            ->willReturn(null);

        $result = $this->service->validateToken($plainToken);

        $this->assertNull($result);
    }

    public function testResetPasswordUpdatesUserPassword(): void
    {
        $user = $this->createUser('test@example.com');
        $newPassword = 'NewSecurePassword123!';
        $hashedPassword = 'hashed_password';

        $resetToken = $this->createMock(PasswordResetToken::class);
        $resetToken->method('getUser')->willReturn($user);

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with($user, $newPassword)
            ->willReturn($hashedPassword);

        $resetToken
            ->expects($this->once())
            ->method('markAsUsed');

        $this->tokenRepository
            ->expects($this->once())
            ->method('invalidateUserTokens')
            ->with($user, $resetToken);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->resetPassword($resetToken, $newPassword);

        // User should have new password set
        $this->assertSame($hashedPassword, $user->getPassword());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getPasswordChangedAt());
    }

    public function testGetTokenLifetimeMinutesReturns30(): void
    {
        $lifetime = $this->service->getTokenLifetimeMinutes();

        $this->assertSame(30, $lifetime);
    }

    public function testCleanupExpiredTokensDeletesExpiredTokens(): void
    {
        $deletedCount = 5;

        $this->tokenRepository
            ->expects($this->once())
            ->method('deleteExpiredTokens')
            ->willReturn($deletedCount);

        $result = $this->service->cleanupExpiredTokens();

        $this->assertSame($deletedCount, $result);
    }

    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setNameFirst('Test');
        $user->setNameLast('User');
        $user->setPassword('old_hashed_password');

        return $user;
    }

    private function createRequest(): Request
    {
        return new Request(
            [],
            [],
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'PHPUnit Test',
            ]
        );
    }
}
