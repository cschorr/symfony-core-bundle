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
        return $this->createQueryBuilder('m')
            ->join('m.userPermissions', 'ump')
            ->andWhere('ump.user = :user')
            ->andWhere('ump.canRead = true OR ump.canWrite = true')
            ->setParameter('user', $user)
            ->orderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();
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
            ->orderBy('m.name', 'ASC')
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
            ->orderBy('m.name', 'ASC')
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
            ->orderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();
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
