<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Entity;

use C3net\CoreBundle\Entity\Invoice;
use C3net\CoreBundle\Entity\InvoiceItem;
use C3net\CoreBundle\Entity\Transaction;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Enum\InvoiceStatus;
use C3net\CoreBundle\Enum\InvoiceType;
use C3net\CoreBundle\Enum\PaymentStatus;
use PHPUnit\Framework\TestCase;

class InvoiceTest extends TestCase
{
    private Invoice $invoice;

    protected function setUp(): void
    {
        $this->invoice = new Invoice();
    }

    public function testConstructor(): void
    {
        $invoice = new Invoice();

        // Test that collections are initialized
        $this->assertCount(0, $invoice->getItems());

        // Test default values
        $this->assertSame(InvoiceStatus::DRAFT, $invoice->getStatus());
        $this->assertSame(InvoiceType::FULL, $invoice->getInvoiceType());
        $this->assertSame(PaymentStatus::UNPAID, $invoice->getPaymentStatus());
        $this->assertSame('0.00', $invoice->getSubtotal());
        $this->assertSame('19.00', $invoice->getTaxRate());
        $this->assertSame('0.00', $invoice->getTaxAmount());
        $this->assertSame('0.00', $invoice->getTotalAmount());
        $this->assertSame('0.00', $invoice->getPaidAmount());
        $this->assertTrue($invoice->isDraft());

        // Test that invoice date is set to today
        $this->assertInstanceOf(\DateTimeImmutable::class, $invoice->getInvoiceDate());
        $this->assertSame((new \DateTime())->format('Y-m-d'), $invoice->getInvoiceDate()->format('Y-m-d'));

        // Test inherited AbstractEntity properties
        $this->assertNotNull($invoice->getCreatedAt());
        $this->assertNotNull($invoice->getUpdatedAt());
        $this->assertTrue($invoice->isActive());
    }

    public function testExtendsAbstractEntity(): void
    {
        $this->assertInstanceOf(\C3net\CoreBundle\Entity\AbstractEntity::class, $this->invoice);
        $this->assertInstanceOf(\Stringable::class, $this->invoice);
    }

    public function testInvoiceNumberProperty(): void
    {
        $this->assertNull($this->invoice->getInvoiceNumber());

        $invoiceNumber = 'INV-2025-001';
        $this->invoice->setInvoiceNumber($invoiceNumber);

        $this->assertSame($invoiceNumber, $this->invoice->getInvoiceNumber());
    }

    public function testTransactionRelationship(): void
    {
        $this->assertNull($this->invoice->getTransaction());

        $transaction = new Transaction();
        $this->invoice->setTransaction($transaction);

        $this->assertSame($transaction, $this->invoice->getTransaction());
    }

    public function testInvoiceTypeProperty(): void
    {
        // Test default type
        $this->assertSame(InvoiceType::FULL, $this->invoice->getInvoiceType());

        // Test setting different types
        $this->invoice->setInvoiceType(InvoiceType::PARTIAL);
        $this->assertSame(InvoiceType::PARTIAL, $this->invoice->getInvoiceType());

        $this->invoice->setInvoiceType(InvoiceType::DEPOSIT);
        $this->assertSame(InvoiceType::DEPOSIT, $this->invoice->getInvoiceType());

        $this->invoice->setInvoiceType(InvoiceType::FINAL);
        $this->assertSame(InvoiceType::FINAL, $this->invoice->getInvoiceType());

        $this->invoice->setInvoiceType(InvoiceType::CREDIT_NOTE);
        $this->assertSame(InvoiceType::CREDIT_NOTE, $this->invoice->getInvoiceType());
    }

    public function testStatusProperty(): void
    {
        // Test default status
        $this->assertSame(InvoiceStatus::DRAFT, $this->invoice->getStatus());

        // Test setting different statuses
        $this->invoice->setStatus(InvoiceStatus::SENT);
        $this->assertSame(InvoiceStatus::SENT, $this->invoice->getStatus());

        $this->invoice->setStatus(InvoiceStatus::PAID);
        $this->assertSame(InvoiceStatus::PAID, $this->invoice->getStatus());

        $this->invoice->setStatus(InvoiceStatus::OVERDUE);
        $this->assertSame(InvoiceStatus::OVERDUE, $this->invoice->getStatus());

        $this->invoice->setStatus(InvoiceStatus::CANCELLED);
        $this->assertSame(InvoiceStatus::CANCELLED, $this->invoice->getStatus());
    }

