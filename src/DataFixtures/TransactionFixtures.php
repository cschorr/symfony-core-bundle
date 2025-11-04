<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\Contact;
use C3net\CoreBundle\Entity\Project;
use C3net\CoreBundle\Entity\Transaction;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Enum\DomainEntityType;
use C3net\CoreBundle\Enum\TransactionStatus;
use C3net\CoreBundle\Enum\TransactionType;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TransactionFixtures extends AbstractCategorizableFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $transactionsData = [
            // Complete workflow: DRAFT -> QUOTED -> ORDERED -> IN_PRODUCTION -> DELIVERED -> INVOICED -> PAID
            ['number' => 'TXN-2025-0001', 'title' => 'E-Commerce Platform Development', 'description' => 'Full-stack e-commerce platform with payment gateway integration', 'status' => TransactionStatus::PAID, 'customer' => 'Cyberdyne Systems', 'contact' => 'john.doe@cyberdyne.example', 'assignedUser' => 'editor@example.com', 'categories' => ['Web Development', 'Technology', 'Software Solutions'], 'currency' => 'USD', 'project' => 'E-Commerce Platform'],
            ['number' => 'TXN-2025-0002', 'title' => 'Mobile Banking Application', 'description' => 'Secure mobile banking app with biometric authentication', 'status' => TransactionStatus::PAID, 'customer' => 'Stark Industries', 'contact' => 'jane.smith@stark.example', 'assignedUser' => 'teamlead@example.com', 'categories' => ['Mobile Development', 'Technology', 'Financial Services'], 'currency' => 'USD', 'project' => 'Mobile Banking App'],
            ['number' => 'TXN-2025-0003', 'title' => 'Corporate Security Upgrade', 'description' => 'Enterprise-wide security system implementation', 'status' => TransactionStatus::IN_PRODUCTION, 'customer' => 'Wayne Enterprises', 'contact' => 'alice.johnson@wayne.example', 'assignedUser' => 'external@example.com', 'categories' => ['Business Services', 'Technology', 'Cybersecurity'], 'currency' => 'USD', 'project' => 'Corporate Security Upgrade'],
            ['number' => 'TXN-2025-0004', 'title' => 'R&D Dashboard System', 'description' => 'Real-time analytics and reporting dashboard', 'status' => TransactionStatus::DELIVERED, 'customer' => 'Oscorp', 'contact' => 'emma.martinez@oscorp.example', 'assignedUser' => 'demo@example.com', 'categories' => ['Software Solutions', 'Technology', 'Business Services'], 'currency' => 'USD', 'project' => 'R&D Dashboard'],
            ['number' => 'TXN-2025-0005', 'title' => 'Digital Marketing Campaign', 'description' => 'Comprehensive digital marketing strategy', 'status' => TransactionStatus::INVOICED, 'customer' => 'Umbrella Corporation', 'contact' => null, 'assignedUser' => 'marketing@example.com', 'categories' => ['Digital Marketing', 'Marketing & Sales', 'Content Creation'], 'currency' => 'EUR', 'project' => 'Digital Marketing Campaign'],
            ['number' => 'TXN-2025-0006', 'title' => 'Audio Branding Services', 'description' => 'Complete audio branding package including jingles, sonic logo, and hold music', 'status' => TransactionStatus::ORDERED, 'customer' => 'Wayne Enterprises', 'contact' => 'alice.johnson@wayne.example', 'assignedUser' => 'external@example.com', 'categories' => ['Content Creation', 'Marketing & Sales', 'Digital Marketing'], 'currency' => 'USD', 'project' => 'Audio Branding Package - Wayne Enterprises'],
            ['number' => 'TXN-2025-0007', 'title' => 'Corporate Video Production', 'description' => 'Corporate overview video showcasing AI technology innovations', 'status' => TransactionStatus::QUOTED, 'customer' => 'Cyberdyne Systems', 'contact' => 'john.doe@cyberdyne.example', 'assignedUser' => 'editor@example.com', 'categories' => ['Content Creation', 'Digital Marketing', 'Technology'], 'currency' => 'USD', 'project' => 'Corporate Video - Cyberdyne Systems'],
            ['number' => 'TXN-2025-0008', 'title' => 'Training Video Series Production', 'description' => 'Multi-part employee training video series on security protocols', 'status' => TransactionStatus::DRAFT, 'customer' => 'Wayne Enterprises', 'contact' => 'bruce.wayne@wayne.example', 'assignedUser' => 'external@example.com', 'categories' => ['Content Creation', 'Business Services', 'Technology'], 'currency' => 'EUR', 'project' => 'Training Video Series - Wayne Enterprises'],
            ['number' => 'TXN-2025-0009', 'title' => 'Podcast Production Series', 'description' => 'Multi-episode podcast series on innovation and technology', 'status' => TransactionStatus::PAID, 'customer' => 'Stark Industries', 'contact' => 'tony.stark@stark.example', 'assignedUser' => 'teamlead@example.com', 'categories' => ['Digital Marketing', 'Content Creation', 'Technology'], 'currency' => 'EUR', 'project' => 'Podcast Series - Stark Industries'],
            ['number' => 'TXN-2025-0010', 'title' => 'Quantum Computing Research', 'description' => 'Advanced quantum computing solutions development', 'status' => TransactionStatus::QUOTED, 'customer' => 'NeuralLink Systems', 'contact' => null, 'assignedUser' => 'admin@example.com', 'categories' => ['Software Solutions', 'Technology', 'AI & Machine Learning'], 'currency' => 'EUR', 'project' => 'Quantum Computing Research'],
            ['number' => 'TXN-2025-0011', 'title' => 'Promotional Video Production - Weyland-Yutani', 'description' => 'High-budget promotional video for space exploration division with heavy VFX', 'status' => TransactionStatus::DRAFT, 'customer' => 'Weyland-Yutani', 'contact' => null, 'assignedUser' => 'admin@example.com', 'categories' => ['Content Creation', 'Digital Marketing', 'Media & Production'], 'currency' => 'USD', 'project' => 'Promotional Video - Weyland-Yutani'],
            ['number' => 'TXN-2025-0012', 'title' => 'Testimonial Compilation Services', 'description' => 'Customer testimonial video featuring case studies and interviews', 'status' => TransactionStatus::QUOTED, 'customer' => 'GeneDyne Technologies', 'contact' => null, 'assignedUser' => 'teamlead@example.com', 'categories' => ['Content Creation', 'Marketing & Sales', 'Business Services'], 'currency' => 'USD', 'project' => 'Testimonial Compilation - GeneDyne Technologies'],
            ['number' => 'TXN-2025-0013', 'title' => 'Music Production Services', 'description' => 'Original music composition for promotional video', 'status' => TransactionStatus::DRAFT, 'customer' => 'Umbrella Corporation', 'contact' => null, 'assignedUser' => 'marketing@example.com', 'categories' => ['Content Creation', 'Digital Marketing', 'Media & Production'], 'currency' => 'EUR', 'project' => 'Music Production - Umbrella Corporation'],
        ];

        foreach ($transactionsData as $data) {
            $categories = $this->findCategoriesByNames($manager, $data['categories']);

            $transaction = (new Transaction())
                ->setTransactionNumber($data['number'])
                ->setName($data['title'])
                ->setDescription($data['description'])
                ->setTransactionType(TransactionType::PROJECT)
                ->setStatus($data['status'])
                ->setCustomer($manager->getRepository(Company::class)->findOneBy(['name' => $data['customer']]))
                ->setAssignedTo($manager->getRepository(User::class)->findOneBy(['email' => $data['assignedUser']]))
                ->setCurrency($data['currency']);

            // @phpstan-ignore-next-line notIdentical.alwaysTrue (Defensive check for fixture data integrity)
            if (isset($data['contact']) && null !== $data['contact']) {
                $contact = $manager->getRepository(Contact::class)->findOneBy(['email' => $data['contact']]);
                if ($contact !== null) {
                    $transaction->setCustomerContact($contact);
                }
            }

            // @phpstan-ignore-next-line isset.offset, booleanAnd.alwaysTrue, notIdentical.alwaysTrue (Defensive check for fixture data integrity)
            if (isset($data['project']) && null !== $data['project']) {
                $project = $manager->getRepository(Project::class)->findOneBy(['name' => $data['project']]);
                if ($project !== null) {
                    $transaction->addProject($project);
                }
            }

            // Persist and flush to get ID
            $this->persistAndFlush($manager, $transaction);

            // Assign multiple categories
            $this->assignCategories($manager, $transaction, $categories, DomainEntityType::Transaction);
        }

        $this->flushSafely($manager);
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
