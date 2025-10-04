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
            ['number' => 'TXN-2025-0001', 'title' => 'E-Commerce Platform Development', 'description' => 'Full-stack e-commerce platform with payment gateway integration', 'status' => TransactionStatus::PAID, 'customer' => 'Cyberdyne Systems', 'contact' => 'john.doe@cyberdyne.example', 'assignedUser' => 'editor@example.com', 'category' => 'Web Development', 'currency' => 'USD', 'project' => 'E-Commerce Platform'],
            ['number' => 'TXN-2025-0002', 'title' => 'Mobile Banking Application', 'description' => 'Secure mobile banking app with biometric authentication', 'status' => TransactionStatus::PAID, 'customer' => 'Stark Industries', 'contact' => 'jane.smith@stark.example', 'assignedUser' => 'teamlead@example.com', 'category' => 'Mobile Development', 'currency' => 'USD', 'project' => 'Mobile Banking App'],
            ['number' => 'TXN-2025-0003', 'title' => 'Corporate Security Upgrade', 'description' => 'Enterprise-wide security system implementation', 'status' => TransactionStatus::IN_PRODUCTION, 'customer' => 'Wayne Enterprises', 'contact' => 'alice.johnson@wayne.example', 'assignedUser' => 'external@example.com', 'category' => 'Business Services', 'currency' => 'USD', 'project' => 'Corporate Security Upgrade'],
            ['number' => 'TXN-2025-0004', 'title' => 'R&D Dashboard System', 'description' => 'Real-time analytics and reporting dashboard', 'status' => TransactionStatus::DELIVERED, 'customer' => 'Oscorp', 'contact' => 'emma.martinez@oscorp.example', 'assignedUser' => 'demo@example.com', 'category' => 'Software Solutions', 'currency' => 'USD', 'project' => 'R&D Dashboard'],
            ['number' => 'TXN-2025-0005', 'title' => 'Digital Marketing Campaign', 'description' => 'Comprehensive digital marketing strategy', 'status' => TransactionStatus::INVOICED, 'customer' => 'Umbrella Corporation', 'contact' => null, 'assignedUser' => 'marketing@example.com', 'category' => 'Digital Marketing', 'currency' => 'EUR', 'project' => 'Digital Marketing Campaign'],
            ['number' => 'TXN-2025-0006', 'title' => 'Mobile Commerce App', 'description' => 'Cross-platform mobile commerce application', 'status' => TransactionStatus::ORDERED, 'customer' => 'Parker Industries', 'contact' => null, 'assignedUser' => 'dev1@example.com', 'category' => 'Mobile Development', 'currency' => 'USD', 'project' => null],
            ['number' => 'TXN-2025-0007', 'title' => 'Financial Analytics Platform', 'description' => 'Real-time financial data analytics and reporting', 'status' => TransactionStatus::QUOTED, 'customer' => 'Rand Corporation', 'contact' => null, 'assignedUser' => 'consultant1@example.com', 'category' => 'Financial Services', 'currency' => 'USD', 'project' => null],
            ['number' => 'TXN-2025-0008', 'title' => 'Legal Compliance Platform', 'description' => 'Multi-jurisdictional legal compliance management', 'status' => TransactionStatus::DRAFT, 'customer' => 'Seegson Corporation', 'contact' => null, 'assignedUser' => 'external@example.com', 'category' => 'Legal Services', 'currency' => 'EUR', 'project' => null],
            ['number' => 'TXN-2025-0009', 'title' => 'Pharmaceutical CRM System', 'description' => 'Customer relationship management for pharmaceutical industry', 'status' => TransactionStatus::PAID, 'customer' => 'Tricell Pharmaceuticals', 'contact' => null, 'assignedUser' => 'marketing1@example.com', 'category' => 'Digital Marketing', 'currency' => 'EUR', 'project' => null],
            ['number' => 'TXN-2025-0010', 'title' => 'Quantum Computing Research', 'description' => 'Advanced quantum computing solutions development', 'status' => TransactionStatus::QUOTED, 'customer' => 'NeuralLink Systems', 'contact' => null, 'assignedUser' => 'admin@example.com', 'category' => 'Software Solutions', 'currency' => 'EUR', 'project' => 'Quantum Computing Research'],
        ];

        foreach ($transactionsData as $index => $data) {
            $transaction = (new Transaction())
                ->setTransactionNumber($data['number'])
                ->setName($data['title'])
                ->setDescription($data['description'])
                ->setTransactionType(TransactionType::PROJECT)
                ->setStatus($data['status'])
                ->setCustomer($manager->getRepository(Company::class)->findOneBy(['name' => $data['customer']]))
                ->setAssignedTo($manager->getRepository(User::class)->findOneBy(['email' => $data['assignedUser']]))
                ->setCategory($manager->getRepository(Category::class)->findOneBy(['name' => $data['category']]))
                ->setCurrency($data['currency']);

            if (isset($data['contact']) && $data['contact']) {
                $contact = $manager->getRepository(Contact::class)->findOneBy(['email' => $data['contact']]);
                if ($contact) {
                    $transaction->setPrimaryContact($contact);
                }
            }

            if (isset($data['project']) && $data['project']) {
                $project = $manager->getRepository(Project::class)->findOneBy(['name' => $data['project']]);
                if ($project) {
                    $transaction->addProject($project);
                }
            }

            $manager->persist($transaction);
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
