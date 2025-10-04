<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Repository;

use C3net\CoreBundle\Entity\Department;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Department>
 */
class DepartmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Department::class);
    }

    /**
     * Find departments by company.
     *
     * @return Department[]
     */
    public function findByCompany(string $companyId): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.company = :companyId')
            ->setParameter('companyId', $companyId)
            ->orderBy('d.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find department by code within a company.
     */
    public function findByCodeAndCompany(string $code, string $companyId): ?Department
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.code = :code')
            ->andWhere('d.company = :companyId')
            ->setParameter('code', $code)
            ->setParameter('companyId', $companyId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
