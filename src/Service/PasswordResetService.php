<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Service;

use C3net\CoreBundle\Entity\PasswordResetToken;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Repository\PasswordResetTokenRepository;
use C3net\CoreBundle\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetService
{
    private const int TOKEN_LENGTH = 32;
     // 32 bytes = 64 characters when hex-encoded
    private const int TOKEN_LIFETIME_MINUTES = 30;

    private const int MAX_REQUESTS_PER_HOUR = 3;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PasswordResetTokenRepository $tokenRepository,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Create a password reset token for a user.
     *
     * @return string The plain (unhashed) token to send via email
     */
    public function createResetToken(User $user, Request $request): string
    {
        // Generate cryptographically secure random token
        $plainToken = bin2hex(random_bytes(self::TOKEN_LENGTH));

        // Hash token for database storage
        $tokenHash = hash('sha256', $plainToken);

        // Create token entity
        $userEmail = $user->getEmail();
        if (null === $userEmail) {
            throw new \LogicException('User must have an email address for password reset');
        }

        $resetToken = new PasswordResetToken();
        $resetToken->setTokenHash($tokenHash);
        $resetToken->setUser($user);
        $resetToken->setEmail($userEmail);
        $resetToken->setExpiresAt(new \DateTimeImmutable(sprintf('+%d minutes', self::TOKEN_LIFETIME_MINUTES)));
        $resetToken->setIpAddress($request->getClientIp() ?? 'unknown');
        $resetToken->setUserAgent($request->headers->get('User-Agent'));

        $this->entityManager->persist($resetToken);
        $this->entityManager->flush();

        $this->logger->info('Password reset token created', [
            'user_id' => $user->getId()?->toString(),
            'user_email' => $user->getEmail(),
            'ip_address' => $resetToken->getIpAddress(),
            'expires_at' => $resetToken->getExpiresAt()->format('Y-m-d H:i:s'),
        ]);

        return $plainToken;
    }

    /**
     * Validate a password reset token.
     *
     * @return PasswordResetToken|null The valid token or null if invalid/expired/used
     */
    public function validateToken(string $plainToken): ?PasswordResetToken
    {
        // Hash the provided token
        $tokenHash = hash('sha256', $plainToken);

        // Find the token
        $token = $this->tokenRepository->findValidToken($tokenHash);

        if (null === $token) {
            $this->logger->warning('Invalid or expired password reset token attempt', [
                'token_hash_prefix' => substr($tokenHash, 0, 10) . '...',
            ]);

            return null;
        }

        $this->logger->info('Password reset token validated successfully', [
            'user_id' => $token->getUser()->getId()?->toString(),
            'user_email' => $token->getEmail(),
        ]);

        return $token;
    }

    /**
     * Reset user password using a valid token.
     */
    public function resetPassword(PasswordResetToken $token, string $newPassword): void
    {
        $user = $token->getUser();

        // Hash the new password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);

        // Update user password
        $user->setPassword($hashedPassword);
        $user->setPasswordChangedAt(new \DateTimeImmutable());

        // Mark token as used
        $token->markAsUsed();

        // Invalidate all other tokens for this user
        $this->tokenRepository->invalidateUserTokens($user, $token);

        $this->entityManager->flush();

        $this->logger->info('Password reset completed successfully', [
            'user_id' => $user->getId()?->toString(),
            'user_email' => $user->getEmail(),
        ]);
    }

    /**
     * Check if a user can request a password reset (rate limiting).
     */
    public function canRequestReset(string $email): bool
    {
        $oneHourAgo = new \DateTimeImmutable('-1 hour');
        $count = $this->tokenRepository->countRecentRequests($email, $oneHourAgo);

        $canRequest = $count < self::MAX_REQUESTS_PER_HOUR;

        if (!$canRequest) {
            $this->logger->warning('Password reset rate limit exceeded', [
                'email' => $email,
                'request_count' => $count,
            ]);
        }

        return $canRequest;
    }

    /**
     * Find user by email address.
     */
    public function findUserByEmail(string $email): ?User
    {
        return $this->userRepository->findOneBy(['email' => $email]);
    }

    /**
     * Delete expired and used tokens (for cleanup command).
     *
     * @return int Number of tokens deleted
     */
    public function cleanupExpiredTokens(): int
    {
        $deletedCount = $this->tokenRepository->deleteExpiredTokens();

        $this->logger->info('Password reset tokens cleanup completed', [
            'deleted_count' => $deletedCount,
        ]);

        return $deletedCount;
    }

    /**
     * Get the token lifetime in minutes.
     */
    public function getTokenLifetimeMinutes(): int
    {
        return self::TOKEN_LIFETIME_MINUTES;
    }

    /**
     * Get the maximum requests per hour for rate limiting.
     */
    public function getMaxRequestsPerHour(): int
    {
        return self::MAX_REQUESTS_PER_HOUR;
    }
}
