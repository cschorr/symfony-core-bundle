<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Domain;

use C3net\CoreBundle\Entity\Comment;
use C3net\CoreBundle\Entity\Vote;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CommentScoreUpdater
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function recomputeFor(Comment $comment): void
    {
        $qb = $this->em->createQueryBuilder()
            ->select('SUM(CASE WHEN v.value = 1 THEN 1 ELSE 0 END) as upCnt,
                      SUM(CASE WHEN v.value = -1 THEN 1 ELSE 0 END) as downCnt')
            ->from(Vote::class, 'v')
            ->where('v.comment = :c')
            ->setParameter('c', $comment);

        $row = $qb->getQuery()->getSingleResult();
        $up = (int) ($row['upCnt'] ?? 0);
        $down = (int) ($row['downCnt'] ?? 0);
        $comment->setUpCount($up);
        $comment->setDownCount($down);
        $comment->setScore($up - $down);
        // kein flush hier – Aufrufer entscheidet
    }
}
