<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Repository;

use C3net\CoreBundle\Entity\Project;
use C3net\CoreBundle\Entity\ProjectDate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProjectDate>
 */
class ProjectDateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectDate::class);
    }

    /**
     * Find all dates for a specific project, ordered by date.
     *
     * @return ProjectDate[]
     */
    public function findByProject(Project $project, string $order = 'ASC'): array
    {
        return $this->createQueryBuilder('pd')
            ->where('pd.project = :project')
            ->setParameter('project', $project)
            ->orderBy('pd.date', $order)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find upcoming dates for a project.
     *
     * @return ProjectDate[]
     */
    public function findUpcomingDates(Project $project, ?\DateTimeImmutable $fromDate = null): array
    {
        $qb = $this->createQueryBuilder('pd')
            ->where('pd.project = :project')
            ->setParameter('project', $project);

        $from = $fromDate ?? new \DateTimeImmutable('now');
        $qb->andWhere('pd.date >= :fromDate')
            ->setParameter('fromDate', $from)
            ->orderBy('pd.date', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Find past dates for a project.
     *
     * @return ProjectDate[]
     */
    public function findPastDates(Project $project, ?\DateTimeImmutable $toDate = null): array
    {
        $qb = $this->createQueryBuilder('pd')
            ->where('pd.project = :project')
            ->setParameter('project', $project);

        $to = $toDate ?? new \DateTimeImmutable('now');
        $qb->andWhere('pd.date < :toDate')
            ->setParameter('toDate', $to)
            ->orderBy('pd.date', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Find dates within a date range for a project.
     *
     * @return ProjectDate[]
     */
    public function findByDateRange(
        Project $project,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array {
        return $this->createQueryBuilder('pd')
            ->where('pd.project = :project')
            ->andWhere('pd.date >= :startDate')
            ->andWhere('pd.date <= :endDate')
            ->setParameter('project', $project)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('pd.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
