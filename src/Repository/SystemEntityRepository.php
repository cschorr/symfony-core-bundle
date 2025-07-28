<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SystemEntity;
use App\Entity\User;
use App\Entity\UserGroup;
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
     * Find all system entities that a user group has any permission for.
     *
     * @return SystemEntity[]
     */
    public function findSystemEntitiesForUser(UserGroup $userGroup): array
    {
        return $this->createQueryBuilder('se')
            ->join('se.userGroupPermissions', 'up')
            ->andWhere('up.userGroup = :userGroup')
            ->andWhere('up.canRead = true OR up.canWrite = true')
            ->setParameter('userGroup', $userGroup->getId(), 'uuid')
            ->orderBy('se.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all active system entities that a user group has any permission for.
     *
     * @return SystemEntity[]
     */
    public function findActiveSystemEntitiesForUser(UserGroup $userGroup): array
    {
        return $this->createQueryBuilder('se')
            ->join('se.userGroupPermissions', 'up')
            ->andWhere('up.userGroup = :userGroup')
            ->andWhere('se.active = :active')
            ->andWhere('up.canRead = true OR up.canWrite = true')
            ->setParameter('userGroup', $userGroup->getId(), 'uuid')
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
     * Find system entities with user groups having read access.
     *
     * @return SystemEntity[]
     */
    public function findWithReadUsers(): array
    {
        return $this->createQueryBuilder('se')
            ->join('se.userGroupPermissions', 'up')
            ->andWhere('up.canRead = :canRead')
            ->setParameter('canRead', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find system entities with user groups having write access.
     *
     * @return SystemEntity[]
     */
    public function findWithWriteUsers(): array
    {
        return $this->createQueryBuilder('se')
            ->join('se.userGroupPermissions', 'up')
            ->andWhere('up.canWrite = :canWrite')
            ->setParameter('canWrite', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find system entities that a specific user group can read.
     *
     * @return SystemEntity[]
     */
    public function findReadableByUser(UserGroup $userGroup): array
    {
        return $this->createQueryBuilder('se')
            ->join('se.userGroupPermissions', 'up')
            ->andWhere('up.userGroup = :userGroup')
            ->andWhere('up.canRead = :canRead')
            ->setParameter('userGroup', $userGroup->getId(), 'uuid')
            ->setParameter('canRead', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find system entities that a specific user group can write to.
     *
     * @return SystemEntity[]
     */
    public function findWritableByUser(UserGroup $userGroup): array
    {
        return $this->createQueryBuilder('se')
            ->join('se.userGroupPermissions', 'up')
            ->andWhere('up.userGroup = :userGroup')
            ->andWhere('up.canWrite = :canWrite')
            ->setParameter('userGroup', $userGroup->getId(), 'uuid')
            ->setParameter('canWrite', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get count of user groups with access to a system entity.
     */
    public function getUserAccessCount(SystemEntity $systemEntity): int
    {
        return (int) $this->getEntityManager()
            ->createQuery('SELECT COUNT(DISTINCT up.userGroup) FROM App\Entity\UserGroupSystemEntityPermission up
                          WHERE up.systemEntity = :systemEntity AND (up.canRead = true OR up.canWrite = true)')
            ->setParameter('systemEntity', $systemEntity)
            ->getSingleScalarResult();
    }

    /**
     * Check if user group has read access to system entity.
     */
    public function userHasReadAccess(UserGroup $userGroup, SystemEntity $systemEntity): bool
    {
        return (bool) $this->getEntityManager()
            ->createQuery('SELECT 1 FROM App\Entity\UserGroupSystemEntityPermission up
                          WHERE up.userGroup = :userGroup AND up.systemEntity = :systemEntity AND up.canRead = true')
            ->setParameter('userGroup', $userGroup->getId(), 'uuid')
            ->setParameter('systemEntity', $systemEntity)
            ->getOneOrNullResult();
    }

    /**
     * Check if user group has write access to system entity.
     */
    public function userHasWriteAccess(UserGroup $userGroup, SystemEntity $systemEntity): bool
    {
        return (bool) $this->getEntityManager()
            ->createQuery('SELECT 1 FROM App\Entity\UserGroupSystemEntityPermission up
                          WHERE up.userGroup = :userGroup AND up.systemEntity = :systemEntity AND up.canWrite = true')
            ->setParameter('userGroup', $userGroup->getId(), 'uuid')
            ->setParameter('systemEntity', $systemEntity)
            ->getOneOrNullResult();
    }

    /**
     * Check if user group has any access to system entity.
     */
    public function userHasAnyAccess(UserGroup $userGroup, SystemEntity $systemEntity): bool
    {
        return (bool) $this->getEntityManager()
            ->createQuery('SELECT 1 FROM App\Entity\UserGroupSystemEntityPermission up
                          WHERE up.userGroup = :userGroup AND up.systemEntity = :systemEntity AND (up.canRead = true OR up.canWrite = true)')
            ->setParameter('userGroup', $userGroup->getId(), 'uuid')
            ->setParameter('systemEntity', $systemEntity)
            ->getOneOrNullResult();
    }
}
