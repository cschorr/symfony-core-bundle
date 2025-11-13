<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Integration\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use C3net\CoreBundle\Entity\PasswordResetToken;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Enum\UserRole;
use C3net\CoreBundle\Service\PasswordResetService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetApiTest extends ApiTestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private PasswordResetService $passwordResetService;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = $kernel->getContainer()->get(UserPasswordHasherInterface::class);
        $this->passwordResetService = $kernel->getContainer()->get(PasswordResetService::class);

        // Clean up any existing test data
        $this->cleanupTestData();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
    }

    public function testRequestPasswordResetWithValidEmail(): void
    {
        $user = $this->createTestUser('testuser@example.com');

        $response = static::createClient()->request('POST', '/api/password-reset/request', [
            'json' => [
                'email' => 'testuser@example.com',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'message' => 'If this email exists in our system, you will receive password reset instructions shortly.',
        ]);

        // Verify token was created
        $token = $this->entityManager->getRepository(PasswordResetToken::class)
            ->findOneBy(['email' => 'testuser@example.com']);

        $this->assertNotNull($token);
        $this->assertSame($user->getId()->toString(), $token->getUser()->getId()->toString());
    }

    public function testRequestPasswordResetWithNonExistentEmail(): void
    {
        $response = static::createClient()->request('POST', '/api/password-reset/request', [
            'json' => [
                'email' => 'nonexistent@example.com',
            ],
        ]);

        // Should still return success to prevent user enumeration
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'message' => 'If this email exists in our system, you will receive password reset instructions shortly.',
        ]);

        // Verify no token was created
        $token = $this->entityManager->getRepository(PasswordResetToken::class)
            ->findOneBy(['email' => 'nonexistent@example.com']);

        $this->assertNull($token);
    }

    public function testRequestPasswordResetWithInvalidEmailFormat(): void
    {
        $response = static::createClient()->request('POST', '/api/password-reset/request', [
            'json' => [
                'email' => 'invalid-email',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422); // Validation error
    }

    public function testRequestPasswordResetRateLimiting(): void
    {
        $user = $this->createTestUser('ratelimit@example.com');

        // Make 3 requests (the limit)
        for ($i = 0; $i < 3; ++$i) {
            static::createClient()->request('POST', '/api/password-reset/request', [
                'json' => [
                    'email' => 'ratelimit@example.com',
                ],
            ]);
        }

        // 4th request should be rate limited
        $response = static::createClient()->request('POST', '/api/password-reset/request', [
            'json' => [
                'email' => 'ratelimit@example.com',
            ],
        ]);

        $this->assertResponseStatusCodeSame(429); // Too Many Requests
    }

    public function testConfirmPasswordResetWithValidToken(): void
    {
        $user = $this->createTestUser('resetuser@example.com', 'OldPassword123!');
        $plainToken = $this->createPasswordResetToken($user);

        $newPassword = 'NewSecureP@ssw0rd123';

        $response = static::createClient()->request('POST', '/api/password-reset/confirm', [
            'json' => [
                'token' => $plainToken,
                'newPassword' => $newPassword,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'message' => 'Password reset successful. You can now log in with your new password.',
        ]);

        // Verify password was changed
        $this->entityManager->refresh($user);
        $isPasswordValid = $this->passwordHasher->isPasswordValid($user, $newPassword);
        $this->assertTrue($isPasswordValid);

        // Verify passwordChangedAt was set
        $this->assertNotNull($user->getPasswordChangedAt());

        // Verify token was marked as used
        $token = $this->entityManager->getRepository(PasswordResetToken::class)
            ->findOneBy(['tokenHash' => hash('sha256', $plainToken)]);

        $this->assertNotNull($token->getUsedAt());
    }

    public function testConfirmPasswordResetWithInvalidToken(): void
    {
        $response = static::createClient()->request('POST', '/api/password-reset/confirm', [
            'json' => [
                'token' => 'invalid-token-12345',
                'newPassword' => 'NewSecureP@ssw0rd123',
            ],
        ]);

        $this->assertResponseStatusCodeSame(400); // Bad Request
        $this->assertJsonContains([
            'error' => 'Invalid or expired reset token. Please request a new password reset.',
        ]);
    }

    public function testConfirmPasswordResetWithExpiredToken(): void
    {
        $user = $this->createTestUser('expireduser@example.com');
        $plainToken = $this->createPasswordResetToken($user, expiresInMinutes: -10); // Already expired

        $response = static::createClient()->request('POST', '/api/password-reset/confirm', [
            'json' => [
                'token' => $plainToken,
                'newPassword' => 'NewSecureP@ssw0rd123',
            ],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'error' => 'Invalid or expired reset token. Please request a new password reset.',
        ]);
    }

    public function testConfirmPasswordResetWithWeakPassword(): void
    {
        $user = $this->createTestUser('weakpass@example.com');
        $plainToken = $this->createPasswordResetToken($user);

        $response = static::createClient()->request('POST', '/api/password-reset/confirm', [
            'json' => [
                'token' => $plainToken,
                'newPassword' => 'weak', // Too short, no special chars, etc.
            ],
        ]);

        $this->assertResponseStatusCodeSame(422); // Validation error
    }

    public function testConfirmPasswordResetTokenCanOnlyBeUsedOnce(): void
    {
        $user = $this->createTestUser('onceuser@example.com');
        $plainToken = $this->createPasswordResetToken($user);

        $newPassword1 = 'FirstPassword123!';
        $newPassword2 = 'SecondPassword123!';

        // First use should succeed
        $response1 = static::createClient()->request('POST', '/api/password-reset/confirm', [
            'json' => [
                'token' => $plainToken,
                'newPassword' => $newPassword1,
            ],
        ]);

        $this->assertResponseIsSuccessful();

        // Second use should fail
        $response2 = static::createClient()->request('POST', '/api/password-reset/confirm', [
            'json' => [
                'token' => $plainToken,
                'newPassword' => $newPassword2,
            ],
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testPasswordResetInvalidatesOtherUserTokens(): void
    {
        $user = $this->createTestUser('multitoken@example.com');

        // Create first token
        $token1 = $this->createPasswordResetToken($user);

        // Create second token (should invalidate first)
        $token2 = $this->createPasswordResetToken($user);

        // Try to use first token - should fail as it was invalidated
        $response = static::createClient()->request('POST', '/api/password-reset/confirm', [
            'json' => [
                'token' => $token1,
                'newPassword' => 'NewPassword123!',
            ],
        ]);

        $this->assertResponseStatusCodeSame(400);

        // Second token should still work
        $response = static::createClient()->request('POST', '/api/password-reset/confirm', [
            'json' => [
                'token' => $token2,
                'newPassword' => 'NewPassword123!',
            ],
        ]);

        $this->assertResponseIsSuccessful();
    }

    private function createTestUser(string $email, ?string $password = null): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setActive(true);
        $user->setRoles([UserRole::ROLE_USER->value]);

        if ($password) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
        } else {
            $user->setPassword($this->passwordHasher->hashPassword($user, 'DefaultPassword123!'));
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createPasswordResetToken(User $user, int $expiresInMinutes = 30): string
    {
        $request = new Request(
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

        $plainToken = $this->passwordResetService->createResetToken($user, $request);

        // If we need an expired token, manually update it
        if ($expiresInMinutes < 0) {
            $tokenHash = hash('sha256', $plainToken);
            $token = $this->entityManager->getRepository(PasswordResetToken::class)
                ->findOneBy(['tokenHash' => $tokenHash]);

            if ($token) {
                $reflection = new \ReflectionClass($token);
                $property = $reflection->getProperty('expiresAt');
                $property->setAccessible(true);
                $property->setValue($token, new \DateTimeImmutable(sprintf('%d minutes', $expiresInMinutes)));
                $this->entityManager->flush();
            }
        }

        return $plainToken;
    }

    private function cleanupTestData(): void
    {
        // Delete all password reset tokens
        $tokens = $this->entityManager->getRepository(PasswordResetToken::class)->findAll();
        foreach ($tokens as $token) {
            $this->entityManager->remove($token);
        }

        // Delete test users
        $testEmails = [
            'testuser@example.com',
            'nonexistent@example.com',
            'ratelimit@example.com',
            'resetuser@example.com',
            'expireduser@example.com',
            'weakpass@example.com',
            'onceuser@example.com',
            'multitoken@example.com',
        ];

        foreach ($testEmails as $email) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($user) {
                $this->entityManager->remove($user);
            }
        }

        $this->entityManager->flush();
    }
}
