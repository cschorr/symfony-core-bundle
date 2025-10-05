<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Entity;

use C3net\CoreBundle\Entity\Offer;
use C3net\CoreBundle\Entity\OfferItem;
use C3net\CoreBundle\Entity\Transaction;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Enum\OfferStatus;
use PHPUnit\Framework\TestCase;

class OfferTest extends TestCase
{
    private Offer $offer;

    protected function setUp(): void
    {
        $this->offer = new Offer();
    }

    public function testConstructor(): void
    {
        $offer = new Offer();

        // Test that collections are initialized
        $this->assertCount(0, $offer->getItems());

        // Test default values
        $this->assertSame(OfferStatus::DRAFT, $offer->getStatus());
        $this->assertSame(1, $offer->getVersion());
        $this->assertSame('0.00', $offer->getSubtotal());
        $this->assertSame('19.00', $offer->getTaxRate());
        $this->assertSame('0.00', $offer->getTaxAmount());
        $this->assertSame('0.00', $offer->getTotalAmount());
        $this->assertTrue($offer->isDraft());

        // Test inherited AbstractEntity properties
        $this->assertNotNull($offer->getCreatedAt());
        $this->assertNotNull($offer->getUpdatedAt());
        $this->assertTrue($offer->isActive());
    }

    public function testExtendsAbstractEntity(): void
    {
        $this->assertInstanceOf(\C3net\CoreBundle\Entity\AbstractEntity::class, $this->offer);
        $this->assertInstanceOf(\Stringable::class, $this->offer);
    }

    public function testOfferNumberProperty(): void
    {
        $this->assertNull($this->offer->getOfferNumber());

        $offerNumber = 'OFF-2025-001';
        $this->offer->setOfferNumber($offerNumber);

        $this->assertSame($offerNumber, $this->offer->getOfferNumber());
    }

    public function testTransactionRelationship(): void
    {
        $this->assertNull($this->offer->getTransaction());

        $transaction = new Transaction();
        $this->offer->setTransaction($transaction);

        $this->assertSame($transaction, $this->offer->getTransaction());
    }

    public function testVersionProperty(): void
    {
        // Test default version
        $this->assertSame(1, $this->offer->getVersion());

        $this->offer->setVersion(2);
        $this->assertSame(2, $this->offer->getVersion());

        $this->offer->setVersion(10);
        $this->assertSame(10, $this->offer->getVersion());
    }

    public function testStatusProperty(): void
    {
        // Test default status
        $this->assertSame(OfferStatus::DRAFT, $this->offer->getStatus());

        // Test setting different statuses
        $this->offer->setStatus(OfferStatus::SENT);
        $this->assertSame(OfferStatus::SENT, $this->offer->getStatus());

        $this->offer->setStatus(OfferStatus::ACCEPTED);
        $this->assertSame(OfferStatus::ACCEPTED, $this->offer->getStatus());

        $this->offer->setStatus(OfferStatus::REJECTED);
        $this->assertSame(OfferStatus::REJECTED, $this->offer->getStatus());

        $this->offer->setStatus(OfferStatus::EXPIRED);
        $this->assertSame(OfferStatus::EXPIRED, $this->offer->getStatus());
    }