    public function testStatusHelperMethods(): void
    {
        // Test draft status
        $this->invoice->setStatus(InvoiceStatus::DRAFT);
        $this->assertTrue($this->invoice->isDraft());
        $this->assertFalse($this->invoice->isSent());
        $this->assertFalse($this->invoice->isPaid());
        $this->assertFalse($this->invoice->isOverdue());
        $this->assertFalse($this->invoice->isCancelled());

        // Test sent status
        $this->invoice->setStatus(InvoiceStatus::SENT);
        $this->assertFalse($this->invoice->isDraft());
        $this->assertTrue($this->invoice->isSent());
        $this->assertFalse($this->invoice->isPaid());
        $this->assertFalse($this->invoice->isOverdue());
        $this->assertFalse($this->invoice->isCancelled());

        // Test paid status
        $this->invoice->setStatus(InvoiceStatus::PAID);
        $this->assertFalse($this->invoice->isDraft());
        $this->assertFalse($this->invoice->isSent());
        $this->assertTrue($this->invoice->isPaid());
        $this->assertFalse($this->invoice->isOverdue());
        $this->assertFalse($this->invoice->isCancelled());

        // Test overdue status
        $this->invoice->setStatus(InvoiceStatus::OVERDUE);
        $this->assertFalse($this->invoice->isDraft());
        $this->assertFalse($this->invoice->isSent());
        $this->assertFalse($this->invoice->isPaid());
        $this->assertTrue($this->invoice->isOverdue());
        $this->assertFalse($this->invoice->isCancelled());

        // Test cancelled status
        $this->invoice->setStatus(InvoiceStatus::CANCELLED);
        $this->assertFalse($this->invoice->isDraft());
        $this->assertFalse($this->invoice->isSent());
        $this->assertFalse($this->invoice->isPaid());
        $this->assertFalse($this->invoice->isOverdue());
        $this->assertTrue($this->invoice->isCancelled());
    }

    public function testPaymentStatusProperty(): void
    {
        // Test default payment status
        $this->assertSame(PaymentStatus::UNPAID, $this->invoice->getPaymentStatus());

        // Test setting different payment statuses
        $this->invoice->setPaymentStatus(PaymentStatus::PARTIAL);
        $this->assertSame(PaymentStatus::PARTIAL, $this->invoice->getPaymentStatus());

        $this->invoice->setPaymentStatus(PaymentStatus::PAID);
        $this->assertSame(PaymentStatus::PAID, $this->invoice->getPaymentStatus());

        $this->invoice->setPaymentStatus(PaymentStatus::OVERDUE);
        $this->assertSame(PaymentStatus::OVERDUE, $this->invoice->getPaymentStatus());
    }

    public function testInvoiceDateProperty(): void
    {
        // Test default invoice date (set in constructor)
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->invoice->getInvoiceDate());

        $invoiceDate = new \DateTimeImmutable('2025-01-15');
        $this->invoice->setInvoiceDate($invoiceDate);

