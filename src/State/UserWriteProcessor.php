<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Exception\PasswordReusedException;
use C3net\CoreBundle\Service\PasswordHistoryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Handles User write operations to properly hash passwords.
 *
 * @implements ProcessorInterface<User, User>
 */
final readonly class UserWriteProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<User, User> $persistProcessor
     */
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private UserPasswordHasherInterface $passwordHasher,
        private PasswordHistoryService $passwordHistoryService,
        private RateLimiterFactory $passwordChangeRateLimiter,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Hash password for User entities
        // The instanceof check is kept for runtime safety even though PHPStan knows the type
        if ($data instanceof User) { // @phpstan-ignore-line
            $this->hashPasswordIfNeeded($data);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    private function hashPasswordIfNeeded(User $user): void
    {
        $password = $user->getPassword();

        // Only hash if password is set and doesn't look like it's already hashed
        if (null === $password || '' === $password) {
            return;
        }

        // Check if password is already hashed (bcrypt, argon2, etc.)
        if ($this->isPasswordHashed($password)) {
            // Password is already hashed, don't re-hash
            return;
        }

        // RATE LIMITING - Check before processing password change
        if (null !== $user->getId()) { // Only for existing users (password changes)
            $limiter = $this->passwordChangeRateLimiter->create($user->getEmail() ?? 'unknown');
            if (!$limiter->consume(1)->isAccepted()) {
                throw new TooManyRequestsHttpException('Too many password change attempts. Please try again later.');
            }
        }

        // PASSWORD HISTORY - Check for password reuse
        if (null !== $user->getId()) { // Only for existing users (password changes)
            try {
                $this->passwordHistoryService->validatePasswordNotReused($user, $password);
            } catch (PasswordReusedException) {
                throw new BadRequestHttpException('This password was used recently. Please choose a different password.');
            }
        }

        // Store old password hash for change detection by event listener
        // Get the current hash from database
        if (null !== $user->getId()) {
            $freshUser = $this->entityManager->find(User::class, $user->getId());
            if ($freshUser instanceof User) {
                $user->setOldPasswordHash($freshUser->getPassword());
            }
        }

        // Hash the plain text password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
    }

    private function isPasswordHashed(string $password): bool
    {
        // Hashed passwords typically start with $2y$, $2b$, $argon2, etc.
        return str_starts_with($password, '$2y$')
            || str_starts_with($password, '$2b$')
            || str_starts_with($password, '$2a$')
            || str_starts_with($password, '$argon2');
    }
}
