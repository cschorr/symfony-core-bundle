<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\Project;
use C3net\CoreBundle\Entity\Transaction;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Enum\DomainEntityType;
use C3net\CoreBundle\Enum\ProjectPriority;
use C3net\CoreBundle\Enum\ProjectStatus;
use C3net\CoreBundle\Enum\TransactionType;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProjectFixtures extends AbstractCategorizableFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $projectsData = [
            ['name' => 'E-Commerce Platform', 'status' => ProjectStatus::IN_PROGRESS, 'priority' => ProjectPriority::HIGH, 'description' => 'Modern e-commerce platform with advanced features', 'client' => 'Cyberdyne Systems', 'assignee' => 'editor@example.com', 'categories' => ['Web Development', 'Technology', 'Creative & Design'], 'dueDate' => new \DateTimeImmutable('Monday this week 9:00')],
            ['name' => 'AI Security System', 'status' => ProjectStatus::PLANNING, 'priority' => ProjectPriority::CRITICAL, 'description' => 'Advanced AI-powered security and surveillance system', 'client' => 'Cyberdyne Systems', 'assignee' => 'editor@example.com', 'categories' => ['Technology', 'AI & Machine Learning', 'Cybersecurity'], 'dueDate' => new \DateTimeImmutable('Monday this week 14:30')],
            ['name' => 'Automated Defense Network', 'status' => ProjectStatus::COMPLETED, 'priority' => ProjectPriority::HIGH, 'description' => 'Fully automated defense and monitoring network', 'client' => 'Cyberdyne Systems', 'assignee' => 'admin@example.com', 'categories' => ['Software Solutions', 'Technology', 'Cybersecurity'], 'dueDate' => new \DateTimeImmutable('Tuesday this week 10:15')],
            ['name' => 'Mobile Banking App', 'status' => ProjectStatus::PLANNING, 'priority' => ProjectPriority::HIGH, 'description' => 'Secure mobile banking application with biometric authentication', 'client' => 'Stark Industries', 'assignee' => 'teamlead@example.com', 'categories' => ['Mobile Development', 'Technology', 'Software Solutions'], 'dueDate' => new \DateTimeImmutable('Tuesday this week 16:00')],
            ['name' => 'Arc Reactor Monitoring', 'status' => ProjectStatus::IN_PROGRESS, 'priority' => ProjectPriority::CRITICAL, 'description' => 'Real-time monitoring system for arc reactor technology', 'client' => 'Stark Industries', 'assignee' => 'teamlead@example.com', 'categories' => ['Software Solutions', 'Technology', 'DevOps & Infrastructure'], 'dueDate' => new \DateTimeImmutable('Wednesday this week 11:45')],
            ['name' => 'Business Process Optimization', 'status' => ProjectStatus::ON_HOLD, 'priority' => ProjectPriority::MEDIUM, 'description' => 'Analysis and optimization of business workflows', 'client' => 'Wayne Enterprises', 'assignee' => 'external@example.com', 'categories' => ['Consulting', 'Business Services', 'Strategy Consulting'], 'dueDate' => new \DateTimeImmutable('Wednesday this week 15:20')],
            ['name' => 'Corporate Security Upgrade', 'status' => ProjectStatus::IN_PROGRESS, 'priority' => ProjectPriority::HIGH, 'description' => 'Enterprise-wide security system upgrade', 'client' => 'Wayne Enterprises', 'assignee' => 'external@example.com', 'categories' => ['Business Services', 'Technology', 'Cybersecurity'], 'dueDate' => new \DateTimeImmutable('Thursday this week 9:30')],
            ['name' => 'Financial Portfolio Management', 'status' => ProjectStatus::COMPLETED, 'priority' => ProjectPriority::MEDIUM, 'description' => 'Advanced portfolio management and analysis system', 'client' => 'Wayne Enterprises', 'assignee' => 'consultant1@example.com', 'categories' => ['Financial Services', 'Software Solutions', 'Business Services'], 'dueDate' => new \DateTimeImmutable('Thursday this week 13:10')],
            ['name' => 'R&D Dashboard', 'status' => ProjectStatus::IN_PROGRESS, 'priority' => ProjectPriority::MEDIUM, 'description' => 'Real-time R&D analytics and reporting', 'client' => 'Oscorp', 'assignee' => 'demo@example.com', 'categories' => ['Software Solutions', 'Technology', 'Media & Production'], 'dueDate' => new \DateTimeImmutable('Thursday this week 16:45')],
            ['name' => 'Scientific Data Analysis', 'status' => ProjectStatus::PLANNING, 'priority' => ProjectPriority::HIGH, 'description' => 'Advanced data analysis platform for scientific research', 'client' => 'Oscorp', 'assignee' => 'demo@example.com', 'categories' => ['Technology', 'Software Solutions', 'AI & Machine Learning'], 'dueDate' => new \DateTimeImmutable('Friday this week 10:00')],
            ['name' => 'Enterprise CMS', 'status' => ProjectStatus::COMPLETED, 'priority' => ProjectPriority::MEDIUM, 'description' => 'Enterprise-grade content management solution', 'client' => 'Weyland-Yutani', 'assignee' => 'admin@example.com', 'categories' => ['Content Creation', 'Web Development', 'Software Solutions'], 'dueDate' => new \DateTimeImmutable('Friday this week 14:15')],
            ['name' => 'Digital Marketing Campaign', 'status' => ProjectStatus::IN_PROGRESS, 'priority' => ProjectPriority::HIGH, 'description' => 'Comprehensive digital marketing strategy implementation', 'client' => 'Umbrella Corporation', 'assignee' => 'marketing@example.com', 'categories' => ['Digital Marketing', 'Marketing & Sales', 'Creative & Design'], 'dueDate' => new \DateTimeImmutable('Friday this week 17:00')],
            ['name' => 'Pharmaceutical Research Portal', 'status' => ProjectStatus::PLANNING, 'priority' => ProjectPriority::MEDIUM, 'description' => 'Web portal for pharmaceutical research and development', 'client' => 'Umbrella Corporation', 'assignee' => 'marketing@example.com', 'categories' => ['Web Development', 'Technology', 'Content Creation'], 'dueDate' => new \DateTimeImmutable('Monday next week 9:15')],
            ['name' => 'Global Distribution Network', 'status' => ProjectStatus::IN_PROGRESS, 'priority' => ProjectPriority::HIGH, 'description' => 'Worldwide distribution and logistics management system', 'client' => 'Umbrella Corporation', 'assignee' => 'marketing1@example.com', 'categories' => ['Marketing & Sales', 'Software Solutions', 'Business Services'], 'dueDate' => new \DateTimeImmutable('Monday next week 12:30')],
            ['name' => 'Neural Interface Development', 'status' => ProjectStatus::PLANNING, 'priority' => ProjectPriority::CRITICAL, 'description' => 'Advanced AI-driven neural interface system', 'client' => 'GeneDyne Technologies', 'assignee' => 'teamlead@example.com', 'categories' => ['Technology', 'AI & Machine Learning', 'Software Solutions'], 'dueDate' => new \DateTimeImmutable('Monday next week 15:45')],
            ['name' => 'Quantum Computing Research', 'status' => ProjectStatus::IN_PROGRESS, 'priority' => ProjectPriority::CRITICAL, 'description' => 'Research and development of quantum computing solutions', 'client' => 'NeuralLink Systems', 'assignee' => 'admin@example.com', 'categories' => ['Software Solutions', 'Technology', 'AI & Machine Learning'], 'dueDate' => new \DateTimeImmutable('Tuesday next week 10:20')],
        ];

        foreach ($projectsData as $index => $projectData) {
            $client = $manager->getRepository(Company::class)->findOneBy(['name' => $projectData['client']]);
            $assignee = $manager->getRepository(User::class)->findOneBy(['email' => $projectData['assignee']]);
            $categories = $this->findCategoriesByNames($manager, $projectData['categories']);

            // Create transaction for the project
            $transaction = (new Transaction())
                ->setName('Transaction for ' . $projectData['name'])
                ->setCustomer($client)
                ->setAssignedTo($assignee)
                ->setTransactionType(TransactionType::PROJECT);

            $manager->persist($transaction);

            $project = (new Project())
                ->setName($projectData['name'])
                ->setStatus($projectData['status'])
                ->setPriority($projectData['priority'])
                ->setDescription($projectData['description'])
                ->setTransaction($transaction)
                ->setAssignee($assignee)
                ->setDueDate($projectData['dueDate']);

            // Persist and flush to get ID
            $this->persistAndFlush($manager, $project);

            // Assign multiple categories
            $this->assignCategories($manager, $project, $categories, DomainEntityType::Project);
        }

        $this->flushSafely($manager);
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
            CompanyFixtures::class,
            UserFixtures::class,
        ];
    }
}