        $this->assertSame($invoiceDate, $this->invoice->getInvoiceDate());
    }

    public function testDueDateProperty(): void
    {
        $this->assertNull($this->invoice->getDueDate());

        $dueDate = new \DateTimeImmutable('2025-02-15');
        $this->invoice->setDueDate($dueDate);

        $this->assertSame($dueDate, $this->invoice->getDueDate());
    }

    public function testFinancialProperties(): void
    {
        // Test default values
        $this->assertSame('0.00', $this->invoice->getSubtotal());
        $this->assertSame('19.00', $this->invoice->getTaxRate());
        $this->assertSame('0.00', $this->invoice->getTaxAmount());
        $this->assertSame('0.00', $this->invoice->getTotalAmount());
        $this->assertSame('0.00', $this->invoice->getPaidAmount());

        // Test setting values
        $this->invoice->setSubtotal('1000.00');
        $this->invoice->setTaxRate('21.00');
        $this->invoice->setTaxAmount('210.00');
        $this->invoice->setTotalAmount('1210.00');
        $this->invoice->setPaidAmount('500.00');

        $this->assertSame('1000.00', $this->invoice->getSubtotal());
        $this->assertSame('21.00', $this->invoice->getTaxRate());
        $this->assertSame('210.00', $this->invoice->getTaxAmount());
        $this->assertSame('1210.00', $this->invoice->getTotalAmount());
        $this->assertSame('500.00', $this->invoice->getPaidAmount());
    }

    public function testPaymentTermsProperty(): void
    {
        $this->assertNull($this->invoice->getPaymentTerms());

        $terms = 'Net 30 days';
        $this->invoice->setPaymentTerms($terms);

        $this->assertSame($terms, $this->invoice->getPaymentTerms());
    }

    public function testSentProperties(): void
    {
        $this->assertNull($this->invoice->getSentAt());
        $this->assertNull($this->invoice->getSentBy());

        $sentAt = new \DateTimeImmutable('2025-01-15 10:30:00');
        $sentBy = new User();

        $this->invoice->setSentAt($sentAt);
        $this->invoice->setSentBy($sentBy);

        $this->assertSame($sentAt, $this->invoice->getSentAt());
        $this->assertSame($sentBy, $this->invoice->getSentBy());
    }

    public function testPaymentProperties(): void
    {
        $this->assertNull($this->invoice->getPaidAt());
        $this->assertNull($this->invoice->getPaymentMethod());
        $this->assertNull($this->invoice->getPaymentReference());

        $paidAt = new \DateTimeImmutable('2025-02-01 15:00:00');
        $paymentMethod = 'Bank Transfer';
        $paymentReference = 'TXN-123456789';

        $this->invoice->setPaidAt($paidAt);
        $this->invoice->setPaymentMethod($paymentMethod);
        $this->invoice->setPaymentReference($paymentReference);

        $this->assertSame($paidAt, $this->invoice->getPaidAt());
        $this->assertSame($paymentMethod, $this->invoice->getPaymentMethod());
        $this->assertSame($paymentReference, $this->invoice->getPaymentReference());
    }

    public function testItemsRelationship(): void
    {
        $item1 = new InvoiceItem();
        $item2 = new InvoiceItem();

        // Add items
        $this->invoice->addItem($item1);
        $this->invoice->addItem($item2);

        $this->assertCount(2, $this->invoice->getItems());
        $this->assertTrue($this->invoice->getItems()->contains($item1));
        $this->assertTrue($this->invoice->getItems()->contains($item2));
        $this->assertSame($this->invoice, $item1->getInvoice());
        $this->assertSame($this->invoice, $item2->getInvoice());

        // Remove item
        $this->invoice->removeItem($item1);

        $this->assertCount(1, $this->invoice->getItems());
        $this->assertFalse($this->invoice->getItems()->contains($item1));
        $this->assertNull($item1->getInvoice());
    }

    public function testItemsNoDuplicates(): void
    {
        $item = new InvoiceItem();

        $this->invoice->addItem($item);
        $this->invoice->addItem($item); // Add same item again

        $this->assertCount(1, $this->invoice->getItems());
    }

    public function testCalculateTotalsWithNoItems(): void
    {
        $this->invoice->setTaxRate('19.00');
        $this->invoice->calculateTotals();

        $this->assertSame('0.00', $this->invoice->getSubtotal());
        $this->assertSame('0.00', $this->invoice->getTaxAmount());
        $this->assertSame('0.00', $this->invoice->getTotalAmount());
    }

    public function testCalculateTotalsWithItems(): void
    {
        // Create mock items with total prices
        $item1 = $this->createMock(InvoiceItem::class);
        $item1->method('getTotalPrice')->willReturn('100.00');

        $item2 = $this->createMock(InvoiceItem::class);
        $item2->method('getTotalPrice')->willReturn('200.00');

        $item3 = $this->createMock(InvoiceItem::class);
        $item3->method('getTotalPrice')->willReturn('50.50');

        // Add items to invoice
        $this->invoice->addItem($item1);
        $this->invoice->addItem($item2);
        $this->invoice->addItem($item3);

        // Set tax rate and calculate
        $this->invoice->setTaxRate('19.00');
        $this->invoice->calculateTotals();

        // Verify calculations
        // Subtotal: 100.00 + 200.00 + 50.50 = 350.50
        $this->assertSame('350.50', $this->invoice->getSubtotal());

        // Tax: 350.50 * 0.19 = 66.595 which bcmath truncates to 66.59 (no rounding)
        $this->assertSame('66.59', $this->invoice->getTaxAmount());

        // Total: 350.50 + 66.59 = 417.09
        $this->assertSame('417.09', $this->invoice->getTotalAmount());
    }

    public function testCalculateTotalsWithDifferentTaxRate(): void
    {
        $item = $this->createMock(InvoiceItem::class);
        $item->method('getTotalPrice')->willReturn('1000.00');

        $this->invoice->addItem($item);
        $this->invoice->setTaxRate('21.00');
        $this->invoice->calculateTotals();

        $this->assertSame('1000.00', $this->invoice->getSubtotal());
        $this->assertSame('210.00', $this->invoice->getTaxAmount());
        $this->assertSame('1210.00', $this->invoice->getTotalAmount());
    }

    public function testToStringWithInvoiceNumber(): void
    {
        $invoiceNumber = 'INV-2025-001';
        $this->invoice->setInvoiceNumber($invoiceNumber);

        $this->assertSame($invoiceNumber, (string) $this->invoice);
    }

    public function testToStringWithoutInvoiceNumber(): void
    {
        $this->assertSame('Unnamed Invoice', (string) $this->invoice);
    }

    public function testCompleteInvoiceWorkflow(): void
    {
        $invoice = new Invoice();

        // Set up complete invoice
        $invoice->setInvoiceNumber('INV-2025-001')
                ->setInvoiceType(InvoiceType::FULL)
                ->setStatus(InvoiceStatus::SENT)
                ->setPaymentStatus(PaymentStatus::UNPAID)
                ->setTaxRate('19.00')
                ->setPaymentTerms('Net 30 days');

        // Set dates
        $invoiceDate = new \DateTimeImmutable('2025-01-15');
        $dueDate = new \DateTimeImmutable('2025-02-14');
        $invoice->setInvoiceDate($invoiceDate)
                ->setDueDate($dueDate);

        // Set transaction
        $transaction = new Transaction();
        $invoice->setTransaction($transaction);

        // Set sent information
        $sentAt = new \DateTimeImmutable('2025-01-15 14:00:00');
        $sentBy = new User();
        $invoice->setSentAt($sentAt)
                ->setSentBy($sentBy);

        // Add items
        $item1 = new InvoiceItem();
        $item2 = new InvoiceItem();
        $invoice->addItem($item1)
                ->addItem($item2);

        // Verify complete setup
        $this->assertSame('INV-2025-001', $invoice->getInvoiceNumber());
        $this->assertSame(InvoiceType::FULL, $invoice->getInvoiceType());
        $this->assertTrue($invoice->isSent());
        $this->assertSame(PaymentStatus::UNPAID, $invoice->getPaymentStatus());
        $this->assertSame('19.00', $invoice->getTaxRate());
        $this->assertSame('Net 30 days', $invoice->getPaymentTerms());
        $this->assertSame($invoiceDate, $invoice->getInvoiceDate());
        $this->assertSame($dueDate, $invoice->getDueDate());
        $this->assertSame($transaction, $invoice->getTransaction());
        $this->assertSame($sentAt, $invoice->getSentAt());
        $this->assertSame($sentBy, $invoice->getSentBy());
        $this->assertCount(2, $invoice->getItems());
        $this->assertSame('INV-2025-001', (string) $invoice);
    }

    public function testInvoiceStatusTransitions(): void
    {
        // Test typical invoice lifecycle
        $this->assertTrue($this->invoice->isDraft());

        // Send invoice
        $this->invoice->setStatus(InvoiceStatus::SENT);
        $this->assertTrue($this->invoice->isSent());

        // Invoice paid
        $this->invoice->setStatus(InvoiceStatus::PAID);
        $this->assertTrue($this->invoice->isPaid());
    }

    public function testInvoiceOverdue(): void
    {
        $this->invoice->setStatus(InvoiceStatus::SENT);
        $this->assertTrue($this->invoice->isSent());

        // Invoice becomes overdue
        $this->invoice->setStatus(InvoiceStatus::OVERDUE);
        $this->assertTrue($this->invoice->isOverdue());
        $this->assertFalse($this->invoice->isSent());
        $this->assertFalse($this->invoice->isPaid());
    }

    public function testInvoiceCancellation(): void
    {
        $this->invoice->setStatus(InvoiceStatus::SENT);
        $this->assertTrue($this->invoice->isSent());

        // Cancel invoice
        $this->invoice->setStatus(InvoiceStatus::CANCELLED);
        $this->assertTrue($this->invoice->isCancelled());
        $this->assertFalse($this->invoice->isSent());
        $this->assertFalse($this->invoice->isPaid());
    }

    public function testPartialPayment(): void
    {
        $this->invoice->setTotalAmount('1000.00');
        $this->invoice->setPaidAmount('0.00');
        $this->invoice->setPaymentStatus(PaymentStatus::UNPAID);

        // Partial payment received
        $this->invoice->setPaidAmount('400.00');
        $this->invoice->setPaymentStatus(PaymentStatus::PARTIAL);

        $this->assertSame('400.00', $this->invoice->getPaidAmount());
        $this->assertSame(PaymentStatus::PARTIAL, $this->invoice->getPaymentStatus());

        // Full payment received
        $this->invoice->setPaidAmount('1000.00');
        $this->invoice->setPaymentStatus(PaymentStatus::PAID);

        $this->assertSame('1000.00', $this->invoice->getPaidAmount());
        $this->assertSame(PaymentStatus::PAID, $this->invoice->getPaymentStatus());
    }

    public function testCreditNoteType(): void
    {
        $this->invoice->setInvoiceType(InvoiceType::CREDIT_NOTE);
        $this->assertSame(InvoiceType::CREDIT_NOTE, $this->invoice->getInvoiceType());

        // Credit notes typically have negative amounts
        $this->invoice->setTotalAmount('-500.00');
        $this->assertSame('-500.00', $this->invoice->getTotalAmount());
    }

    public function testDepositAndFinalInvoice(): void
    {
        // Create deposit invoice
        $depositInvoice = new Invoice();
        $depositInvoice->setInvoiceType(InvoiceType::DEPOSIT);
        $depositInvoice->setTotalAmount('500.00');

        $this->assertSame(InvoiceType::DEPOSIT, $depositInvoice->getInvoiceType());

        // Create final invoice
        $finalInvoice = new Invoice();
        $finalInvoice->setInvoiceType(InvoiceType::FINAL);
        $finalInvoice->setTotalAmount('1500.00');

        $this->assertSame(InvoiceType::FINAL, $finalInvoice->getInvoiceType());
    }

    public function testInheritedActiveProperty(): void
    {
        // Test inherited active status from AbstractEntity
        $this->assertTrue($this->invoice->isActive());

        $this->invoice->setActive(false);
        $this->assertFalse($this->invoice->isActive());
    }

    public function testInheritedNotesProperty(): void
    {
        // Test inherited notes from AbstractEntity
        $notes = 'Internal invoice notes';
        $this->invoice->setNotes($notes);

        $this->assertSame($notes, $this->invoice->getNotes());
    }

    public function testCalculateTotalsWithPrecision(): void
    {
        // Test precision in calculations
        $item1 = $this->createMock(InvoiceItem::class);
        $item1->method('getTotalPrice')->willReturn('33.33');

        $item2 = $this->createMock(InvoiceItem::class);
        $item2->method('getTotalPrice')->willReturn('66.67');

        $this->invoice->addItem($item1);
        $this->invoice->addItem($item2);
        $this->invoice->setTaxRate('19.00');
        $this->invoice->calculateTotals();

        // Subtotal: 33.33 + 66.67 = 100.00
        $this->assertSame('100.00', $this->invoice->getSubtotal());

        // Tax: 100.00 * 0.19 = 19.00
        $this->assertSame('19.00', $this->invoice->getTaxAmount());

        // Total: 100.00 + 19.00 = 119.00
        $this->assertSame('119.00', $this->invoice->getTotalAmount());
    }
}
