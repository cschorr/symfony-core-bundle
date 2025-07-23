<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SystemEntity;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SystemEntity>
 */
class SystemEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SystemEntity::class);
    }

    /**
     * Find all system entities that a user has any permission for.
     *
     * @return SystemEntity[]
     */
    public function findSystemEntitiesForUser(User $user): array
    {
        return $this->createQueryBuilder('se')
            ->join('se.userPermissions', 'up')
            ->andWhere('up.user = :user')
            ->andWhere('up.canRead = true OR up.canWrite = true')
            ->setParameter('user', $user->getId(), 'uuid')
            ->orderBy('se.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all active system entities that a user has any permission for.
     *
     * @return SystemEntity[]
     */
    public function findActiveSystemEntitiesForUser(User $user): array
    {
        return $this->createQueryBuilder('se')
            ->join('se.userPermissions', 'up')
            ->andWhere('up.user = :user')
            ->andWhere('se.active = :active')
            ->andWhere('up.canRead = true OR up.canWrite = true')
            ->setParameter('user', $user->getId(), 'uuid')
            ->setParameter('active', true)
            ->orderBy('se.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all active system entities (for admin users).
     *
     * @return SystemEntity[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('se')
            ->andWhere('se.active = :active')
            ->setParameter('active', true)
            ->orderBy('se.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if system entity exists by code.
     */
    public function existsByCode(string $code): bool
    {
        return (bool) $this->createQueryBuilder('se')
            ->select('1')
            ->andWhere('se.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find system entity by code.
     */
    public function findOneByCode(string $code): ?SystemEntity
    {
        return $this->createQueryBuilder('se')
            ->andWhere('se.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find system entities with users having read access.
     *
     * @return SystemEntity[]
     */
    public function findWithReadUsers(): array
    {
        return $this->createQueryBuilder('se')
            ->join('se.userPermissions', 'up')
            ->andWhere('up.canRead = :canRead')
            ->setParameter('canRead', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find system entities with users having write access.
     *
     * @return SystemEntity[]
     */
    public function findWithWriteUsers(): array
    {
        return $this->createQueryBuilder('se')
            ->join('se.userPermissions', 'up')
            ->andWhere('up.canWrite = :canWrite')
            ->setParameter('canWrite', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find system entities that a specific user can read.
     *
     * @return SystemEntity[]
     */
    public function findReadableByUser(User $user): array
    {
        return $this->createQueryBuilder('se')
            ->join('se.userPermissions', 'up')
            ->andWhere('up.user = :user')
            ->andWhere('up.canRead = :canRead')
            ->setParameter('user', $user->getId(), 'uuid')
            ->setParameter('canRead', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find system entities that a specific user can write to.
     *
     * @return SystemEntity[]
     */
    public function findWritableByUser(User $user): array
    {
        return $this->createQueryBuilder('se')
            ->join('se.userPermissions', 'up')
            ->andWhere('up.user = :user')
            ->andWhere('up.canWrite = :canWrite')
            ->setParameter('user', $user->getId(), 'uuid')
            ->setParameter('canWrite', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get count of users with access to a system entity.
     */
    public function getUserAccessCount(SystemEntity $systemEntity): int
    {
        return (int) $this->getEntityManager()
            ->createQuery('SELECT COUNT(DISTINCT up.user) FROM App\Entity\UserSystemEntityPermission up 
                          WHERE up.systemEntity = :systemEntity AND (up.canRead = true OR up.canWrite = true)')
            ->setParameter('systemEntity', $systemEntity)
            ->getSingleScalarResult();
    }

    /**
     * Check if user has read access to system entity.
     */
    public function userHasReadAccess(User $user, SystemEntity $systemEntity): bool
    {
        return (bool) $this->getEntityManager()
            ->createQuery('SELECT 1 FROM App\Entity\UserSystemEntityPermission up 
                          WHERE up.user = :user AND up.systemEntity = :systemEntity AND up.canRead = true')
            ->setParameter('user', $user->getId(), 'uuid')
            ->setParameter('systemEntity', $systemEntity)
            ->getOneOrNullResult();
    }

    /**
     * Check if user has write access to system entity.
     */
    public function userHasWriteAccess(User $user, SystemEntity $systemEntity): bool
    {
        return (bool) $this->getEntityManager()
            ->createQuery('SELECT 1 FROM App\Entity\UserSystemEntityPermission up 
                          WHERE up.user = :user AND up.systemEntity = :systemEntity AND up.canWrite = true')
            ->setParameter('user', $user->getId(), 'uuid')
            ->setParameter('systemEntity', $systemEntity)
            ->getOneOrNullResult();
    }

    /**
     * Check if user has any access to system entity.
     */
    public function userHasAnyAccess(User $user, SystemEntity $systemEntity): bool
    {
        return (bool) $this->getEntityManager()
            ->createQuery('SELECT 1 FROM App\Entity\UserSystemEntityPermission up 
                          WHERE up.user = :user AND up.systemEntity = :systemEntity AND (up.canRead = true OR up.canWrite = true)')
            ->setParameter('user', $user->getId(), 'uuid')
            ->setParameter('systemEntity', $systemEntity)
            ->getOneOrNullResult();
    }
}
