<?php

declare(strict_types=1);

namespace C3net\CoreBundle\EventListener;

use C3net\CoreBundle\Entity\Transaction;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\GuardEvent;

/**
 * Transaction Workflow Event Subscriber
 *
 * Handles workflow transition events for Transaction entities.
 * Listens to guard, entered, and completed events to enforce business rules
 * and trigger side effects when transaction status changes.
 */
class TransactionWorkflowListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.transaction_status.guard' => ['onGuard'],
            'workflow.transaction_status.entered' => ['onEntered'],
            'workflow.transaction_status.completed' => ['onCompleted'],

            // Specific transition events
            'workflow.transaction_status.guard.quote' => ['onGuardQuote'],
            'workflow.transaction_status.completed.mark_paid' => ['onCompletedMarkPaid'],
            'workflow.transaction_status.completed.cancel' => ['onCompletedCancel'],
        ];
    }

    /**
     * Guard event - validate if transition can be performed
     */
    public function onGuard(GuardEvent $event): void
    {
        /** @var Transaction $transaction */
        $transaction = $event->getSubject();

        $this->logger->info('Transaction workflow guard', [
            'transaction_id' => $transaction->getId(),
            'transition' => $event->getTransition()->getName(),
            'from' => $event->getMarking()->getPlaces(),
        ]);

        // Add custom validation logic here
        // Example: Check if user has permission to perform transition
        // if (!$this->hasPermission($transaction, $event->getTransition())) {
        //     $event->setBlocked(true, 'Insufficient permissions');
        // }
    }

    /**
     * Guard for quote transition - ensure transaction has required data
     */
    public function onGuardQuote(GuardEvent $event): void
    {
        /** @var Transaction $transaction */
        $transaction = $event->getSubject();

        // Example validation: ensure customer is set
        if (!$transaction->getCustomer()) {
            $event->setBlocked(true, 'Customer must be set before creating a quote');
        }

        // Example validation: ensure transaction has a name
        if (!$transaction->getName()) {
            $event->setBlocked(true, 'Transaction name is required');
        }
    }

    /**
     * Entered event - triggered when entering a new place
     */
    public function onEntered(EnteredEvent $event): void
    {
        /** @var Transaction $transaction */
        $transaction = $event->getSubject();

        $this->logger->info('Transaction entered new status', [
            'transaction_id' => $transaction->getId(),
            'transaction_number' => $transaction->getTransactionNumber(),
            'place' => $event->getMarking()->getPlaces(),
        ]);

        // Add business logic when entering specific states
        // Example: Send notification, update related entities, etc.
    }

    /**
     * Completed event - triggered after transition is completed
     */
    public function onCompleted(CompletedEvent $event): void
    {
        /** @var Transaction $transaction */
        $transaction = $event->getSubject();

        $this->logger->info('Transaction workflow transition completed', [
            'transaction_id' => $transaction->getId(),
            'transaction_number' => $transaction->getTransactionNumber(),
            'transition' => $event->getTransition()->getName(),
            'from' => $event->getMarking()->getPlaces(),
        ]);

        // Add post-transition logic here
    }

    /**
     * Handle mark_paid transition completion
     */
    public function onCompletedMarkPaid(CompletedEvent $event): void
    {
        /** @var Transaction $transaction */
        $transaction = $event->getSubject();

        $this->logger->info('Transaction marked as paid', [
            'transaction_id' => $transaction->getId(),
            'transaction_number' => $transaction->getTransactionNumber(),
        ]);

        // Example: Send payment confirmation email
        // Example: Update accounting system
        // Example: Trigger revenue recognition
    }

    /**
     * Handle cancel transition completion
     */
    public function onCompletedCancel(CompletedEvent $event): void
    {
        /** @var Transaction $transaction */
        $transaction = $event->getSubject();

        $this->logger->info('Transaction cancelled', [
            'transaction_id' => $transaction->getId(),
            'transaction_number' => $transaction->getTransactionNumber(),
        ]);

        // Example: Send cancellation notification
        // Example: Release reserved resources
        // Example: Update related projects/offers
    }
}
