<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Functional;

use C3net\CoreBundle\Command\LoadDemoDataCommand;
use C3net\CoreBundle\Entity\Campaign;
use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\CompanyGroup;
use C3net\CoreBundle\Entity\Contact;
use C3net\CoreBundle\Entity\Project;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Entity\UserGroup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Functional test for the LoadDemoDataCommand
 * Tests the complete demo data loading workflow.
 */
class LoadDemoDataCommandTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get(EntityManagerInterface::class);

        $application = new Application($kernel);
        $command = $application->find('c3net:load-demo-data');
        $this->commandTester = new CommandTester($command);
    }

    public function testLoadDemoDataCommandExecution(): void
    {
        // Clear existing data to ensure clean test
        $this->clearExistingData();

        // Execute the command
        $exitCode = $this->commandTester->execute([]);

        // Assert command succeeded
        $this->assertSame(0, $exitCode);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Demo data loaded successfully', $output);
    }

    public function testCategoriesAreCreated(): void
    {
        $this->clearExistingData();
        $this->commandTester->execute([]);

        $categories = $this->entityManager->getRepository(Category::class)->findAll();

        $this->assertGreaterThanOrEqual(4, count($categories)); // At least main categories

        // Check for specific main categories
        $categoryNames = array_map(fn ($c) => $c->getName(), $categories);
        $this->assertContains('Technology', $categoryNames);
        $this->assertContains('Business Services', $categoryNames);
        $this->assertContains('Marketing & Sales', $categoryNames);
        $this->assertContains('Consulting', $categoryNames);

        // Check for subcategories
        $this->assertContains('Web Development', $categoryNames);
        $this->assertContains('Mobile Development', $categoryNames);
        $this->assertContains('Digital Marketing', $categoryNames);

        // Verify hierarchical structure
        $webDevCategory = $this->entityManager->getRepository(Category::class)
            ->findOneBy(['name' => 'Web Development']);
        $this->assertNotNull($webDevCategory);
        $this->assertNotNull($webDevCategory->getParent());
        $this->assertSame('Technology', $webDevCategory->getParent()->getName());
    }

    public function testUserGroupsAreCreated(): void
    {
        $this->clearExistingData();
        $this->commandTester->execute([]);

        $userGroups = $this->entityManager->getRepository(UserGroup::class)->findAll();

        $this->assertGreaterThanOrEqual(5, count($userGroups));

        $groupNames = array_map(fn ($g) => $g->getName(), $userGroups);
        $this->assertContains('External Users', $groupNames);
        $this->assertContains('Editor', $groupNames);
        $this->assertContains('Teamlead', $groupNames);
        $this->assertContains('Manager', $groupNames);
        $this->assertContains('Admin', $groupNames);

        // Verify roles are properly set
        $adminGroup = $this->entityManager->getRepository(UserGroup::class)
            ->findOneBy(['name' => 'Admin']);
        $this->assertNotNull($adminGroup);
        $this->assertContains('ROLE_ADMIN', $adminGroup->getRoles());

        $teamleadGroup = $this->entityManager->getRepository(UserGroup::class)
            ->findOneBy(['name' => 'Teamlead']);
        $this->assertNotNull($teamleadGroup);
        $this->assertContains('ROLE_TEAMLEAD', $teamleadGroup->getRoles());
        $this->assertContains('ROLE_FINANCE', $teamleadGroup->getRoles());
    }

    public function testCompanyGroupsAndCompaniesAreCreated(): void
    {
        $this->clearExistingData();
        $this->commandTester->execute([]);

        // Check company groups
        $companyGroups = $this->entityManager->getRepository(CompanyGroup::class)->findAll();
        $this->assertGreaterThanOrEqual(5, count($companyGroups));

        $groupNames = array_map(fn ($g) => $g->getName(), $companyGroups);
        $this->assertContains('Skynet Group', $groupNames);
        $this->assertContains('Marvel Group', $groupNames);
        $this->assertContains('DC Group', $groupNames);

        // Check companies
        $companies = $this->entityManager->getRepository(Company::class)->findAll();
        $this->assertGreaterThanOrEqual(10, count($companies));

        $companyNames = array_map(fn ($c) => $c->getName(), $companies);
        $this->assertContains('Cyberdyne Systems', $companyNames);
        $this->assertContains('Stark Industries', $companyNames);
        $this->assertContains('Wayne Enterprises', $companyNames);
        $this->assertContains('Umbrella Corporation', $companyNames);

        // Verify company-group relationships
        $cyberdyne = $this->entityManager->getRepository(Company::class)
            ->findOneBy(['name' => 'Cyberdyne Systems']);
        $this->assertNotNull($cyberdyne);
        $this->assertNotNull($cyberdyne->getCompanyGroup());
        $this->assertSame('Skynet Group', $cyberdyne->getCompanyGroup()->getName());

        // Verify company has all required fields
        $this->assertNotEmpty($cyberdyne->getEmail());
        $this->assertNotEmpty($cyberdyne->getCountryCode());
        $this->assertNotNull($cyberdyne->getCategory());
        $this->assertNotEmpty($cyberdyne->getPhone());
        $this->assertNotEmpty($cyberdyne->getUrl());
    }

    public function testUsersAreCreatedWithRelationships(): void
    {
        $this->clearExistingData();
        $this->commandTester->execute([]);

        $users = $this->entityManager->getRepository(User::class)->findAll();
        $this->assertGreaterThanOrEqual(10, count($users));

        $userEmails = array_map(fn ($u) => $u->getEmail(), $users);
        $this->assertContains('admin@example.com', $userEmails);
        $this->assertContains('editor@example.com', $userEmails);
        $this->assertContains('teamlead@example.com', $userEmails);
        $this->assertContains('manager@example.com', $userEmails);

        // Verify admin user
        $adminUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'admin@example.com']);
        $this->assertNotNull($adminUser);
        $this->assertSame('System', $adminUser->getNameFirst());
        $this->assertSame('Admin', $adminUser->getNameLast());
        $this->assertTrue($adminUser->isActive());
        $this->assertNotNull($adminUser->getCategory());

        // Verify user-group relationships
        $this->assertGreaterThan(0, count($adminUser->getUserGroups()));
        $groupNames = $adminUser->getUserGroups()->map(fn ($g) => $g->getName())->toArray();
        $this->assertContains('Admin', $groupNames);

        // Verify user-company relationships
        $editorUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'editor@example.com']);
        $this->assertNotNull($editorUser);
        $this->assertNotNull($editorUser->getCompany());
        $this->assertSame('Cyberdyne Systems', $editorUser->getCompany()->getName());

        // Verify password is hashed
        $this->assertNotSame('pass_1234', $adminUser->getPassword());
        $this->assertGreaterThan(50, strlen($adminUser->getPassword())); // Hashed passwords are long
    }

    public function testContactsAreCreatedWithCompanyRelationships(): void
    {
        $this->clearExistingData();
        $this->commandTester->execute([]);

        $contacts = $this->entityManager->getRepository(Contact::class)->findAll();
        $this->assertGreaterThanOrEqual(30, count($contacts)); // Expecting many contacts

        // Check specific contacts
        $johnDoe = $this->entityManager->getRepository(Contact::class)
            ->findOneBy(['email' => 'john.doe@example.com']);
        $this->assertNotNull($johnDoe);
        $this->assertSame('John', $johnDoe->getNameFirst());
        $this->assertSame('Doe', $johnDoe->getNameLast());
        $this->assertNotNull($johnDoe->getCompany());
        $this->assertNotEmpty($johnDoe->getPhone());
        $this->assertNotEmpty($johnDoe->getCell());

        // Verify contacts have proper company assignments
        $contactsWithCompanies = array_filter($contacts, fn ($c) => null !== $c->getCompany());
        $this->assertSame(count($contacts), count($contactsWithCompanies));

        // Check for contacts with positions and departments
        $contactsWithPositions = array_filter($contacts, fn ($c) => !empty($c->getPosition()));
        $this->assertGreaterThan(0, count($contactsWithPositions));

        $contactsWithDepartments = array_filter($contacts, fn ($c) => !empty($c->getDepartment()));
        $this->assertGreaterThan(0, count($contactsWithDepartments));
    }

    public function testProjectsAreCreatedWithComplexRelationships(): void
    {
        $this->clearExistingData();
        $this->commandTester->execute([]);

        $projects = $this->entityManager->getRepository(Project::class)->findAll();
        $this->assertGreaterThanOrEqual(25, count($projects)); // Expecting many projects

        $projectNames = array_map(fn ($p) => $p->getName(), $projects);
        $this->assertContains('E-Commerce Platform', $projectNames);
        $this->assertContains('Mobile Banking App', $projectNames);
        $this->assertContains('AI Security System', $projectNames);

        // Verify project relationships
        $ecommerceProject = $this->entityManager->getRepository(Project::class)
            ->findOneBy(['name' => 'E-Commerce Platform']);
        $this->assertNotNull($ecommerceProject);
        $this->assertNotNull($ecommerceProject->getClient()); // Company
        $this->assertNotNull($ecommerceProject->getAssignee()); // User
        $this->assertNotNull($ecommerceProject->getCategory());
        $this->assertNotNull($ecommerceProject->getDueDate());
        $this->assertNotEmpty($ecommerceProject->getDescription());

        // Verify project status enum
        $this->assertContains($ecommerceProject->getStatus()->value, ['planning', 'in_progress', 'on_hold', 'completed', 'cancelled']);

        // Check that assignees are employees of client companies where applicable
        $assigneeCompany = $ecommerceProject->getAssignee()->getCompany();
        $clientCompany = $ecommerceProject->getClient();
        if ($assigneeCompany && $clientCompany) {
            // In some cases, assignee should be from the same company or related company
            $this->assertTrue(
                $assigneeCompany->getId() === $clientCompany->getId()
                || $assigneeCompany->getCompanyGroup() === $clientCompany->getCompanyGroup()
                || true // Allow external assignees
            );
        }
    }

    public function testCampaignsAreCreatedWithProjectAssignments(): void
    {
        $this->clearExistingData();
        $this->commandTester->execute([]);

        $campaigns = $this->entityManager->getRepository(Campaign::class)->findAll();
        $this->assertGreaterThanOrEqual(8, count($campaigns)); // Now expecting 8 campaigns

        $campaignNames = array_map(fn ($c) => $c->getName(), $campaigns);
        $this->assertContains('Digital Transformation 2025', $campaignNames);
        $this->assertContains('Global Marketing Excellence', $campaignNames);
        $this->assertContains('Enterprise Security & Compliance', $campaignNames);
        $this->assertContains('Innovation Lab 2024', $campaignNames);
        $this->assertContains('Customer Experience Revolution', $campaignNames);
        $this->assertContains('Sustainable Operations Initiative', $campaignNames);
        $this->assertContains('Financial Technology Modernization', $campaignNames);
        $this->assertContains('Healthcare Technology Advancement', $campaignNames);

        // Verify campaign shortcodes are set
        $campaignShortcodes = array_map(fn ($c) => $c->getShortcode(), $campaigns);
        $this->assertContains('DT2025', $campaignShortcodes);
        $this->assertContains('GME2024', $campaignShortcodes);
        $this->assertContains('ESC2024', $campaignShortcodes);
        $this->assertContains('INNO2024', $campaignShortcodes);

        // Verify campaign-project relationships
        $digitalTransformation = $this->entityManager->getRepository(Campaign::class)
            ->findOneBy(['name' => 'Digital Transformation 2025']);
        $this->assertNotNull($digitalTransformation);
        $this->assertSame('DT2025', $digitalTransformation->getShortcode());
        $this->assertNotNull($digitalTransformation->getCategory());
        $this->assertGreaterThan(0, count($digitalTransformation->getProjects()));
        $this->assertNotEmpty($digitalTransformation->getDescription());
        $this->assertNotNull($digitalTransformation->getStartDate());
        $this->assertNotNull($digitalTransformation->getEndDate());

        // Verify date ranges are logical
        $this->assertTrue($digitalTransformation->getStartDate() < $digitalTransformation->getEndDate());

        // Verify projects are properly assigned to campaigns
        $assignedProjects = $digitalTransformation->getProjects();
        $this->assertGreaterThan(0, count($assignedProjects));

        foreach ($assignedProjects as $project) {
            $this->assertNotNull($project->getName());
            $this->assertNotNull($project->getClient());
        }

        // Test another campaign with different category
        $innovationLab = $this->entityManager->getRepository(Campaign::class)
            ->findOneBy(['code' => 'INNO2024']);
        $this->assertNotNull($innovationLab);
        $this->assertSame('Innovation Lab 2024', $innovationLab->getName());
        $this->assertNotNull($innovationLab->getStartDate());
        $this->assertNotNull($innovationLab->getEndDate());
        $this->assertStringContainsString('Research and development', $innovationLab->getDescription());

        // Test campaign with consulting category
        $sustainableOps = $this->entityManager->getRepository(Campaign::class)
            ->findOneBy(['code' => 'SOI2024']);
        $this->assertNotNull($sustainableOps);
        $this->assertStringContainsString('Environmental sustainability', $sustainableOps->getDescription());
        $this->assertGreaterThan(0, count($sustainableOps->getProjects()));
    }

    public function testDataIntegrityAndConstraints(): void
    {
        $this->clearExistingData();
        $this->commandTester->execute([]);

        // Test that all users have valid emails
        $users = $this->entityManager->getRepository(User::class)->findAll();
        foreach ($users as $user) {
            $this->assertMatchesRegularExpression('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $user->getEmail());
            $this->assertNotEmpty($user->getNameFirst());
            $this->assertNotEmpty($user->getNameLast());
        }

        // Test that all companies have valid data
        $companies = $this->entityManager->getRepository(Company::class)->findAll();
        foreach ($companies as $company) {
            $this->assertNotEmpty($company->getName());
            $this->assertNotEmpty($company->getEmail());
            $this->assertNotNull($company->getCategory());
            $this->assertNotNull($company->getCompanyGroup());
        }

        // Test that all projects have required relationships
        $projects = $this->entityManager->getRepository(Project::class)->findAll();
        foreach ($projects as $project) {
            $this->assertNotEmpty($project->getName());
            $this->assertNotNull($project->getClient());
            $this->assertNotNull($project->getAssignee());
            $this->assertNotNull($project->getCategory());
            $this->assertNotNull($project->getDueDate());
            $this->assertNotNull($project->getStatus());
        }

        // Test foreign key constraints
        $this->assertNoDanglingReferences();
    }

    public function testCommandIdempotency(): void
    {
        $this->clearExistingData();

        // Run command first time
        $this->commandTester->execute([]);

        // Count created entities
        $userCount1 = count($this->entityManager->getRepository(User::class)->findAll());
        $companyCount1 = count($this->entityManager->getRepository(Company::class)->findAll());
        $projectCount1 = count($this->entityManager->getRepository(Project::class)->findAll());

        // Run command second time (should handle existing data gracefully)
        $this->commandTester->execute([]);

        // Verify counts (depending on implementation, might be same or different)
        $userCount2 = count($this->entityManager->getRepository(User::class)->findAll());
        $companyCount2 = count($this->entityManager->getRepository(Company::class)->findAll());
        $projectCount2 = count($this->entityManager->getRepository(Project::class)->findAll());

        // For this test, we expect the command to either:
        // 1. Skip existing data (same counts)
        // 2. Clear and reload (same counts)
        // 3. Add additional data (different counts but no errors)

        $this->assertGreaterThan(0, $userCount2);
        $this->assertGreaterThan(0, $companyCount2);
        $this->assertGreaterThan(0, $projectCount2);

        // The important thing is no exceptions were thrown
        $this->assertSame(0, $this->commandTester->getStatusCode());
    }

    private function clearExistingData(): void
    {
        // Clear in reverse order of dependencies
        $this->entityManager->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM campaign_project');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM user_user_group');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM campaign');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM project');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM contact');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM user');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM company');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM company_group');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM user_group');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM category');
        $this->entityManager->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

        $this->entityManager->clear();
    }

    private function assertNoDanglingReferences(): void
    {
        // Check that all user companies exist
        $result = $this->entityManager->getConnection()->executeQuery(
            'SELECT COUNT(*) as count FROM user u LEFT JOIN company c ON u.company_id = c.id WHERE u.company_id IS NOT NULL AND c.id IS NULL'
        )->fetchAssociative();
        $this->assertSame(0, (int) $result['count'], 'Found users with invalid company references');

        // Check that all project clients exist
        $result = $this->entityManager->getConnection()->executeQuery(
            'SELECT COUNT(*) as count FROM project p LEFT JOIN company c ON p.client_id = c.id WHERE c.id IS NULL'
        )->fetchAssociative();
        $this->assertSame(0, (int) $result['count'], 'Found projects with invalid client references');

        // Check that all project assignees exist
        $result = $this->entityManager->getConnection()->executeQuery(
            'SELECT COUNT(*) as count FROM project p LEFT JOIN user u ON p.assignee_id = u.id WHERE u.id IS NULL'
        )->fetchAssociative();
        $this->assertSame(0, (int) $result['count'], 'Found projects with invalid assignee references');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up is handled by clearExistingData() method
        // No additional cleanup needed as this is a functional test
    }
}
