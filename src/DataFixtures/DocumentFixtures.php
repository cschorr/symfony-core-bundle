<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Document;
use C3net\CoreBundle\Entity\Project;
use C3net\CoreBundle\Entity\Transaction;
use C3net\CoreBundle\Enum\DocumentType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DocumentFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $documentsData = [
            // TXN-2025-0001: Complete project - multiple documents
            [
                'transaction' => 'transaction_0',
                'project' => 'project_0',
                'name' => 'Project Brief - E-Commerce Platform', // FIXED: 'name' key instead of 'title'
                'type' => DocumentType::BRIEF,
                'filePath' => '/documents/briefs/ecommerce-platform-brief.pdf',
            ],
            [
                'transaction' => 'transaction_0',
                'project' => 'project_0',
                'name' => 'Development Contract', // FIXED: 'name' key instead of 'title'
                'type' => DocumentType::CONTRACT,
                'filePath' => '/documents/contracts/contract-txn-2025-0001.pdf',
            ],
            [
                'transaction' => 'transaction_0',
                'project' => null,
                'name' => 'Offer Document OFF-2025-0001-V1', // FIXED: 'name' key instead of 'title'
                'type' => DocumentType::OFFER_PDF,
                'filePath' => '/documents/offers/offer-2025-0001-v1.pdf',
            ],
            [
                'transaction' => 'transaction_0',
                'project' => null,
                'name' => 'Invoice INV-2025-0001', // FIXED: 'name' key instead of 'title'
                'type' => DocumentType::INVOICE_PDF,
                'filePath' => '/documents/invoices/invoice-2025-0001.pdf',
            ],
            // TXN-2025-0002: Complete project
            [
                'transaction' => 'transaction_1',
                'project' => 'project_3',
                'name' => 'Mobile Banking App - Requirements', // FIXED: 'name' key instead of 'title'
                'type' => DocumentType::BRIEF,
                'filePath' => '/documents/briefs/mobile-banking-requirements.pdf',
            ],
            [
                'transaction' => 'transaction_1',
                'project' => 'project_3',
                'name' => 'Security Audit Report', // FIXED: 'name' key instead of 'title'
                'type' => DocumentType::DELIVERABLE,
                'filePath' => '/documents/deliverables/security-audit-mobile-banking.pdf',
            ],
            [
                'transaction' => 'transaction_1',
                'project' => null,
                'name' => 'Invoice INV-2025-0002', // FIXED: 'name' key instead of 'title'
                'type' => DocumentType::INVOICE_PDF,
                'filePath' => '/documents/invoices/invoice-2025-0002.pdf',
            ],
            // TXN-2025-0003: In production
            [
                'transaction' => 'transaction_2',
                'project' => 'project_6',
                'name' => 'Corporate Security - Project Brief', // FIXED: 'name' key instead of 'title'
                'type' => DocumentType::BRIEF,
                'filePath' => '/documents/briefs/corporate-security-brief.pdf',
            ],
            [
                'transaction' => 'transaction_2',
                'project' => null,
                'name' => 'Service Agreement', // FIXED: 'name' key instead of 'title'
                'type' => DocumentType::CONTRACT,
                'filePath' => '/documents/contracts/contract-txn-2025-0003.pdf',
            ],
            // TXN-2025-0004: Delivered
            [
                'transaction' => 'transaction_3',
                'project' => 'project_8',
                'name' => 'R&D Dashboard - Specifications', // FIXED: 'name' key instead of 'title'
                'type' => DocumentType::BRIEF,
                'filePath' => '/documents/briefs/rnd-dashboard-specs.pdf',
            ],
            [
                'transaction' => 'transaction_3',
                'project' => 'project_8',
                'name' => 'Dashboard User Manual', // FIXED: 'name' key instead of 'title'
                'type' => DocumentType::DELIVERABLE,
                'filePath' => '/documents/deliverables/dashboard-user-manual.pdf',
            ],
            // TXN-2025-0005: Invoiced
            [
                'transaction' => 'transaction_4',
                'project' => 'project_11',
                'name' => 'Marketing Campaign Strategy', // FIXED: 'name' key instead of 'title'
                'type' => DocumentType::BRIEF,
                'filePath' => '/documents/briefs/marketing-campaign-strategy.pdf',
            ],
        ];

        foreach ($documentsData as $index => $documentData) {
            $transaction = $this->getReference($documentData['transaction'], Transaction::class);
            $project = null;
            if (isset($documentData['project']) && $documentData['project']) {
                $project = $this->getReference($documentData['project'], Project::class);
            }

            $document = (new Document())
                ->setName($documentData['name']) // FIXED: setName() not setTitle()
                ->setDocumentType($documentData['type']) // FIXED: setDocumentType() not setType()
                ->setFilePath($documentData['filePath'])
                ->setTransaction($transaction);

            if ($project) {
                $document->setProject($project);
            }

            $manager->persist($document);
            $this->addReference('document_' . $index, $document);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            TransactionFixtures::class,
            ProjectFixtures::class,
        ];
    }
}