    public function testStatusHelperMethods(): void
    {
        // Test draft status
        $this->offer->setStatus(OfferStatus::DRAFT);
        $this->assertTrue($this->offer->isDraft());
        $this->assertFalse($this->offer->isSent());
        $this->assertFalse($this->offer->isAccepted());
        $this->assertFalse($this->offer->isRejected());
        $this->assertFalse($this->offer->isExpired());

        // Test sent status
        $this->offer->setStatus(OfferStatus::SENT);
        $this->assertFalse($this->offer->isDraft());
        $this->assertTrue($this->offer->isSent());
        $this->assertFalse($this->offer->isAccepted());
        $this->assertFalse($this->offer->isRejected());
        $this->assertFalse($this->offer->isExpired());

        // Test accepted status
        $this->offer->setStatus(OfferStatus::ACCEPTED);
        $this->assertFalse($this->offer->isDraft());
        $this->assertFalse($this->offer->isSent());
        $this->assertTrue($this->offer->isAccepted());
        $this->assertFalse($this->offer->isRejected());
        $this->assertFalse($this->offer->isExpired());

        // Test rejected status
        $this->offer->setStatus(OfferStatus::REJECTED);
        $this->assertFalse($this->offer->isDraft());
        $this->assertFalse($this->offer->isSent());
        $this->assertFalse($this->offer->isAccepted());
        $this->assertTrue($this->offer->isRejected());
        $this->assertFalse($this->offer->isExpired());

        // Test expired status
        $this->offer->setStatus(OfferStatus::EXPIRED);
        $this->assertFalse($this->offer->isDraft());
        $this->assertFalse($this->offer->isSent());
        $this->assertFalse($this->offer->isAccepted());
        $this->assertFalse($this->offer->isRejected());
        $this->assertTrue($this->offer->isExpired());
    }

    public function testValidUntilProperty(): void
    {
        $this->assertNull($this->offer->getValidUntil());

        $validUntil = new \DateTimeImmutable('2025-12-31');
        $this->offer->setValidUntil($validUntil);

        $this->assertSame($validUntil, $this->offer->getValidUntil());
    }

    public function testFinancialProperties(): void
    {
        // Test default values
        $this->assertSame('0.00', $this->offer->getSubtotal());
        $this->assertSame('19.00', $this->offer->getTaxRate());
        $this->assertSame('0.00', $this->offer->getTaxAmount());
        $this->assertSame('0.00', $this->offer->getTotalAmount());

        // Test setting values
        $this->offer->setSubtotal('1000.00');
        $this->offer->setTaxRate('21.00');
        $this->offer->setTaxAmount('210.00');
        $this->offer->setTotalAmount('1210.00');

        $this->assertSame('1000.00', $this->offer->getSubtotal());
        $this->assertSame('21.00', $this->offer->getTaxRate());
        $this->assertSame('210.00', $this->offer->getTaxAmount());
        $this->assertSame('1210.00', $this->offer->getTotalAmount());
    }

    public function testTermsProperty(): void
    {
        $this->assertNull($this->offer->getTerms());

        $terms = 'Payment due within 30 days. All prices exclude shipping.';
        $this->offer->setTerms($terms);

        $this->assertSame($terms, $this->offer->getTerms());
    }

    public function testCustomerNotesProperty(): void
    {
        $this->assertNull($this->offer->getCustomerNotes());

        $notes = 'Please confirm the delivery schedule.';
        $this->offer->setCustomerNotes($notes);

        $this->assertSame($notes, $this->offer->getCustomerNotes());
    }

    public function testSentProperties(): void
    {
        $this->assertNull($this->offer->getSentAt());
        $this->assertNull($this->offer->getSentBy());

        $sentAt = new \DateTimeImmutable('2025-01-15 10:30:00');
        $sentBy = new User();

        $this->offer->setSentAt($sentAt);
        $this->offer->setSentBy($sentBy);

        $this->assertSame($sentAt, $this->offer->getSentAt());
        $this->assertSame($sentBy, $this->offer->getSentBy());
    }

    public function testAcceptedProperties(): void
    {
        $this->assertNull($this->offer->getAcceptedAt());
        $this->assertNull($this->offer->getAcceptedBy());

        $acceptedAt = new \DateTimeImmutable('2025-01-20 14:00:00');
        $acceptedBy = 'John Doe (Customer)';

        $this->offer->setAcceptedAt($acceptedAt);
        $this->offer->setAcceptedBy($acceptedBy);

        $this->assertSame($acceptedAt, $this->offer->getAcceptedAt());
        $this->assertSame($acceptedBy, $this->offer->getAcceptedBy());
    }

