<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SystemEntity;
use App\Entity\User;
use App\Entity\UserSystemEntityPermission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserSystemEntityPermission>
 */
class UserSystemEntityPermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSystemEntityPermission::class);
    }

    /**
     * Find permission for a specific user and system entity.
     */
    public function findByUserAndSystemEntity(User $user, SystemEntity $systemEntity): ?UserSystemEntityPermission
    {
        return $this->createQueryBuilder('usep')
            ->andWhere('usep.user = :user')
            ->andWhere('usep.systemEntity = :systemEntity')
            ->setParameter('user', $user->getId(), 'uuid')
            ->setParameter('systemEntity', $systemEntity->getId(), 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all permissions for a specific user.
     *
     * @return UserSystemEntityPermission[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('usep')
            ->andWhere('usep.user = :user')
            ->setParameter('user', $user->getId(), 'uuid')
            ->orderBy('usep.systemEntity', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all permissions for a specific system entity.
     *
     * @return UserSystemEntityPermission[]
     */
    public function findBySystemEntity(SystemEntity $systemEntity): array
    {
        return $this->createQueryBuilder('usep')
            ->andWhere('usep.systemEntity = :systemEntity')
            ->setParameter('systemEntity', $systemEntity->getId(), 'uuid')
            ->orderBy('usep.user', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all system entities that a user has read access to.
     *
     * @return SystemEntity[]
     */
    public function findSystemEntitiesWithReadAccess(User $user): array
    {
        return $this->createQueryBuilder('usep')
            ->select('se')
            ->join('usep.systemEntity', 'se')
            ->andWhere('usep.user = :user')
            ->andWhere('usep.canRead = :canRead')
            ->setParameter('user', $user->getId(), 'uuid')
            ->setParameter('canRead', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all system entities that a user has write access to.
     *
     * @return SystemEntity[]
     */
    public function findSystemEntitiesWithWriteAccess(User $user): array
    {
        return $this->createQueryBuilder('usep')
            ->select('se')
            ->join('usep.systemEntity', 'se')
            ->andWhere('usep.user = :user')
            ->andWhere('usep.canWrite = :canWrite')
            ->setParameter('user', $user->getId(), 'uuid')
            ->setParameter('canWrite', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find users with read access to a system entity.
     *
     * @return User[]
     */
    public function findUsersWithReadAccess(SystemEntity $systemEntity): array
    {
        return $this->createQueryBuilder('usep')
            ->select('u')
            ->join('usep.user', 'u')
            ->andWhere('usep.systemEntity = :systemEntity')
            ->andWhere('usep.canRead = :canRead')
            ->setParameter('systemEntity', $systemEntity->getId(), 'uuid')
            ->setParameter('canRead', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find users with write access to a system entity.
     *
     * @return User[]
     */
    public function findUsersWithWriteAccess(SystemEntity $systemEntity): array
    {
        return $this->createQueryBuilder('usep')
            ->select('u')
            ->join('usep.user', 'u')
            ->andWhere('usep.systemEntity = :systemEntity')
            ->andWhere('usep.canWrite = :canWrite')
            ->setParameter('systemEntity', $systemEntity->getId(), 'uuid')
            ->setParameter('canWrite', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if user has read access to a system entity.
     */
    public function userHasReadAccess(User $user, SystemEntity $systemEntity): bool
    {
        $permission = $this->findByUserAndSystemEntity($user, $systemEntity);

        return $permission && $permission->canRead();
    }

    /**
     * Check if user has write access to a system entity.
     */
    public function userHasWriteAccess(User $user, SystemEntity $systemEntity): bool
    {
        $permission = $this->findByUserAndSystemEntity($user, $systemEntity);

        return $permission && $permission->canWrite();
    }

    /**
     * Grant or update permissions for a user on a system entity.
     */
    public function grantPermissions(User $user, SystemEntity $systemEntity, bool $canRead = false, bool $canWrite = false): UserSystemEntityPermission
    {
        $permission = $this->findByUserAndSystemEntity($user, $systemEntity);

        if (null === $permission) {
            $permission = new UserSystemEntityPermission();
            $permission->setUser($user);
            $permission->setSystemEntity($systemEntity);
            $this->getEntityManager()->persist($permission);
        }

        $permission->setCanRead($canRead);
        $permission->setCanWrite($canWrite);

        return $permission;
    }

    /**
     * Revoke all permissions for a user on a system entity.
     */
    public function revokePermissions(User $user, SystemEntity $systemEntity): void
    {
        $permission = $this->findByUserAndSystemEntity($user, $systemEntity);

        if (null !== $permission) {
            $this->getEntityManager()->remove($permission);
        }
    }

    /**
     * Get count of users with any access to a system entity.
     */
    public function getUserAccessCount(SystemEntity $systemEntity): int
    {
        return (int) $this->createQueryBuilder('usep')
            ->select('COUNT(DISTINCT usep.user)')
            ->andWhere('usep.systemEntity = :systemEntity')
            ->andWhere('(usep.canRead = :canRead OR usep.canWrite = :canWrite)')
            ->setParameter('systemEntity', $systemEntity->getId(), 'uuid')
            ->setParameter('canRead', true)
            ->setParameter('canWrite', true)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
