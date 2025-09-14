<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Doctrine\Listener;

use C3net\CoreBundle\Domain\CommentScoreUpdater;
use C3net\CoreBundle\Entity\Vote;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsEntityListener(event: Events::postPersist, entity: Vote::class)]
#[AsEntityListener(event: Events::postUpdate, entity: Vote::class)]
#[AsEntityListener(event: Events::postRemove, entity: Vote::class)]
final readonly class VoteScoreListener
{
    public function __construct(private CommentScoreUpdater $updater)
    {
    }

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    public function postPersist(Vote $vote, LifecycleEventArgs $args): void
    {
        $this->touch($vote, $args);
    }

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    public function postUpdate(Vote $vote, LifecycleEventArgs $args): void
    {
        $this->touch($vote, $args);
    }

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    public function postRemove(Vote $vote, LifecycleEventArgs $args): void
    {
        $this->touch($vote, $args);
    }

    /**
     * @param LifecycleEventArgs<EntityManagerInterface> $args
     */
    private function touch(Vote $vote, LifecycleEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $comment = $vote->getComment();
        if (null !== $comment) {
            $this->updater->recomputeFor($comment);
            $em->flush(); // counters materialisieren
        }
    }
}
