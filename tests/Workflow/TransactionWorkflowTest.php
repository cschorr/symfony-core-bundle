<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Workflow;

use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\Transaction;
use C3net\CoreBundle\Enum\TransactionStatus;
use C3net\CoreBundle\Enum\TransactionType;
use C3net\CoreBundle\Service\TransactionWorkflowService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\Exception\LogicException;

class TransactionWorkflowTest extends KernelTestCase
{
    private TransactionWorkflowService $workflowService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->workflowService = self::getContainer()->get(TransactionWorkflowService::class);
    }

    private function createTransaction(TransactionStatus $status = TransactionStatus::DRAFT): Transaction
    {
        $transaction = new Transaction();
        $transaction->setTransactionNumber('TEST-001');
        $transaction->setName('Test Transaction');
        $transaction->setTransactionType(TransactionType::PROJECT);
        $transaction->setStatus($status);
        $transaction->setCurrency('EUR');

        // Set customer to satisfy workflow guards
        $customer = new Company();
        $customer->setName('Test Customer');
        $transaction->setCustomer($customer);

        return $transaction;
    }

    public function testInitialStatus(): void
    {
        $transaction = $this->createTransaction();

        $this->assertEquals('draft', $this->workflowService->getCurrentPlace($transaction));
        $this->assertTrue($this->workflowService->canQuote($transaction));
        $this->assertTrue($this->workflowService->canCancel($transaction));
        $this->assertFalse($this->workflowService->canOrder($transaction));
    }

    public function testQuoteTransition(): void
    {
        $transaction = $this->createTransaction();

        $this->workflowService->quote($transaction);

        $this->assertEquals(TransactionStatus::QUOTED, $transaction->getStatusEnum());
        $this->assertTrue($this->workflowService->canOrder($transaction));
        $this->assertFalse($this->workflowService->canQuote($transaction));
    }

    public function testFullWorkflow(): void
    {
        $transaction = $this->createTransaction();

        // Draft -> Quoted
        $this->workflowService->quote($transaction);
        $this->assertEquals(TransactionStatus::QUOTED, $transaction->getStatusEnum());

        // Quoted -> Ordered
        $this->workflowService->order($transaction);
        $this->assertEquals(TransactionStatus::ORDERED, $transaction->getStatusEnum());

        // Ordered -> In Production
        $this->workflowService->startProduction($transaction);
        $this->assertEquals(TransactionStatus::IN_PRODUCTION, $transaction->getStatusEnum());

        // In Production -> Delivered
        $this->workflowService->deliver($transaction);
        $this->assertEquals(TransactionStatus::DELIVERED, $transaction->getStatusEnum());

        // Delivered -> Invoiced
        $this->workflowService->invoice($transaction);
        $this->assertEquals(TransactionStatus::INVOICED, $transaction->getStatusEnum());

        // Invoiced -> Paid
        $this->workflowService->markPaid($transaction);
        $this->assertEquals(TransactionStatus::PAID, $transaction->getStatusEnum());

        // Cannot transition from paid
        $this->assertFalse($this->workflowService->can($transaction, 'quote'));
        $this->assertFalse($this->workflowService->canCancel($transaction));
    }

    public function testCancelTransition(): void
    {
        $transaction = $this->createTransaction();

        // Can cancel from draft
        $this->assertTrue($this->workflowService->canCancel($transaction));
        $this->workflowService->cancel($transaction);
        $this->assertEquals(TransactionStatus::CANCELLED, $transaction->getStatusEnum());

        // Can reopen from cancelled
        $this->assertTrue($this->workflowService->canReopen($transaction));
        $this->workflowService->reopen($transaction);
        $this->assertEquals(TransactionStatus::DRAFT, $transaction->getStatusEnum());
    }

    public function testCannotCancelAfterProduction(): void
    {
        $transaction = $this->createTransaction();

        // Move to delivered state (past in_production)
        $this->workflowService->quote($transaction);
        $this->workflowService->order($transaction);
        $this->workflowService->startProduction($transaction);
        $this->workflowService->deliver($transaction);

        // Cannot cancel after production phase
        $this->assertFalse($this->workflowService->canCancel($transaction));
    }

    public function testInvalidTransitionThrowsException(): void
    {
        $transaction = $this->createTransaction();

        $this->expectException(LogicException::class);
        $this->workflowService->order($transaction); // Cannot order without quoting first
    }

    public function testGetEnabledTransitions(): void
    {
        $transaction = $this->createTransaction();

        $transitions = $this->workflowService->getEnabledTransitions($transaction);

        $this->assertContains('quote', $transitions);
        $this->assertContains('cancel', $transitions);
        $this->assertNotContains('order', $transitions);
    }

    public function testGetAvailableTransitionsWithTargets(): void
    {
        $transaction = $this->createTransaction();

        $transitionsWithTargets = $this->workflowService->getAvailableTransitionsWithTargets($transaction);

        $this->assertArrayHasKey('quote', $transitionsWithTargets);
        $this->assertEquals('quoted', $transitionsWithTargets['quote']);
        $this->assertArrayHasKey('cancel', $transitionsWithTargets);
        $this->assertEquals('cancelled', $transitionsWithTargets['cancel']);
    }

    public function testWorkflowGuardBlocksTransitionWithoutCustomer(): void
    {
        $transaction = new Transaction();
        $transaction->setTransactionNumber('TEST-002');
        $transaction->setName('Test Transaction');
        $transaction->setTransactionType(TransactionType::PROJECT);
        $transaction->setStatus(TransactionStatus::DRAFT);
        $transaction->setCurrency('EUR');
        // Intentionally NOT setting customer

        $this->assertFalse($this->workflowService->canQuote($transaction));

        $blockers = $this->workflowService->getTransitionBlockers($transaction, 'quote');
        $this->assertGreaterThan(0, $blockers->count());
    }

    public function testStatusLabelAndBadgeClass(): void
    {
        $transaction = $this->createTransaction(TransactionStatus::PAID);

        $this->assertEquals(TransactionStatus::PAID, $transaction->getStatusEnum());
        $this->assertEquals('Paid', $this->workflowService->getStatusLabel($transaction));
        $this->assertEquals('success', $this->workflowService->getStatusBadgeClass($transaction));
    }
}
