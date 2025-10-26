<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Entity;

use C3net\CoreBundle\Entity\Campaign;
use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\Contact;
use C3net\CoreBundle\Entity\Document;
use C3net\CoreBundle\Entity\Invoice;
use C3net\CoreBundle\Entity\Offer;
use C3net\CoreBundle\Entity\Project;
use C3net\CoreBundle\Entity\Transaction;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Enum\TransactionStatus;
use C3net\CoreBundle\Enum\TransactionType;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    private Transaction $transaction;

    protected function setUp(): void
    {
        $this->transaction = new Transaction();
    }

    public function testConstructor(): void
    {
        $transaction = new Transaction();

        // Test that collections are initialized
        $this->assertCount(0, $transaction->getOffers());
        $this->assertCount(0, $transaction->getInvoices());
        $this->assertCount(0, $transaction->getCampaigns());
        $this->assertCount(0, $transaction->getProjects());
        $this->assertCount(0, $transaction->getContacts());
        $this->assertCount(0, $transaction->getDocuments());

        // Test default values
        $this->assertSame(TransactionStatus::DRAFT, $transaction->getStatusEnum());
        $this->assertSame(TransactionType::ORDER, $transaction->getTransactionType());
        $this->assertSame('EUR', $transaction->getCurrency());
        $this->assertTrue($transaction->isDraft());

        // Test inherited AbstractEntity properties
        $this->assertNotNull($transaction->getCreatedAt());
        $this->assertNotNull($transaction->getUpdatedAt());
        $this->assertTrue($transaction->isActive());
    }

    public function testExtendsAbstractEntity(): void
    {
        $this->assertInstanceOf(\C3net\CoreBundle\Entity\AbstractEntity::class, $this->transaction);
        $this->assertInstanceOf(\Stringable::class, $this->transaction);
    }

    public function testTransactionNumberProperty(): void
    {
        $this->assertNull($this->transaction->getTransactionNumber());

        $transactionNumber = 'TRX-2025-0001';
        $this->transaction->setTransactionNumber($transactionNumber);

        $this->assertSame($transactionNumber, $this->transaction->getTransactionNumber());
    }

    public function testTransactionTypeProperty(): void
    {
        // Test default type
        $this->assertSame(TransactionType::ORDER, $this->transaction->getTransactionType());

        // Test setting different types
        $this->transaction->setTransactionType(TransactionType::QUOTE);
        $this->assertSame(TransactionType::QUOTE, $this->transaction->getTransactionType());

        $this->transaction->setTransactionType(TransactionType::SERVICE);
        $this->assertSame(TransactionType::SERVICE, $this->transaction->getTransactionType());

        $this->transaction->setTransactionType(TransactionType::RETAINER);
        $this->assertSame(TransactionType::RETAINER, $this->transaction->getTransactionType());

        $this->transaction->setTransactionType(TransactionType::PROJECT);
        $this->assertSame(TransactionType::PROJECT, $this->transaction->getTransactionType());

        $this->transaction->setTransactionType(TransactionType::OTHER);
        $this->assertSame(TransactionType::OTHER, $this->transaction->getTransactionType());
    }

    public function testStatusProperty(): void
    {
        // Test default status
        $this->assertSame(TransactionStatus::DRAFT, $this->transaction->getStatusEnum());

        // Test setting different statuses
        $this->transaction->setStatus(TransactionStatus::QUOTED);
        $this->assertSame(TransactionStatus::QUOTED, $this->transaction->getStatusEnum());

        $this->transaction->setStatus(TransactionStatus::ORDERED);
        $this->assertSame(TransactionStatus::ORDERED, $this->transaction->getStatusEnum());

        $this->transaction->setStatus(TransactionStatus::IN_PRODUCTION);
        $this->assertSame(TransactionStatus::IN_PRODUCTION, $this->transaction->getStatusEnum());

        $this->transaction->setStatus(TransactionStatus::DELIVERED);
        $this->assertSame(TransactionStatus::DELIVERED, $this->transaction->getStatusEnum());

        $this->transaction->setStatus(TransactionStatus::INVOICED);
        $this->assertSame(TransactionStatus::INVOICED, $this->transaction->getStatusEnum());

        $this->transaction->setStatus(TransactionStatus::PAID);
        $this->assertSame(TransactionStatus::PAID, $this->transaction->getStatusEnum());

        $this->transaction->setStatus(TransactionStatus::CANCELLED);
        $this->assertSame(TransactionStatus::CANCELLED, $this->transaction->getStatusEnum());
    }

    public function testStatusHelperMethods(): void
    {
        // Test draft status
        $this->transaction->setStatus(TransactionStatus::DRAFT);
        $this->assertTrue($this->transaction->isDraft());
        $this->assertFalse($this->transaction->isQuoted());
        $this->assertFalse($this->transaction->isOrdered());
        $this->assertFalse($this->transaction->isInProduction());
        $this->assertFalse($this->transaction->isDelivered());
        $this->assertFalse($this->transaction->isInvoiced());
        $this->assertFalse($this->transaction->isPaid());
        $this->assertFalse($this->transaction->isCancelled());

        // Test quoted status
        $this->transaction->setStatus(TransactionStatus::QUOTED);
        $this->assertFalse($this->transaction->isDraft());
        $this->assertTrue($this->transaction->isQuoted());
        $this->assertFalse($this->transaction->isOrdered());
        $this->assertFalse($this->transaction->isInProduction());
        $this->assertFalse($this->transaction->isDelivered());
        $this->assertFalse($this->transaction->isInvoiced());
        $this->assertFalse($this->transaction->isPaid());
        $this->assertFalse($this->transaction->isCancelled());

        // Test ordered status
        $this->transaction->setStatus(TransactionStatus::ORDERED);
        $this->assertFalse($this->transaction->isDraft());
        $this->assertFalse($this->transaction->isQuoted());
        $this->assertTrue($this->transaction->isOrdered());
        $this->assertFalse($this->transaction->isInProduction());
        $this->assertFalse($this->transaction->isDelivered());
        $this->assertFalse($this->transaction->isInvoiced());
        $this->assertFalse($this->transaction->isPaid());
        $this->assertFalse($this->transaction->isCancelled());

        // Test in production status
        $this->transaction->setStatus(TransactionStatus::IN_PRODUCTION);
        $this->assertFalse($this->transaction->isDraft());
        $this->assertFalse($this->transaction->isQuoted());
        $this->assertFalse($this->transaction->isOrdered());
        $this->assertTrue($this->transaction->isInProduction());
        $this->assertFalse($this->transaction->isDelivered());
        $this->assertFalse($this->transaction->isInvoiced());
        $this->assertFalse($this->transaction->isPaid());
        $this->assertFalse($this->transaction->isCancelled());

        // Test delivered status
        $this->transaction->setStatus(TransactionStatus::DELIVERED);
        $this->assertFalse($this->transaction->isDraft());
        $this->assertFalse($this->transaction->isQuoted());
        $this->assertFalse($this->transaction->isOrdered());
        $this->assertFalse($this->transaction->isInProduction());
        $this->assertTrue($this->transaction->isDelivered());
        $this->assertFalse($this->transaction->isInvoiced());
        $this->assertFalse($this->transaction->isPaid());
        $this->assertFalse($this->transaction->isCancelled());

        // Test invoiced status
        $this->transaction->setStatus(TransactionStatus::INVOICED);
        $this->assertFalse($this->transaction->isDraft());
        $this->assertFalse($this->transaction->isQuoted());
        $this->assertFalse($this->transaction->isOrdered());
        $this->assertFalse($this->transaction->isInProduction());
        $this->assertFalse($this->transaction->isDelivered());
        $this->assertTrue($this->transaction->isInvoiced());
        $this->assertFalse($this->transaction->isPaid());
        $this->assertFalse($this->transaction->isCancelled());

        // Test paid status
        $this->transaction->setStatus(TransactionStatus::PAID);
        $this->assertFalse($this->transaction->isDraft());
        $this->assertFalse($this->transaction->isQuoted());
        $this->assertFalse($this->transaction->isOrdered());
        $this->assertFalse($this->transaction->isInProduction());
        $this->assertFalse($this->transaction->isDelivered());
        $this->assertFalse($this->transaction->isInvoiced());
        $this->assertTrue($this->transaction->isPaid());
        $this->assertFalse($this->transaction->isCancelled());

        // Test cancelled status
        $this->transaction->setStatus(TransactionStatus::CANCELLED);
        $this->assertFalse($this->transaction->isDraft());
        $this->assertFalse($this->transaction->isQuoted());
        $this->assertFalse($this->transaction->isOrdered());
        $this->assertFalse($this->transaction->isInProduction());
        $this->assertFalse($this->transaction->isDelivered());
        $this->assertFalse($this->transaction->isInvoiced());
        $this->assertFalse($this->transaction->isPaid());
        $this->assertTrue($this->transaction->isCancelled());
    }

    public function testCustomerRelationship(): void
    {
        $this->assertNull($this->transaction->getCustomer());

        $company = new Company();
        $this->transaction->setCustomer($company);

        $this->assertSame($company, $this->transaction->getCustomer());
    }

    public function testPrimaryContactRelationship(): void
    {
        $this->assertNull($this->transaction->getPrimaryContact());

        $contact = new Contact();
        $this->transaction->setPrimaryContact($contact);

        $this->assertSame($contact, $this->transaction->getPrimaryContact());
    }

    public function testTotalValueProperty(): void
    {
        $this->assertNull($this->transaction->getTotalValue());

        $totalValue = '1500.50';
        $this->transaction->setTotalValue($totalValue);

        $this->assertSame($totalValue, $this->transaction->getTotalValue());
    }

    public function testCurrencyProperty(): void
    {
        // Test default currency
        $this->assertSame('EUR', $this->transaction->getCurrency());

        $currency = 'USD';
        $this->transaction->setCurrency($currency);

        $this->assertSame($currency, $this->transaction->getCurrency());
    }

    public function testDescriptionProperty(): void
    {
        $this->assertNull($this->transaction->getDescription());

        $description = 'This is a transaction description';
        $this->transaction->setDescription($description);

        $this->assertSame($description, $this->transaction->getDescription());
    }

    public function testInternalNotesProperty(): void
    {
        $this->assertNull($this->transaction->getInternalNotes());

        $notes = 'Internal notes for the transaction';
        $this->transaction->setInternalNotes($notes);

        $this->assertSame($notes, $this->transaction->getInternalNotes());
    }

    public function testAssignedToRelationship(): void
    {
        $this->assertNull($this->transaction->getAssignedTo());

        $user = new User();
        $this->transaction->setAssignedTo($user);

        $this->assertSame($user, $this->transaction->getAssignedTo());
    }

    public function testOffersRelationship(): void
    {
        $offer1 = new Offer();
        $offer2 = new Offer();

        // Add offers
        $this->transaction->addOffer($offer1);
        $this->transaction->addOffer($offer2);

        $this->assertCount(2, $this->transaction->getOffers());
        $this->assertTrue($this->transaction->getOffers()->contains($offer1));
        $this->assertTrue($this->transaction->getOffers()->contains($offer2));
        $this->assertSame($this->transaction, $offer1->getTransaction());
        $this->assertSame($this->transaction, $offer2->getTransaction());

        // Remove offer
        $this->transaction->removeOffer($offer1);

        $this->assertCount(1, $this->transaction->getOffers());
        $this->assertFalse($this->transaction->getOffers()->contains($offer1));
        $this->assertNull($offer1->getTransaction());
    }

    public function testOffersNoDuplicates(): void
    {
        $offer = new Offer();

        $this->transaction->addOffer($offer);
        $this->transaction->addOffer($offer); // Add same offer again

        $this->assertCount(1, $this->transaction->getOffers());
    }

    public function testInvoicesRelationship(): void
    {
        $invoice1 = new Invoice();
        $invoice2 = new Invoice();

        // Add invoices
        $this->transaction->addInvoice($invoice1);
        $this->transaction->addInvoice($invoice2);

        $this->assertCount(2, $this->transaction->getInvoices());
        $this->assertTrue($this->transaction->getInvoices()->contains($invoice1));
        $this->assertTrue($this->transaction->getInvoices()->contains($invoice2));
        $this->assertSame($this->transaction, $invoice1->getTransaction());
        $this->assertSame($this->transaction, $invoice2->getTransaction());

        // Remove invoice
        $this->transaction->removeInvoice($invoice1);

        $this->assertCount(1, $this->transaction->getInvoices());
        $this->assertFalse($this->transaction->getInvoices()->contains($invoice1));
        $this->assertNull($invoice1->getTransaction());
    }

    public function testInvoicesNoDuplicates(): void
    {
        $invoice = new Invoice();

        $this->transaction->addInvoice($invoice);
        $this->transaction->addInvoice($invoice); // Add same invoice again

        $this->assertCount(1, $this->transaction->getInvoices());
    }

    public function testCampaignsRelationship(): void
    {
        $campaign1 = new Campaign();
        $campaign2 = new Campaign();

        // Add campaigns
        $this->transaction->addCampaign($campaign1);
        $this->transaction->addCampaign($campaign2);

        $this->assertCount(2, $this->transaction->getCampaigns());
        $this->assertTrue($this->transaction->getCampaigns()->contains($campaign1));
        $this->assertTrue($this->transaction->getCampaigns()->contains($campaign2));
        $this->assertSame($this->transaction, $campaign1->getTransaction());
        $this->assertSame($this->transaction, $campaign2->getTransaction());

        // Remove campaign
        $this->transaction->removeCampaign($campaign1);

        $this->assertCount(1, $this->transaction->getCampaigns());
        $this->assertFalse($this->transaction->getCampaigns()->contains($campaign1));
        $this->assertNull($campaign1->getTransaction());
    }

    public function testProjectsRelationship(): void
    {
        $project1 = new Project();
        $project2 = new Project();

        // Add projects
        $this->transaction->addProject($project1);
        $this->transaction->addProject($project2);

        $this->assertCount(2, $this->transaction->getProjects());
        $this->assertTrue($this->transaction->getProjects()->contains($project1));
        $this->assertTrue($this->transaction->getProjects()->contains($project2));
        $this->assertSame($this->transaction, $project1->getTransaction());
        $this->assertSame($this->transaction, $project2->getTransaction());

        // Remove project
        $this->transaction->removeProject($project1);

        $this->assertCount(1, $this->transaction->getProjects());
        $this->assertFalse($this->transaction->getProjects()->contains($project1));
        $this->assertNull($project1->getTransaction());
    }

    public function testContactsRelationship(): void
    {
        $contact1 = new Contact();
        $contact2 = new Contact();

        // Add contacts
        $this->transaction->addContact($contact1);
        $this->transaction->addContact($contact2);

        $this->assertCount(2, $this->transaction->getContacts());
        $this->assertTrue($this->transaction->getContacts()->contains($contact1));
        $this->assertTrue($this->transaction->getContacts()->contains($contact2));

        // Remove contact
        $this->transaction->removeContact($contact1);

        $this->assertCount(1, $this->transaction->getContacts());
        $this->assertFalse($this->transaction->getContacts()->contains($contact1));
    }

    public function testContactsNoDuplicates(): void
    {
        $contact = new Contact();

        $this->transaction->addContact($contact);
        $this->transaction->addContact($contact); // Add same contact again

        $this->assertCount(1, $this->transaction->getContacts());
    }

    public function testDocumentsRelationship(): void
    {
        $document1 = new Document();
        $document2 = new Document();

        // Add documents
        $this->transaction->addDocument($document1);
        $this->transaction->addDocument($document2);

        $this->assertCount(2, $this->transaction->getDocuments());
        $this->assertTrue($this->transaction->getDocuments()->contains($document1));
        $this->assertTrue($this->transaction->getDocuments()->contains($document2));
        $this->assertSame($this->transaction, $document1->getTransaction());
        $this->assertSame($this->transaction, $document2->getTransaction());

        // Remove document
        $this->transaction->removeDocument($document1);

        $this->assertCount(1, $this->transaction->getDocuments());
        $this->assertFalse($this->transaction->getDocuments()->contains($document1));
        $this->assertNull($document1->getTransaction());
    }

    public function testToStringWithTransactionNumber(): void
    {
        $transactionNumber = 'TRX-2025-0001';
        $this->transaction->setTransactionNumber($transactionNumber);

        $this->assertSame($transactionNumber, (string) $this->transaction);
    }

    public function testToStringWithName(): void
    {
        $name = 'Test Transaction';
        $this->transaction->setName($name);

        $this->assertSame('Test Transaction', (string) $this->transaction);
    }

    public function testToStringWithTransactionNumberAndName(): void
    {
        $transactionNumber = 'TRX-2025-0001';
        $name = 'Test Transaction';

        $this->transaction->setTransactionNumber($transactionNumber);
        $this->transaction->setName($name);

        // Transaction number takes precedence
        $this->assertSame($transactionNumber, (string) $this->transaction);
    }

    public function testToStringWithoutTransactionNumberOrName(): void
    {
        // The name trait initializes name to an empty string by default
        // The __toString method does null coalesce, but empty string is not null
        // So it will return the empty string, not 'Unnamed Transaction'
        $this->assertSame('', (string) $this->transaction);
    }

    public function testCompleteTransactionWorkflow(): void
    {
        $transaction = new Transaction();

        // Set up complete transaction
        $transaction->setTransactionNumber('TRX-2025-0001')
                    ->setName('Complete Test Transaction')
                    ->setDescription('A comprehensive test transaction')
                    ->setStatus(TransactionStatus::IN_PRODUCTION)
                    ->setTransactionType(TransactionType::PROJECT)
                    ->setTotalValue('25000.00')
                    ->setCurrency('USD')
                    ->setInternalNotes('Important internal notes');

        // Set dates (inherited from traits)
        $startDate = new \DateTimeImmutable('2025-01-01');
        $endDate = new \DateTimeImmutable('2025-12-31');

        $transaction->setStartedAt($startDate)
                    ->setEndedAt($endDate);

        // Set relationships
        $customer = new Company();
        $primaryContact = new Contact();
        $assignedTo = new User();

        $transaction->setCustomer($customer)
                    ->setPrimaryContact($primaryContact)
                    ->setAssignedTo($assignedTo);

        // Add related entities
        $offer = new Offer();
        $invoice = new Invoice();
        $campaign = new Campaign();
        $project = new Project();
        $contact = new Contact();
        $document = new Document();

        $transaction->addOffer($offer)
                    ->addInvoice($invoice)
                    ->addCampaign($campaign)
                    ->addProject($project)
                    ->addContact($contact)
                    ->addDocument($document);

        // Verify complete setup
        $this->assertSame('TRX-2025-0001', $transaction->getTransactionNumber());
        $this->assertSame('Complete Test Transaction', $transaction->getName());
        $this->assertSame('A comprehensive test transaction', $transaction->getDescription());
        $this->assertTrue($transaction->isInProduction());
        $this->assertSame(TransactionType::PROJECT, $transaction->getTransactionType());
        $this->assertSame('25000.00', $transaction->getTotalValue());
        $this->assertSame('USD', $transaction->getCurrency());
        $this->assertSame('Important internal notes', $transaction->getInternalNotes());
        $this->assertSame($startDate, $transaction->getStartedAt());
        $this->assertSame($endDate, $transaction->getEndedAt());
        $this->assertSame($customer, $transaction->getCustomer());
        $this->assertSame($primaryContact, $transaction->getPrimaryContact());
        $this->assertSame($assignedTo, $transaction->getAssignedTo());
        $this->assertCount(1, $transaction->getOffers());
        $this->assertCount(1, $transaction->getInvoices());
        $this->assertCount(1, $transaction->getCampaigns());
        $this->assertCount(1, $transaction->getProjects());
        $this->assertCount(1, $transaction->getContacts());
        $this->assertCount(1, $transaction->getDocuments());
        $this->assertSame('TRX-2025-0001', (string) $transaction);
    }

    public function testTransactionStatusTransitions(): void
    {
        // Test typical transaction lifecycle
        $this->assertTrue($this->transaction->isDraft());

        // Quote phase
        $this->transaction->setStatus(TransactionStatus::QUOTED);
        $this->assertTrue($this->transaction->isQuoted());

        // Order phase
        $this->transaction->setStatus(TransactionStatus::ORDERED);
        $this->assertTrue($this->transaction->isOrdered());

        // Production phase
        $this->transaction->setStatus(TransactionStatus::IN_PRODUCTION);
        $this->assertTrue($this->transaction->isInProduction());

        // Delivery phase
        $this->transaction->setStatus(TransactionStatus::DELIVERED);
        $this->assertTrue($this->transaction->isDelivered());

        // Invoicing phase
        $this->transaction->setStatus(TransactionStatus::INVOICED);
        $this->assertTrue($this->transaction->isInvoiced());

        // Payment phase
        $this->transaction->setStatus(TransactionStatus::PAID);
        $this->assertTrue($this->transaction->isPaid());
    }

    public function testTransactionCancellation(): void
    {
        $this->transaction->setStatus(TransactionStatus::IN_PRODUCTION);
        $this->assertTrue($this->transaction->isInProduction());

        // Cancel transaction
        $this->transaction->setStatus(TransactionStatus::CANCELLED);
        $this->assertTrue($this->transaction->isCancelled());
        $this->assertFalse($this->transaction->isInProduction());
        $this->assertFalse($this->transaction->isPaid());
    }

    public function testInheritedTraits(): void
    {
        // Test StringNameTrait
        $name = 'Test Transaction Name';
        $this->transaction->setName($name);
        $this->assertSame($name, $this->transaction->getName());

        // Test StringShortcodeTrait
        $shortcode = 'TRX001';
        $this->transaction->setShortcode($shortcode);
        $this->assertSame($shortcode, $this->transaction->getShortcode());

        // Test SetStartEndTrait
        $startDate = new \DateTimeImmutable('2025-01-01');
        $endDate = new \DateTimeImmutable('2025-12-31');
        $this->transaction->setStartedAt($startDate);
        $this->transaction->setEndedAt($endDate);
        $this->assertSame($startDate, $this->transaction->getStartedAt());
        $this->assertSame($endDate, $this->transaction->getEndedAt());

        // Test inherited active status from AbstractEntity
        $this->assertTrue($this->transaction->isActive());
        $this->transaction->setActive(false);
        $this->assertFalse($this->transaction->isActive());

        // Test inherited notes from AbstractEntity
        $notes = 'General transaction notes';
        $this->transaction->setNotes($notes);
        $this->assertSame($notes, $this->transaction->getNotes());
    }
}
