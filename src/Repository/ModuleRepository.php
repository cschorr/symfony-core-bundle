<?php

namespace App\Repository;

use App\Entity\Module;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Module>
 */
class ModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Module::class);
    }

    /**
     * Find all modules that a user has any permission for
     * @return Module[]
     */
    public function findModulesForUser(User $user): array
    {
        $userIdHex = str_replace('-', '', $user->getId()->toRfc4122());
        
        $sql = 'SELECT HEX(m.id) as id_hex FROM module m 
                INNER JOIN user_module_permission ump ON m.id = ump.module_id 
                WHERE ump.user_id = UNHEX(:userIdHex) 
                AND (ump.can_read = 1 OR ump.can_write = 1) 
                ORDER BY m.id ASC';
        
        $connection = $this->getEntityManager()->getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->bindValue('userIdHex', $userIdHex);
        
        $results = $stmt->executeQuery()->fetchAllAssociative();
        
        // Convert results to entities
        $modules = [];
        foreach ($results as $row) {
            $uuid = \Symfony\Component\Uid\Uuid::fromString(
                substr($row['id_hex'], 0, 8) . '-' . 
                substr($row['id_hex'], 8, 4) . '-' . 
                substr($row['id_hex'], 12, 4) . '-' . 
                substr($row['id_hex'], 16, 4) . '-' . 
                substr($row['id_hex'], 20, 12)
            );
            $module = $this->find($uuid);
            if ($module) {
                $modules[] = $module;
            }
        }
        
        return $modules;
    }

    /**
     * Find all modules that a user has read access to
     * @return Module[]
     */
    public function findReadableModulesForUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.userPermissions', 'ump')
            ->andWhere('ump.user = :user')
            ->andWhere('ump.canRead = true')
            ->setParameter('user', $user)
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all modules that a user has write access to
     * @return Module[]
     */
    public function findWritableModulesForUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.userPermissions', 'ump')
            ->andWhere('ump.user = :user')
            ->andWhere('ump.canWrite = true')
            ->setParameter('user', $user)
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all modules that have no permissions assigned
     * @return Module[]
     */
    public function findModulesWithoutPermissions(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.userPermissions', 'ump')
            ->andWhere('ump.id IS NULL')
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all active modules that a user has read access to
     * @return Module[]
     */
    public function findActiveReadableModulesForUser(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.userPermissions', 'ump')
            ->andWhere('ump.user = :user')
            ->andWhere('ump.canRead = true')
            ->andWhere('m.active = true')
            ->setParameter('user', $user)
            ->orderBy('m.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all active modules that a user has any permission for
     * @return Module[]
     */
    public function findActiveModulesForUser(User $user): array
    {
        $userIdHex = str_replace('-', '', $user->getId()->toRfc4122());
        
        $sql = 'SELECT HEX(m.id) as id_hex FROM module m 
                INNER JOIN user_module_permission ump ON m.id = ump.module_id 
                WHERE ump.user_id = UNHEX(:userIdHex) 
                AND (ump.can_read = 1 OR ump.can_write = 1) 
                AND m.active = 1 
                ORDER BY m.id ASC';
        
        $connection = $this->getEntityManager()->getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->bindValue('userIdHex', $userIdHex);
        
        $results = $stmt->executeQuery()->fetchAllAssociative();
        
        // Convert results to entities
        $modules = [];
        foreach ($results as $row) {
            $uuid = \Symfony\Component\Uid\Uuid::fromString(
                substr($row['id_hex'], 0, 8) . '-' . 
                substr($row['id_hex'], 8, 4) . '-' . 
                substr($row['id_hex'], 12, 4) . '-' . 
                substr($row['id_hex'], 16, 4) . '-' . 
                substr($row['id_hex'], 20, 12)
            );
            $module = $this->find($uuid);
            if ($module) {
                $modules[] = $module;
            }
        }
        
        return $modules;
    }

    /**
     * Find all active modules that a user has any permission for using native SQL
     * @return Module[]
     */
    public function findActiveModulesForUserNative(User $user): array
    {
        $userIdHex = str_replace('-', '', $user->getId()->toRfc4122());
        
        $sql = 'SELECT HEX(m.id) as id_hex, m.name, m.code, m.active FROM module m 
                INNER JOIN user_module_permission ump ON m.id = ump.module_id 
                WHERE ump.user_id = UNHEX(:userIdHex) 
                AND (ump.can_read = 1 OR ump.can_write = 1) 
                AND m.active = 1 
                ORDER BY m.id ASC';
        
        $connection = $this->getEntityManager()->getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->bindValue('userIdHex', $userIdHex);
        
        $results = $stmt->executeQuery()->fetchAllAssociative();
        
        // Convert results to entities by finding them by ID
        $modules = [];
        foreach ($results as $row) {
            $uuid = \Symfony\Component\Uid\Uuid::fromString(
                substr($row['id_hex'], 0, 8) . '-' . 
                substr($row['id_hex'], 8, 4) . '-' . 
                substr($row['id_hex'], 12, 4) . '-' . 
                substr($row['id_hex'], 16, 4) . '-' . 
                substr($row['id_hex'], 20, 12)
            );
            $module = $this->find($uuid);
            if ($module) {
                $modules[] = $module;
            }
        }
        
        return $modules;
    }

//    /**
//     * @return Module[] Returns an array of Module objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Company
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
