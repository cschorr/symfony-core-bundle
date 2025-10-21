<?php

declare(strict_types=1);

namespace C3net\CoreBundle\EventListener;

use C3net\CoreBundle\Entity\Transaction;
use C3net\CoreBundle\Service\TransactionNumberGenerator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

/**
 * Automatically generate transaction numbers for new transactions.
 */
#[AsEntityListener(event: Events::prePersist, method: 'prePersist', entity: Transaction::class)]
class TransactionNumberListener
{
    public function __construct(
        private readonly TransactionNumberGenerator $numberGenerator,
    ) {
    }

    /**
     * Generate transaction number before persisting if not already set.
     *
     * @param LifecycleEventArgs<EntityManagerInterface> $event
     */
    public function prePersist(Transaction $transaction, LifecycleEventArgs $event): void
    {
        if (null === $transaction->getTransactionNumber()) {
            $transaction->setTransactionNumber($this->numberGenerator->generate());
        }
    }
}