    public function testItemsRelationship(): void
    {
        $item1 = new OfferItem();
        $item2 = new OfferItem();

        // Add items
        $this->offer->addItem($item1);
        $this->offer->addItem($item2);

        $this->assertCount(2, $this->offer->getItems());
        $this->assertTrue($this->offer->getItems()->contains($item1));
        $this->assertTrue($this->offer->getItems()->contains($item2));
        $this->assertSame($this->offer, $item1->getOffer());
        $this->assertSame($this->offer, $item2->getOffer());

        // Remove item
        $this->offer->removeItem($item1);

        $this->assertCount(1, $this->offer->getItems());
        $this->assertFalse($this->offer->getItems()->contains($item1));
        $this->assertNull($item1->getOffer());
    }

    public function testItemsNoDuplicates(): void
    {
        $item = new OfferItem();

        $this->offer->addItem($item);
        $this->offer->addItem($item); // Add same item again

        $this->assertCount(1, $this->offer->getItems());
    }

    public function testCalculateTotalsWithNoItems(): void
    {
        $this->offer->setTaxRate('19.00');
        $this->offer->calculateTotals();

        $this->assertSame('0.00', $this->offer->getSubtotal());
        $this->assertSame('0.00', $this->offer->getTaxAmount());
        $this->assertSame('0.00', $this->offer->getTotalAmount());
    }

    public function testCalculateTotalsWithItems(): void
    {
        // Create mock items with total prices
        $item1 = $this->createMock(OfferItem::class);
        $item1->method('getTotalPrice')->willReturn('100.00');

        $item2 = $this->createMock(OfferItem::class);
        $item2->method('getTotalPrice')->willReturn('200.00');

        $item3 = $this->createMock(OfferItem::class);
        $item3->method('getTotalPrice')->willReturn('50.50');

        // Add items to offer
        $this->offer->addItem($item1);
        $this->offer->addItem($item2);
        $this->offer->addItem($item3);

        // Set tax rate and calculate
        $this->offer->setTaxRate('19.00');
        $this->offer->calculateTotals();

        // Verify calculations
        // Subtotal: 100.00 + 200.00 + 50.50 = 350.50
        $this->assertSame('350.50', $this->offer->getSubtotal());

        // Tax: 350.50 * 0.19 = 66.595 which bcmath truncates to 66.59 (no rounding)
        $this->assertSame('66.59', $this->offer->getTaxAmount());

        // Total: 350.50 + 66.59 = 417.09
        $this->assertSame('417.09', $this->offer->getTotalAmount());
    }

    public function testCalculateTotalsWithDifferentTaxRate(): void
    {
        $item = $this->createMock(OfferItem::class);
        $item->method('getTotalPrice')->willReturn('1000.00');

        $this->offer->addItem($item);
        $this->offer->setTaxRate('21.00');
        $this->offer->calculateTotals();

        $this->assertSame('1000.00', $this->offer->getSubtotal());
        $this->assertSame('210.00', $this->offer->getTaxAmount());
        $this->assertSame('1210.00', $this->offer->getTotalAmount());
    }

    public function testToStringWithOfferNumber(): void
    {
        $offerNumber = 'OFF-2025-001';
        $this->offer->setOfferNumber($offerNumber);

        $this->assertSame($offerNumber, (string) $this->offer);
    }

    public function testToStringWithoutOfferNumber(): void
    {
        $this->assertSame('Unnamed Offer', (string) $this->offer);
    }

