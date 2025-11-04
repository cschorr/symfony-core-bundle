<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Repository;

use C3net\CoreBundle\Entity\PasswordResetToken;
use C3net\CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetToken>
 */
class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    /**
     * Find a valid token by its hash.
     */
    public function findValidToken(string $tokenHash): ?PasswordResetToken
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.tokenHash = :hash')
            ->andWhere('t.expiresAt > :now')
            ->andWhere('t.usedAt IS NULL')
            ->setParameter('hash', $tokenHash)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Count recent reset requests for an email (for rate limiting).
     */
    public function countRecentRequests(string $email, \DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.email = :email')
            ->andWhere('t.createdAt >= :since')
            ->setParameter('email', $email)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Invalidate all tokens for a user except the given token.
     */
    public function invalidateUserTokens(User $user, ?PasswordResetToken $except = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->update()
            ->set('t.usedAt', ':now')
            ->where('t.user = :user')
            ->andWhere('t.usedAt IS NULL')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('user', $user);

        if (null !== $except) {
            $qb->andWhere('t.id != :exceptId')
                ->setParameter('exceptId', $except->getId());
        }

        return $qb->getQuery()->execute();
    }

    /**
     * Delete expired or used tokens.
     */
    public function deleteExpiredTokens(): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->where('t.expiresAt < :now')
            ->orWhere('t.usedAt IS NOT NULL')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * Find all tokens for a specific user.
     *
     * @return PasswordResetToken[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all active (valid) tokens for a specific email.
     *
     * @return PasswordResetToken[]
     */
    public function findActiveTokensByEmail(string $email): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.email = :email')
            ->andWhere('t.expiresAt > :now')
            ->andWhere('t.usedAt IS NULL')
            ->setParameter('email', $email)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
