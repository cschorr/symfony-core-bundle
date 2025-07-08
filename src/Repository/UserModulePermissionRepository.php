<?php

namespace App\Repository;

use App\Entity\UserModulePermission;
use App\Entity\User;
use App\Entity\Module;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserModulePermission>
 */
class UserModulePermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserModulePermission::class);
    }

    /**
     * Find permission for a specific user and module
     */
    public function findByUserAndModule(User $user, Module $module): ?UserModulePermission
    {
        // Workaround: Use entity relationships instead of queries
        // This works around a potential issue with UUID v7 parameter binding
        foreach ($user->getModulePermissions() as $permission) {
            if ($permission->getModule()->getId()->equals($module->getId())) {
                return $permission;
            }
        }
        
        return null;
    }

    /**
     * Find all permissions for a specific user
     * @return UserModulePermission[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('ump')
            ->andWhere('ump.user = :user')
            ->setParameter('user', $user)
            ->orderBy('ump.module', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all permissions for a specific module
     * @return UserModulePermission[]
     */
    public function findByModule(Module $module): array
    {
        return $this->createQueryBuilder('ump')
            ->andWhere('ump.module = :module')
            ->setParameter('module', $module)
            ->orderBy('ump.user', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all modules that a user has read access to
     * @return Module[]
     */
    public function findModulesWithReadAccess(User $user): array
    {
        return $this->createQueryBuilder('ump')
            ->select('m')
            ->join('ump.module', 'm')
            ->andWhere('ump.user = :user')
            ->andWhere('ump.canRead = true')
            ->setParameter('user', $user)
            ->orderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all modules that a user has write access to
     * @return Module[]
     */
    public function findModulesWithWriteAccess(User $user): array
    {
        return $this->createQueryBuilder('ump')
            ->select('m')
            ->join('ump.module', 'm')
            ->andWhere('ump.user = :user')
            ->andWhere('ump.canWrite = true')
            ->setParameter('user', $user)
            ->orderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if user has read access to a module
     */
    public function hasReadAccess(User $user, Module $module): bool
    {
        $permission = $this->findByUserAndModule($user, $module);
        return $permission && $permission->canRead();
    }

    /**
     * Check if user has write access to a module
     */
    public function hasWriteAccess(User $user, Module $module): bool
    {
        $permission = $this->findByUserAndModule($user, $module);
        return $permission && $permission->canWrite();
    }

    /**
     * Grant or update permissions for a user and module
     */
    public function grantPermissions(User $user, Module $module, bool $canRead = false, bool $canWrite = false): UserModulePermission
    {
        $permission = $this->findByUserAndModule($user, $module);
        
        if (!$permission) {
            $permission = new UserModulePermission();
            $permission->setUser($user);
            $permission->setModule($module);
        }
        
        $permission->setCanRead($canRead);
        $permission->setCanWrite($canWrite);
        
        $this->getEntityManager()->persist($permission);
        $this->getEntityManager()->flush();
        
        return $permission;
    }

    /**
     * Revoke all permissions for a user and module
     */
    public function revokePermissions(User $user, Module $module): void
    {
        $permission = $this->findByUserAndModule($user, $module);
        
        if ($permission) {
            $this->getEntityManager()->remove($permission);
            $this->getEntityManager()->flush();
        }
    }
}
