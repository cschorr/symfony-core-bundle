<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Document;
use C3net\CoreBundle\Entity\Project;
use C3net\CoreBundle\Entity\Transaction;
use C3net\CoreBundle\Enum\DocumentType;
use C3net\CoreBundle\Enum\DomainEntityType;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DocumentFixtures extends AbstractCategorizableFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $documentsData = [
            // TXN-2025-0001: Complete project - multiple documents
            [
                'transaction' => 'TXN-2025-0001',
                'project' => 'E-Commerce Platform',
                'name' => 'Project Brief - E-Commerce Platform',
                'type' => DocumentType::BRIEF,
                'filePath' => '/documents/briefs/ecommerce-platform-brief.pdf',
            ],
            [
                'transaction' => 'TXN-2025-0001',
                'project' => 'E-Commerce Platform',
                'name' => 'Development Contract',
                'type' => DocumentType::CONTRACT,
                'filePath' => '/documents/contracts/contract-txn-2025-0001.pdf',
            ],
            [
                'transaction' => 'TXN-2025-0001',
                'project' => null,
                'name' => 'Offer Document OFF-2025-0001-V1',
                'type' => DocumentType::OFFER,
                'filePath' => '/documents/offers/offer-2025-0001-v1.pdf',
            ],
            [
                'transaction' => 'TXN-2025-0001',
                'project' => null,
                'name' => 'Invoice INV-2025-0001',
                'type' => DocumentType::INVOICE,
                'filePath' => '/documents/invoices/invoice-2025-0001.pdf',
            ],
            // TXN-2025-0002: Complete project
            [
                'transaction' => 'TXN-2025-0002',
                'project' => 'Mobile Banking App',
                'name' => 'Mobile Banking App - Requirements',
                'type' => DocumentType::BRIEF,
                'filePath' => '/documents/briefs/mobile-banking-requirements.pdf',
            ],
            [
                'transaction' => 'TXN-2025-0002',
                'project' => 'Mobile Banking App',
                'name' => 'Security Audit Report',
                'type' => DocumentType::DELIVERABLE,
                'filePath' => '/documents/deliverables/security-audit-mobile-banking.pdf',
            ],
            [
                'transaction' => 'TXN-2025-0002',
                'project' => null,
                'name' => 'Invoice INV-2025-0002',
                'type' => DocumentType::INVOICE,
                'filePath' => '/documents/invoices/invoice-2025-0002.pdf',
            ],
            // TXN-2025-0003: In production
            [
                'transaction' => 'TXN-2025-0003',
                'project' => 'Corporate Security Upgrade',
                'name' => 'Corporate Security - Project Brief',
                'type' => DocumentType::BRIEF,
                'filePath' => '/documents/briefs/corporate-security-brief.pdf',
            ],
            [
                'transaction' => 'TXN-2025-0003',
                'project' => null,
                'name' => 'Service Agreement',
                'type' => DocumentType::CONTRACT,
                'filePath' => '/documents/contracts/contract-txn-2025-0003.pdf',
            ],
            // TXN-2025-0004: Delivered
            [
                'transaction' => 'TXN-2025-0004',
                'project' => 'R&D Dashboard',
                'name' => 'R&D Dashboard - Specifications',
                'type' => DocumentType::BRIEF,
                'filePath' => '/documents/briefs/rnd-dashboard-specs.pdf',
            ],
            [
                'transaction' => 'TXN-2025-0004',
                'project' => 'R&D Dashboard',
                'name' => 'Dashboard User Manual',
                'type' => DocumentType::DELIVERABLE,
                'filePath' => '/documents/deliverables/dashboard-user-manual.pdf',
            ],
            // TXN-2025-0005: Invoiced
            [
                'transaction' => 'TXN-2025-0005',
                'project' => 'Digital Marketing Campaign',
                'name' => 'Marketing Campaign Strategy',
                'type' => DocumentType::BRIEF,
                'filePath' => '/documents/briefs/marketing-campaign-strategy.pdf',
            ],
        ];

        foreach ($documentsData as $documentData) {
            $transaction = $manager->getRepository(Transaction::class)->findOneBy(['transactionNumber' => $documentData['transaction']]);
            $project = null;
            // @phpstan-ignore-next-line booleanAnd.rightAlwaysTrue (Defensive check for fixture data integrity)
            if (isset($documentData['project']) && $documentData['project']) {
                $project = $manager->getRepository(Project::class)->findOneBy(['name' => $documentData['project']]);
            }

            // Assign categories based on document type
            $categoryNames = match ($documentData['type']) {
                DocumentType::BRIEF => ['Management Consulting', 'Strategy Consulting'],
                DocumentType::CONTRACT => ['Legal Services', 'Business Services'],
                DocumentType::OFFER => ['Marketing & Sales', 'Business Services'],
                DocumentType::INVOICE => ['Financial Services', 'Business Services'],
                // @phpstan-ignore-next-line match.alwaysTrue (All cases are intentional for fixture completeness)
                DocumentType::DELIVERABLE => ['Software Solutions', 'Technology'],
                default => ['Business Services'],
            };
            $categories = $this->findCategoriesByNames($manager, $categoryNames);

            $fileName = basename($documentData['filePath']);

            $document = (new Document())
                ->setName($documentData['name'])
                ->setDocumentType($documentData['type'])
                ->setFileName($fileName)
                ->setFilePath($documentData['filePath'])
                ->setTransaction($transaction);

            if (null !== $project) {
                $document->setProject($project);
            }

            // Persist and flush to get ID
            $this->persistAndFlush($manager, $document);

            // Assign multiple categories
            $this->assignCategories($manager, $document, $categories, DomainEntityType::Document);
        }

        $this->flushSafely($manager);
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
            TransactionFixtures::class,
            ProjectFixtures::class,
        ];
    }
}
