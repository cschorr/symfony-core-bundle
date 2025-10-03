<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Repository;

use C3net\CoreBundle\Entity\Offer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Offer>
 */
class OfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offer::class);
    }
}
