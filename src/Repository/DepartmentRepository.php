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
     * Find department by shortcode within a company.
     */
    public function findByShortcodeAndCompany(string $shortcode, string $companyId): ?Department
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.shortcode = :shortcode')
            ->andWhere('d.company = :companyId')
            ->setParameter('shortcode', $shortcode)
            ->setParameter('companyId', $companyId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
