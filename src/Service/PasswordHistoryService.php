<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Service;

use C3net\CoreBundle\Entity\PasswordHistory;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Exception\PasswordReusedException;
use C3net\CoreBundle\Repository\PasswordHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;

class PasswordHistoryService
{
    private const int PASSWORD_HISTORY_LIMIT = 10;
    private const int PASSWORD_HISTORY_MAX_AGE_DAYS = 365;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    private function getRepository(): PasswordHistoryRepository
    {
        $repository = $this->entityManager->getRepository(PasswordHistory::class);
        assert($repository instanceof PasswordHistoryRepository);

        return $repository;
    }

    /**
     * Validate that the password hasn't been used recently.
     *
     * @throws PasswordReusedException if password was used in the last 10 passwords
     */
    public function validatePasswordNotReused(User $user, string $plainPassword): void
    {
        $recentPasswords = $this->getRepository()->findByUser(
            $user,
            self::PASSWORD_HISTORY_LIMIT
        );

        foreach ($recentPasswords as $historyEntry) {
            if (password_verify($plainPassword, $historyEntry->getPasswordHash())) {
                throw new PasswordReusedException();
            }
        }
    }

    /**
     * Store a password hash in the user's password history.
     */
    public function storePasswordHash(User $user, string $passwordHash): void
    {
        // Create new password history entry
        $historyEntry = new PasswordHistory();
        $historyEntry->setUser($user);
        $historyEntry->setPasswordHash($passwordHash);

        $this->entityManager->persist($historyEntry);
        $this->entityManager->flush();

        // Cleanup: Keep only the most recent entries
        $this->enforceHistoryLimit($user);
    }

    /**
     * Get the number of password history entries for a user.
     */
    public function getHistoryCount(User $user): int
    {
        return $this->getRepository()->countByUser($user);
    }

    /**
     * Clean up old password history entries (older than 1 year).
     */
    public function cleanupOldHistory(User $user): int
    {
        $cutoffDate = new \DateTimeImmutable(
            sprintf('-%d days', self::PASSWORD_HISTORY_MAX_AGE_DAYS)
        );

        return $this->getRepository()->deleteOlderThan($cutoffDate, $user);
    }

    /**
     * Enforce password history limit by deleting oldest entries.
     */
    private function enforceHistoryLimit(User $user): void
    {
        $this->getRepository()->deleteOldest($user, self::PASSWORD_HISTORY_LIMIT);
    }
}
