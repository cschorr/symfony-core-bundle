<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\Project;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Enum\ProjectStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProjectFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $projectsData = [
            ['name' => 'E-Commerce Platform', 'status' => ProjectStatus::IN_PROGRESS, 'description' => 'Modern e-commerce platform with advanced features', 'client' => 'company_0', 'assignee' => 'editor', 'category' => 'sub1', 'dueDate' => new \DateTimeImmutable('Monday this week 9:00')],
            ['name' => 'AI Security System', 'status' => ProjectStatus::PLANNING, 'description' => 'Advanced AI-powered security and surveillance system', 'client' => 'company_0', 'assignee' => 'editor', 'category' => 'main1', 'dueDate' => new \DateTimeImmutable('Monday this week 14:30')],
            ['name' => 'Automated Defense Network', 'status' => ProjectStatus::COMPLETED, 'description' => 'Fully automated defense and monitoring network', 'client' => 'company_0', 'assignee' => 'admin', 'category' => 'sub3', 'dueDate' => new \DateTimeImmutable('Tuesday this week 10:15')],
            ['name' => 'Mobile Banking App', 'status' => ProjectStatus::PLANNING, 'description' => 'Secure mobile banking application with biometric authentication', 'client' => 'company_1', 'assignee' => 'teamlead', 'category' => 'sub2', 'dueDate' => new \DateTimeImmutable('Tuesday this week 16:00')],
            ['name' => 'Arc Reactor Monitoring', 'status' => ProjectStatus::IN_PROGRESS, 'description' => 'Real-time monitoring system for arc reactor technology', 'client' => 'company_1', 'assignee' => 'teamlead', 'category' => 'sub3', 'dueDate' => new \DateTimeImmutable('Wednesday this week 11:45')],
            ['name' => 'Business Process Optimization', 'status' => ProjectStatus::ON_HOLD, 'description' => 'Analysis and optimization of business workflows', 'client' => 'company_2', 'assignee' => 'external', 'category' => 'main4', 'dueDate' => new \DateTimeImmutable('Wednesday this week 15:20')],
            ['name' => 'Corporate Security Upgrade', 'status' => ProjectStatus::IN_PROGRESS, 'description' => 'Enterprise-wide security system upgrade', 'client' => 'company_2', 'assignee' => 'external', 'category' => 'main2', 'dueDate' => new \DateTimeImmutable('Thursday this week 9:30')],
            ['name' => 'Financial Portfolio Management', 'status' => ProjectStatus::COMPLETED, 'description' => 'Advanced portfolio management and analysis system', 'client' => 'company_2', 'assignee' => 'consultant1', 'category' => 'sub4', 'dueDate' => new \DateTimeImmutable('Thursday this week 13:10')],
            ['name' => 'R&D Dashboard', 'status' => ProjectStatus::IN_PROGRESS, 'description' => 'Real-time R&D analytics and reporting', 'client' => 'company_3', 'assignee' => 'demo', 'category' => 'sub3', 'dueDate' => new \DateTimeImmutable('Thursday this week 16:45')],
            ['name' => 'Scientific Data Analysis', 'status' => ProjectStatus::PLANNING, 'description' => 'Advanced data analysis platform for scientific research', 'client' => 'company_3', 'assignee' => 'demo', 'category' => 'main1', 'dueDate' => new \DateTimeImmutable('Friday this week 10:00')],
            ['name' => 'Enterprise CMS', 'status' => ProjectStatus::COMPLETED, 'description' => 'Enterprise-grade content management solution', 'client' => 'company_4', 'assignee' => 'admin', 'category' => 'sub7', 'dueDate' => new \DateTimeImmutable('Friday this week 14:15')],
            ['name' => 'Digital Marketing Campaign', 'status' => ProjectStatus::IN_PROGRESS, 'description' => 'Comprehensive digital marketing strategy implementation', 'client' => 'company_5', 'assignee' => 'manager', 'category' => 'sub6', 'dueDate' => new \DateTimeImmutable('Friday this week 17:00')],
            ['name' => 'Pharmaceutical Research Portal', 'status' => ProjectStatus::PLANNING, 'description' => 'Web portal for pharmaceutical research and development', 'client' => 'company_5', 'assignee' => 'manager', 'category' => 'sub1', 'dueDate' => new \DateTimeImmutable('Monday next week 9:15')],
            ['name' => 'Global Distribution Network', 'status' => ProjectStatus::IN_PROGRESS, 'description' => 'Worldwide distribution and logistics management system', 'client' => 'company_5', 'assignee' => 'marketing1', 'category' => 'main3', 'dueDate' => new \DateTimeImmutable('Monday next week 12:30')],
            ['name' => 'Neural Interface Development', 'status' => ProjectStatus::PLANNING, 'description' => 'Advanced AI-driven neural interface system', 'client' => 'company_6', 'assignee' => 'teamlead', 'category' => 'main1', 'dueDate' => new \DateTimeImmutable('Monday next week 15:45')],
            ['name' => 'Quantum Computing Research', 'status' => ProjectStatus::IN_PROGRESS, 'description' => 'Research and development of quantum computing solutions', 'client' => 'company_7', 'assignee' => 'admin', 'category' => 'sub3', 'dueDate' => new \DateTimeImmutable('Tuesday next week 10:20')],
        ];

        foreach ($projectsData as $index => $projectData) {
            $client = $this->getReference($projectData['client'], Company::class);
            $assignee = $this->getReference('user_' . $projectData['assignee'], User::class);
            $category = $this->getReference($projectData['category'], Category::class);

            $project = (new Project())
                ->setName($projectData['name'])
                ->setStatus($projectData['status'])
                ->setDescription($projectData['description'])
                ->setClient($client)
                ->setAssignee($assignee)
                ->setCategory($category)
                ->setDueDate($projectData['dueDate']);

            $manager->persist($project);
            $this->addReference('project_' . $index, $project);
        }

        $manager->flush();
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
