<?php

namespace App\Doctrine\Listener;

use App\Domain\CommentScoreUpdater;
use App\Entity\Vote;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsEntityListener(event: Events::postPersist, entity: Vote::class)]
#[AsEntityListener(event: Events::postUpdate, entity: Vote::class)]
#[AsEntityListener(event: Events::postRemove, entity: Vote::class)]
final class VoteScoreListener
{
    public function __construct(private CommentScoreUpdater $updater)
    {
    }

    public function postPersist(Vote $vote, LifecycleEventArgs $args): void
    {
        $this->touch($vote, $args);
    }

    public function postUpdate(Vote $vote, LifecycleEventArgs $args): void
    {
        $this->touch($vote, $args);
    }

    public function postRemove(Vote $vote, LifecycleEventArgs $args): void
    {
        $this->touch($vote, $args);
    }

    private function touch(Vote $vote, LifecycleEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $comment = $vote->getComment();
        $this->updater->recomputeFor($comment);
        $em->flush(); // counters materialisieren
    }
}
