<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Repository;

use C3net\CoreBundle\Entity\PasswordHistory;
use C3net\CoreBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordHistory>
 */
class PasswordHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordHistory::class);
    }

    /**
     * Get password history for a user, ordered by most recent first.
     *
     * @param int|null $limit Maximum number of entries to return
     *
     * @return list<PasswordHistory>
     */
    public function findByUser(User $user, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('ph')
            ->where('ph.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ph.createdAt', 'DESC');

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        /* @var list<PasswordHistory> */
        return $qb->getQuery()->getResult();
    }

    /**
     * Delete password history older than a certain date.
     */
    public function deleteOlderThan(\DateTimeImmutable $date, User $user): int
    {
        return $this->createQueryBuilder('ph')
            ->delete()
            ->where('ph.user = :user')
            ->andWhere('ph.createdAt < :date')
            ->setParameter('user', $user)
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }

    /**
     * Get the count of password history entries for a user.
     */
    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('ph')
            ->select('COUNT(ph.id)')
            ->where('ph.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Delete oldest password history entries beyond the limit.
     */
    public function deleteOldest(User $user, int $keepCount): int
    {
        $entriesToKeep = $this->findByUser($user, $keepCount);

        if (count($entriesToKeep) < $keepCount) {
            return 0; // Not enough entries to delete
        }

        $oldestToKeep = end($entriesToKeep);

        if (!$oldestToKeep instanceof PasswordHistory) {
            return 0; // No valid entry found
        }

        return $this->createQueryBuilder('ph')
            ->delete()
            ->where('ph.user = :user')
            ->andWhere('ph.createdAt < :date')
            ->setParameter('user', $user)
            ->setParameter('date', $oldestToKeep->getCreatedAt())
            ->getQuery()
            ->execute();
    }
}
