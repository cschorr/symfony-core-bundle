<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DomainEntityPermission;
use App\Entity\UserGroup;
use App\Entity\UserGroupDomainEntityPermission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserGroupDomainEntityPermission>
 */
class UserGroupDomainEntityPermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserGroupDomainEntityPermission::class);
    }

    /**
     * Find permission for a specific user and system entity.
     */
    public function findByUserGroupAndSystemEntity(UserGroup $userGroup, DomainEntityPermission $systemEntity): ?UserGroupDomainEntityPermission
    {
        // get
        return $this->createQueryBuilder('usep')
            ->andWhere('usep.user = :user')
            ->andWhere('usep.domainEntityPermission = :systemEntity')
            ->setParameter('user', $userGroup->getId(), 'uuid')
            ->setParameter('systemEntity', $systemEntity->getId(), 'uuid')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all permissions for a specific user.
     *
     * @return UserGroupDomainEntityPermission[]
     */
    public function findByUser(UserGroup $userGroup): array
    {
        return $this->createQueryBuilder('usep')
            ->andWhere('usep.user = :user')
            ->setParameter('user', $userGroup->getId(), 'uuid')
            ->orderBy('usep.domainEntityPermission', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all permissions for a specific system entity.
     *
     * @return UserGroupDomainEntityPermission[]
     */
    public function findBySystemEntity(DomainEntityPermission $systemEntity): array
    {
        return $this->createQueryBuilder('usep')
            ->andWhere('usep.domainEntityPermission = :systemEntity')
            ->setParameter('systemEntity', $systemEntity->getId(), 'uuid')
            ->orderBy('usep.user', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all system entities that a user has read access to.
     *
     * @return DomainEntityPermission[]
     */
    public function findSystemEntitiesWithReadAccess(UserGroup $userGroup): array
    {
        return $this->createQueryBuilder('usep')
            ->select('se')
            ->join('usep.domainEntityPermission', 'se')
            ->andWhere('usep.user = :user')
            ->andWhere('usep.canRead = :canRead')
            ->setParameter('user', $userGroup->getId(), 'uuid')
            ->setParameter('canRead', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all system entities that a user has write access to.
     *
     * @return DomainEntityPermission[]
     */
    public function findSystemEntitiesWithWriteAccess(UserGroup $userGroup): array
    {
        return $this->createQueryBuilder('usep')
            ->select('se')
            ->join('usep.domainEntityPermission', 'se')
            ->andWhere('usep.user = :user')
            ->andWhere('usep.canWrite = :canWrite')
            ->setParameter('user', $userGroup->getId(), 'uuid')
            ->setParameter('canWrite', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find users with read access to a system entity.
     *
     * @return UserGroup[]
     */
    public function findUsersWithReadAccess(DomainEntityPermission $systemEntity): array
    {
        return $this->createQueryBuilder('usep')
            ->select('u')
            ->join('usep.user', 'u')
            ->andWhere('usep.domainEntityPermission = :systemEntity')
            ->andWhere('usep.canRead = :canRead')
            ->setParameter('systemEntity', $systemEntity->getId(), 'uuid')
            ->setParameter('canRead', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find users with write access to a system entity.
     *
     * @return UserGroup[]
     */
    public function findUsersWithWriteAccess(DomainEntityPermission $systemEntity): array
    {
        return $this->createQueryBuilder('usep')
            ->select('u')
            ->join('usep.user', 'u')
            ->andWhere('usep.domainEntityPermission = :systemEntity')
            ->andWhere('usep.canWrite = :canWrite')
            ->setParameter('systemEntity', $systemEntity->getId(), 'uuid')
            ->setParameter('canWrite', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Grant or update permissions for a user on a system entity.
     */
    public function grantPermissions(UserGroup $userGroup, DomainEntityPermission $systemEntity, bool $canRead = false, bool $canWrite = false): UserGroupDomainEntityPermission
    {
        $permission = $this->findByUserGroupAndSystemEntity($user, $systemEntity);

        if (null === $permission) {
            $permission = new UserGroupDomainEntityPermission();
            $permission->setUserGroup($user);
            $permission->setDomainEntityPermission($systemEntity);
            $this->getEntityManager()->persist($permission);
        }

        $permission->setCanRead($canRead);
        $permission->setCanWrite($canWrite);

        return $permission;
    }

    /**
     * Revoke all permissions for a user on a system entity.
     */
    public function revokePermissions(UserGroup $userGroup, DomainEntityPermission $systemEntity): void
    {
        $permission = $this->findByUserGroupAndSystemEntity($userGroup, $systemEntity);

        if (null !== $permission) {
            $this->getEntityManager()->remove($permission);
        }
    }

    /**
     * Get count of users with any access to a system entity.
     */
    public function getUserAccessCount(DomainEntityPermission $systemEntity): int
    {
        return (int) $this->createQueryBuilder('usep')
            ->select('COUNT(DISTINCT usep.user)')
            ->andWhere('usep.domainEntityPermission = :systemEntity')
            ->andWhere('(usep.canRead = :canRead OR usep.canWrite = :canWrite)')
            ->setParameter('systemEntity', $systemEntity->getId(), 'uuid')
            ->setParameter('canRead', true)
            ->setParameter('canWrite', true)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
