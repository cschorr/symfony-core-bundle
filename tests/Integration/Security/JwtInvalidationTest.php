<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Integration\Security;

use C3net\CoreBundle\Entity\RefreshToken;
use C3net\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Integration test for JWT token invalidation on password change.
 */
class JwtInvalidationTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);
    }

    public function testPasswordChangeUpdatesPasswordChangedAtTimestamp(): void
    {
        $user = $this->createTestUser('jwt-timestamp@test.com', 'OldPassword123!');

        // Initially should be null
        $this->assertNull($user->getPasswordChangedAt());

        // Simulate password change by directly updating password
        $newHash = $this->passwordHasher->hashPassword($user, 'NewPassword456!');
        $user->setPassword($newHash);

        $this->entityManager->flush();
        $this->entityManager->clear();

        // Reload user to get fresh data
        $freshUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'jwt-timestamp@test.com']);

        // After password change via event listener, timestamp should be set
        $this->assertInstanceOf(\DateTimeImmutable::class, $freshUser->getPasswordChangedAt());
    }

    public function testPasswordChangeInvalidatesAllRefreshTokens(): void
    {
        $user = $this->createTestUser('jwt-refresh@test.com', 'OldPassword123!');

        // Create some refresh tokens
        $this->createRefreshToken($user, 'token1');
        $this->createRefreshToken($user, 'token2');
        $this->createRefreshToken($user, 'token3');

        $this->entityManager->flush();

        // Verify tokens exist
        $tokensBefore = $this->entityManager->getRepository(RefreshToken::class)
            ->findBy(['username' => $user->getUserIdentifier()]);
        $this->assertCount(3, $tokensBefore);

        // Change password
        $newHash = $this->passwordHasher->hashPassword($user, 'NewPassword456!');
        $user->setPassword($newHash);

        $this->entityManager->flush();
        $this->entityManager->clear();

        // Verify all tokens were deleted
        $tokensAfter = $this->entityManager->getRepository(RefreshToken::class)
            ->findBy(['username' => $user->getUserIdentifier()]);
        $this->assertCount(0, $tokensAfter);
    }

    public function testPasswordResetTokenClearedOnPasswordChange(): void
    {
        $user = $this->createTestUser('password-reset@test.com', 'OldPassword123!');

        // Set a password reset token
        $resetToken = bin2hex(random_bytes(32));
        $user->setPasswordResetToken($resetToken);
        $user->setPasswordResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));

        $this->entityManager->flush();

        // Verify reset token is set
        $this->assertNotNull($user->getPasswordResetToken());
        $this->assertNotNull($user->getPasswordResetTokenExpiresAt());

        // Change password
        $newHash = $this->passwordHasher->hashPassword($user, 'NewPassword456!');
        $user->setPassword($newHash);

        $this->entityManager->flush();
        $this->entityManager->clear();

        // Reload user
        $freshUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'password-reset@test.com']);

        // Reset token should be cleared
        $this->assertNull($freshUser->getPasswordResetToken());
        $this->assertNull($freshUser->getPasswordResetTokenExpiresAt());
    }

    public function testMultiplePasswordChangesUpdateTimestamp(): void
    {
        $user = $this->createTestUser('multiple-changes@test.com', 'Password1!');

        // First password change
        $hash1 = $this->passwordHasher->hashPassword($user, 'Password2!');
        $user->setPassword($hash1);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'multiple-changes@test.com']);
        $firstTimestamp = $user->getPasswordChangedAt();

        // Wait to ensure different timestamps (microtime precision)
        sleep(1); // 1 second to ensure different timestamps

        // Second password change
        $hash2 = $this->passwordHasher->hashPassword($user, 'Password3!');
        $user->setPassword($hash2);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'multiple-changes@test.com']);
        $secondTimestamp = $user->getPasswordChangedAt();

        // Second timestamp should be later than first
        $this->assertNotNull($firstTimestamp);
        $this->assertNotNull($secondTimestamp);
        $this->assertGreaterThan($firstTimestamp->getTimestamp(), $secondTimestamp->getTimestamp());
    }

    public function testRefreshTokensIsolatedBetweenUsers(): void
    {
        $user1 = $this->createTestUser('user1-tokens@test.com', 'Password123!');
        $user2 = $this->createTestUser('user2-tokens@test.com', 'Password123!');

        // Create tokens for both users
        $this->createRefreshToken($user1, 'user1-token1');
        $this->createRefreshToken($user1, 'user1-token2');
        $this->createRefreshToken($user2, 'user2-token1');
        $this->createRefreshToken($user2, 'user2-token2');

        $this->entityManager->flush();

        // Change user1's password
        $newHash = $this->passwordHasher->hashPassword($user1, 'NewPassword456!');
        $user1->setPassword($newHash);

        $this->entityManager->flush();
        $this->entityManager->clear();

        // User1's tokens should be deleted
        $user1Tokens = $this->entityManager->getRepository(RefreshToken::class)
            ->findBy(['username' => $user1->getUserIdentifier()]);
        $this->assertCount(0, $user1Tokens);

        // User2's tokens should remain
        $user2Tokens = $this->entityManager->getRepository(RefreshToken::class)
            ->findBy(['username' => $user2->getUserIdentifier()]);
        $this->assertCount(2, $user2Tokens);
    }

    private function createTestUser(string $email, string $password): User
    {
        $user = new User();
        $user->setEmail($email)
            ->setFirstName('Test')
            ->setLastName('User')
            ->setPassword($this->passwordHasher->hashPassword($user, $password))
            ->setActive(true)
            ->setLocked(false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createRefreshToken(User $user, string $tokenValue): RefreshToken
    {
        $token = new RefreshToken();
        $token->setRefreshToken(hash('sha256', $tokenValue));
        $token->setUsername($user->getUserIdentifier());
        $token->setValid(new \DateTime('+30 days'));

        $this->entityManager->persist($token);

        return $token;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test data
        $this->entityManager->getConnection()->executeStatement(
            'DELETE FROM refresh_tokens WHERE username LIKE "%@test.com"'
        );
        $this->entityManager->getConnection()->executeStatement(
            'DELETE FROM password_history WHERE user_id IN (SELECT id FROM user WHERE email LIKE "%@test.com")'
        );
        $this->entityManager->getConnection()->executeStatement(
            'DELETE FROM user WHERE email LIKE "%@test.com"'
        );
    }
}
