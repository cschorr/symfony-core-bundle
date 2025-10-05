<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Service;

use C3net\CoreBundle\Entity\Transaction;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\TransitionBlockerList;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Transaction Workflow Service.
 *
 * Provides helper methods for working with the transaction status workflow.
 * Centralizes workflow logic for easier testing and reusability.
 */
class TransactionWorkflowService
{
    private const WORKFLOW_NAME = 'transaction_status';

    public function __construct(
        private readonly Registry $workflowRegistry,
    ) {
    }

    /**
     * Get the workflow/state machine for a transaction.
     */
    private function getWorkflow(Transaction $transaction): WorkflowInterface
    {
        return $this->workflowRegistry->get($transaction, self::WORKFLOW_NAME);
    }

    /**
     * Check if a transition can be applied.
     */
    public function can(Transaction $transaction, string $transitionName): bool
    {
        return $this->getWorkflow($transaction)->can($transaction, $transitionName);
    }

    /**
     * Apply a transition to a transaction.
     *
     * @throws \Symfony\Component\Workflow\Exception\LogicException if transition cannot be applied
     */
    public function apply(Transaction $transaction, string $transitionName): void
    {
        $this->getWorkflow($transaction)->apply($transaction, $transitionName);
    }

    /**
     * Get all available transitions for a transaction.
     *
     * @return string[]
     */
    public function getEnabledTransitions(Transaction $transaction): array
    {
        $workflow = $this->getWorkflow($transaction);
        $enabledTransitions = $workflow->getEnabledTransitions($transaction);

        return array_map(fn ($transition) => $transition->getName(), $enabledTransitions);
    }

    /**
     * Get transition blockers (reasons why a transition cannot be applied).
     */
    public function getTransitionBlockers(Transaction $transaction, string $transitionName): TransitionBlockerList
    {
        return $this->getWorkflow($transaction)->buildTransitionBlockerList($transaction, $transitionName);
    }

    /**
     * Get current marking (current place/status).
     */
    public function getCurrentPlace(Transaction $transaction): string
    {
        $marking = $this->getWorkflow($transaction)->getMarking($transaction);
        $places = array_keys($marking->getPlaces());

        $firstPlace = reset($places);

        return \is_string($firstPlace) ? $firstPlace : 'draft';
    }

    /**
     * Get all available transitions with their target status.
     *
     * @return array<string, string> Map of transition name => target status
     */
    public function getAvailableTransitionsWithTargets(Transaction $transaction): array
    {
        $workflow = $this->getWorkflow($transaction);
        $enabledTransitions = $workflow->getEnabledTransitions($transaction);

        $result = [];
        foreach ($enabledTransitions as $transition) {
            $targets = $transition->getTos();
            $firstTarget = reset($targets);
            if (\is_string($firstTarget)) {
                $result[$transition->getName()] = $firstTarget;
            }
        }

        return $result;
    }

    /**
     * Check if transaction can be quoted.
     */
    public function canQuote(Transaction $transaction): bool
    {
        return $this->can($transaction, 'quote');
    }

    /**
     * Quote transaction.
     */
    public function quote(Transaction $transaction): void
    {
        $this->apply($transaction, 'quote');
    }

    /**
     * Check if transaction can be ordered.
     */
    public function canOrder(Transaction $transaction): bool
    {
        return $this->can($transaction, 'order');
    }

    /**
     * Order transaction.
     */
    public function order(Transaction $transaction): void
    {
        $this->apply($transaction, 'order');
    }

    /**
     * Check if transaction can start production.
     */
    public function canStartProduction(Transaction $transaction): bool
    {
        return $this->can($transaction, 'start_production');
    }

    /**
     * Start production for transaction.
     */
    public function startProduction(Transaction $transaction): void
    {
        $this->apply($transaction, 'start_production');
    }

    /**
     * Check if transaction can be delivered.
     */
    public function canDeliver(Transaction $transaction): bool
    {
        return $this->can($transaction, 'deliver');
    }

    /**
     * Deliver transaction.
     */
    public function deliver(Transaction $transaction): void
    {
        $this->apply($transaction, 'deliver');
    }

    /**
     * Check if transaction can be invoiced.
     */
    public function canInvoice(Transaction $transaction): bool
    {
        return $this->can($transaction, 'invoice');
    }

    /**
     * Invoice transaction.
     */
    public function invoice(Transaction $transaction): void
    {
        $this->apply($transaction, 'invoice');
    }

    /**
     * Check if transaction can be marked as paid.
     */
    public function canMarkPaid(Transaction $transaction): bool
    {
        return $this->can($transaction, 'mark_paid');
    }

    /**
     * Mark transaction as paid.
     */
    public function markPaid(Transaction $transaction): void
    {
        $this->apply($transaction, 'mark_paid');
    }

    /**
     * Check if transaction can be cancelled.
     */
    public function canCancel(Transaction $transaction): bool
    {
        return $this->can($transaction, 'cancel');
    }

    /**
     * Cancel transaction.
     */
    public function cancel(Transaction $transaction): void
    {
        $this->apply($transaction, 'cancel');
    }

    /**
     * Check if transaction can be reopened.
     */
    public function canReopen(Transaction $transaction): bool
    {
        return $this->can($transaction, 'reopen');
    }

    /**
     * Reopen transaction.
     */
    public function reopen(Transaction $transaction): void
    {
        $this->apply($transaction, 'reopen');
    }

    /**
     * Get a human-readable status label for the current status.
     */
    public function getStatusLabel(Transaction $transaction): string
    {
        return $transaction->getStatusEnum()->getLabel();
    }

    /**
     * Get badge CSS class for current status.
     */
    public function getStatusBadgeClass(Transaction $transaction): string
    {
        return $transaction->getStatusEnum()->getBadgeClass();
    }
}
