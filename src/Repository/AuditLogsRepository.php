<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Repository;

use C3net\CoreBundle\Entity\AuditLogs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditLogs>
 */
class AuditLogsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLogs::class);
    }

    //    /**
    //     * @return AuditLogs[] Returns an array of AuditLogs objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?AuditLogs
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Get all unique authors from audit logs
     *
     * @return array
     */
    public function findUniqueAuthors(): array
    {
        return $this->createQueryBuilder('a')
            ->select('DISTINCT IDENTITY(a.author) as author_id')
            ->leftJoin('a.author', 'u')
            ->addSelect('u.id, u.email, u.firstname, u.lastname')
            ->where('a.author IS NOT NULL')
            ->orderBy('u.firstname', 'ASC')
            ->addOrderBy('u.lastname', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all unique resources from audit logs
     *
     * @return array
     */
    public function findUniqueResources(): array
    {
        return $this->createQueryBuilder('a')
            ->select('DISTINCT a.resource')
            ->where('a.resource IS NOT NULL')
            ->orderBy('a.resource', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all unique actions from audit logs
     *
     * @return array
     */
    public function findUniqueActions(): array
    {
        return $this->createQueryBuilder('a')
            ->select('DISTINCT a.action')
            ->where('a.action IS NOT NULL')
            ->orderBy('a.action', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