    public function testCompleteOfferWorkflow(): void
    {
        $offer = new Offer();

        // Set up complete offer
        $offer->setOfferNumber('OFF-2025-001')
              ->setVersion(1)
              ->setStatus(OfferStatus::SENT)
              ->setTaxRate('19.00')
              ->setTerms('Payment due within 30 days')
              ->setCustomerNotes('Please review and confirm');

        // Set validity period
        $validUntil = new \DateTimeImmutable('2025-02-28');
        $offer->setValidUntil($validUntil);

        // Set transaction
        $transaction = new Transaction();
        $offer->setTransaction($transaction);

        // Set sent information
        $sentAt = new \DateTimeImmutable('2025-01-15');
        $sentBy = new User();
        $offer->setSentAt($sentAt)
              ->setSentBy($sentBy);

        // Add items
        $item1 = new OfferItem();
        $item2 = new OfferItem();
        $offer->addItem($item1)
              ->addItem($item2);

        // Verify complete setup
        $this->assertSame('OFF-2025-001', $offer->getOfferNumber());
        $this->assertSame(1, $offer->getVersion());
        $this->assertTrue($offer->isSent());
        $this->assertSame('19.00', $offer->getTaxRate());
        $this->assertSame('Payment due within 30 days', $offer->getTerms());
        $this->assertSame('Please review and confirm', $offer->getCustomerNotes());
        $this->assertSame($validUntil, $offer->getValidUntil());
        $this->assertSame($transaction, $offer->getTransaction());
        $this->assertSame($sentAt, $offer->getSentAt());
        $this->assertSame($sentBy, $offer->getSentBy());
        $this->assertCount(2, $offer->getItems());
        $this->assertSame('OFF-2025-001', (string) $offer);
    }

    public function testOfferStatusTransitions(): void
    {
        // Test typical offer lifecycle
        $this->assertTrue($this->offer->isDraft());

        // Send offer
        $this->offer->setStatus(OfferStatus::SENT);
        $this->assertTrue($this->offer->isSent());

        // Customer accepts
        $this->offer->setStatus(OfferStatus::ACCEPTED);
        $this->assertTrue($this->offer->isAccepted());
    }

    public function testOfferRejection(): void
    {
        $this->offer->setStatus(OfferStatus::SENT);
        $this->assertTrue($this->offer->isSent());

        // Customer rejects
        $this->offer->setStatus(OfferStatus::REJECTED);
        $this->assertTrue($this->offer->isRejected());
        $this->assertFalse($this->offer->isSent());
        $this->assertFalse($this->offer->isAccepted());
    }

    public function testOfferExpiration(): void
    {
        $this->offer->setStatus(OfferStatus::SENT);
        $this->assertTrue($this->offer->isSent());

        // Offer expires
        $this->offer->setStatus(OfferStatus::EXPIRED);
        $this->assertTrue($this->offer->isExpired());
        $this->assertFalse($this->offer->isSent());
        $this->assertFalse($this->offer->isAccepted());
    }

    public function testVersionIncrement(): void
    {
        $this->assertSame(1, $this->offer->getVersion());

        // Create new version
        $this->offer->setVersion(2);
        $this->assertSame(2, $this->offer->getVersion());

        // Multiple versions
        $this->offer->setVersion(5);
        $this->assertSame(5, $this->offer->getVersion());
    }

    public function testInheritedActiveProperty(): void
    {
        // Test inherited active status from AbstractEntity
        $this->assertTrue($this->offer->isActive());

        $this->offer->setActive(false);
        $this->assertFalse($this->offer->isActive());
    }

    public function testInheritedNotesProperty(): void
    {
        // Test inherited notes from AbstractEntity
        $notes = 'Internal offer notes';
        $this->offer->setNotes($notes);

        $this->assertSame($notes, $this->offer->getNotes());
    }

    public function testCalculateTotalsWithPrecision(): void
    {
        // Test precision in calculations
        $item1 = $this->createMock(OfferItem::class);
        $item1->method('getTotalPrice')->willReturn('33.33');

        $item2 = $this->createMock(OfferItem::class);
        $item2->method('getTotalPrice')->willReturn('66.67');

        $this->offer->addItem($item1);
        $this->offer->addItem($item2);
        $this->offer->setTaxRate('19.00');
        $this->offer->calculateTotals();

        // Subtotal: 33.33 + 66.67 = 100.00
        $this->assertSame('100.00', $this->offer->getSubtotal());

        // Tax: 100.00 * 0.19 = 19.00
        $this->assertSame('19.00', $this->offer->getTaxAmount());

        // Total: 100.00 + 19.00 = 119.00
        $this->assertSame('119.00', $this->offer->getTotalAmount());
    }
}
