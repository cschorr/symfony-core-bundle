<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\Contact;
use C3net\CoreBundle\Entity\Project;
use C3net\CoreBundle\Entity\Transaction;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Enum\TransactionStatus;
use C3net\CoreBundle\Enum\TransactionType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TransactionFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $transactionsData = [
            // Complete workflow: DRAFT -> QUOTED -> ORDERED -> IN_PRODUCTION -> DELIVERED -> INVOICED -> PAID
            ['number' => 'TXN-2025-0001', 'title' => 'E-Commerce Platform Development', 'description' => 'Full-stack e-commerce platform with payment gateway integration', 'status' => TransactionStatus::PAID, 'customer' => 'company_0', 'contact' => 'contact_0', 'assignedUser' => 'editor', 'category' => 'sub1', 'currency' => 'USD', 'project' => 'project_0'],
            ['number' => 'TXN-2025-0002', 'title' => 'Mobile Banking Application', 'description' => 'Secure mobile banking app with biometric authentication', 'status' => TransactionStatus::PAID, 'customer' => 'company_1', 'contact' => 'contact_6', 'assignedUser' => 'teamlead', 'category' => 'sub2', 'currency' => 'USD', 'project' => 'project_3'],
            ['number' => 'TXN-2025-0003', 'title' => 'Corporate Security Upgrade', 'description' => 'Enterprise-wide security system implementation', 'status' => TransactionStatus::IN_PRODUCTION, 'customer' => 'company_2', 'contact' => 'contact_12', 'assignedUser' => 'external', 'category' => 'main2', 'currency' => 'USD', 'project' => 'project_6'],
            ['number' => 'TXN-2025-0004', 'title' => 'R&D Dashboard System', 'description' => 'Real-time analytics and reporting dashboard', 'status' => TransactionStatus::DELIVERED, 'customer' => 'company_3', 'contact' => 'contact_18', 'assignedUser' => 'demo', 'category' => 'sub3', 'currency' => 'USD', 'project' => 'project_8'],
            ['number' => 'TXN-2025-0005', 'title' => 'Digital Marketing Campaign', 'description' => 'Comprehensive digital marketing strategy', 'status' => TransactionStatus::INVOICED, 'customer' => 'company_5', 'contact' => null, 'assignedUser' => 'manager', 'category' => 'sub6', 'currency' => 'EUR', 'project' => 'project_11'],
            ['number' => 'TXN-2025-0006', 'title' => 'Mobile Commerce App', 'description' => 'Cross-platform mobile commerce application', 'status' => TransactionStatus::ORDERED, 'customer' => 'company_8', 'contact' => null, 'assignedUser' => 'dev1', 'category' => 'sub2', 'currency' => 'USD', 'project' => null],
            ['number' => 'TXN-2025-0007', 'title' => 'Financial Analytics Platform', 'description' => 'Real-time financial data analytics and reporting', 'status' => TransactionStatus::QUOTED, 'customer' => 'company_11', 'contact' => null, 'assignedUser' => 'consultant1', 'category' => 'sub4', 'currency' => 'USD', 'project' => null],
            ['number' => 'TXN-2025-0008', 'title' => 'Legal Compliance Platform', 'description' => 'Multi-jurisdictional legal compliance management', 'status' => TransactionStatus::DRAFT, 'customer' => 'company_15', 'contact' => null, 'assignedUser' => 'external', 'category' => 'sub5', 'currency' => 'EUR', 'project' => null],
            ['number' => 'TXN-2025-0009', 'title' => 'Pharmaceutical CRM System', 'description' => 'Customer relationship management for pharmaceutical industry', 'status' => TransactionStatus::PAID, 'customer' => 'company_16', 'contact' => null, 'assignedUser' => 'marketing1', 'category' => 'sub6', 'currency' => 'EUR', 'project' => null],
            ['number' => 'TXN-2025-0010', 'title' => 'Quantum Computing Research', 'description' => 'Advanced quantum computing solutions development', 'status' => TransactionStatus::QUOTED, 'customer' => 'company_7', 'contact' => null, 'assignedUser' => 'admin', 'category' => 'sub3', 'currency' => 'EUR', 'project' => 'project_15'],
        ];

        foreach ($transactionsData as $index => $data) {
            $transaction = (new Transaction())
                ->setTransactionNumber($data['number'])
                ->setName($data['title']) // FIXED: setName() not setTitle()
                ->setDescription($data['description'])
                ->setTransactionType(TransactionType::PROJECT)
                ->setStatus($data['status'])
                ->setCustomer($this->getReference($data['customer'], Company::class))
                ->setAssignedTo($this->getReference('user_' . $data['assignedUser'], User::class)) // FIXED: setAssignedTo() not setAssignedUser()
                ->setCategory($this->getReference($data['category'], Category::class))
                ->setCurrency($data['currency']);

            if (isset($data['contact']) && $data['contact']) {
                $transaction->setPrimaryContact($this->getReference($data['contact'], Contact::class));
            }

            if (isset($data['project']) && $data['project']) {
                $transaction->addProject($this->getReference($data['project'], Project::class));
            }

            $manager->persist($transaction);
            $this->addReference('transaction_' . $index, $transaction);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
            CompanyFixtures::class,
            ContactFixtures::class,
            UserFixtures::class,
            ProjectFixtures::class,
        ];
    }
}
