<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Company;
use App\Entity\Contact;
use App\Entity\Project;
use App\Entity\DomainEntityPermission;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\UserGroupDomainEntityPermission;
use App\Entity\Thread;
use App\Entity\Comment;
use App\Entity\Vote;
use App\Enum\ProjectStatus;
use App\Enum\DomainEntityType as SystemEntityEnum;
use App\Repository\UserGroupRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const DEFAULT_PASSWORD = 'pass_1234';
    private array $users = [];
    private array $userGroups = [];
    private array $systemEntities = [];
    private array $categories = [];
    private array $companies = [];
    private array $contacts = [];
    private array $threads = [];
    private array $comments = [];

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly UserGroupRepository $userGroupRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Order is important for dependencies
        $this->createSystemEntityFixtures($manager);
        $this->createCategoryFixtures($manager);
        $this->createUserGroupFixtures($manager);
        $this->createUserFixtures($manager);
        $this->createPermissionFixtures($manager);
        $this->createCompanyFixtures($manager);
        $this->createContactFixtures($manager);
        $this->createProjectFixtures($manager);

        // New fixtures for discussions
        $this->createThreadFixtures($manager);
        $this->createCommentFixtures($manager);
        $this->createVoteFixtures($manager);
    }

    private function createSystemEntityFixtures(ObjectManager $manager): void
    {
        $systemEntitiesData = [
            'DomainEntityPermission' => [
                'name' => 'SystemEntities',
                'text' => 'System entities and configuration',
                'icon' => 'fas fa-list',
            ],
            'User' => [
                'name' => 'Users',
                'text' => 'User management',
                'icon' => 'fas fa-users',
            ],
            'UserGroup' => [
                'name' => 'Usergroups',
                'text' => 'Usergroup management',
                'icon' => 'fas fa-users',
            ],
            'Company' => [
                'name' => 'Companies',
                'text' => 'Clients, suppliers, partners etc.',
                'icon' => 'fas fa-building',
            ],
            'CompanyGroup' => [
                'name' => 'CompanyGroups',
                'text' => 'Groups of companies',
                'icon' => 'fas fa-layer-group',
            ],
            'Contact' => [
                'name' => 'Contacts',
                'text' => 'Contact persons',
                'icon' => 'fas fa-users',
            ],
            'Project' => [
                'name' => 'Projects',
                'text' => 'Manage projects',
                'icon' => 'fas fa-project-diagram',
            ],
            'Category' => [
                'name' => 'Categories',
                'text' => 'Manage categories',
                'icon' => 'fas fa-tags',
            ],
        ];

        foreach ($systemEntitiesData as $code => $data) {
            $systemEntity = (new DomainEntityPermission())
                ->setName($data['name'])
                ->setCode($code)
                ->setText($data['text'])
                ->setIcon($data['icon']);

            $manager->persist($systemEntity);
            $this->systemEntities[$code] = $systemEntity;
        }

        $manager->flush();
    }

    private function createCategoryFixtures(ObjectManager $manager): void
    {
        $categoriesData = [
            'main1' => [
                'name' => 'Technology',
                'color' => 'blue',
                'icon' => 'fas fa-laptop-code',
            ],
            'main2' => [
                'name' => 'Business Services',
                'color' => 'red',
                'icon' => 'fas fa-briefcase',
            ],
            'main3' => [
                'name' => 'Marketing & Sales',
                'color' => 'green',
                'icon' => 'fas fa-bullhorn',
            ],
            'main4' => [
                'name' => 'Consulting',
                'color' => 'purple',
                'icon' => 'fas fa-user-tie',
            ],
        ];

        // Create main categories first
        foreach ($categoriesData as $key => $data) {
            $category = (new Category())
                ->setName($data['name'])
                ->setColor($data['color'])
                ->setIcon($data['icon']);

            $manager->persist($category);
            $this->categories[$key] = $category;
        }

        $manager->flush();

        // Then create subcategories
        $subCategoriesData = [
            'sub1' => [
                'name' => 'Web Development',
                'color' => 'lightblue',
                'icon' => 'fas fa-globe',
                'parent' => 'main1',
            ],
            'sub2' => [
                'name' => 'Mobile Development',
                'color' => 'lightblue',
                'icon' => 'fas fa-mobile-alt',
                'parent' => 'main1',
            ],
            'sub3' => [
                'name' => 'Software Solutions',
                'color' => 'lightblue',
                'icon' => 'fas fa-code',
                'parent' => 'main1',
            ],
            'sub4' => [
                'name' => 'Financial Services',
                'color' => 'lightred',
                'icon' => 'fas fa-coins',
                'parent' => 'main2',
            ],
            'sub5' => [
                'name' => 'Legal Services',
                'color' => 'lightred',
                'icon' => 'fas fa-gavel',
                'parent' => 'main2',
            ],
            'sub6' => [
                'name' => 'Digital Marketing',
                'color' => 'lightgreen',
                'icon' => 'fas fa-chart-line',
                'parent' => 'main3',
            ],
            'sub7' => [
                'name' => 'Content Creation',
                'color' => 'lightgreen',
                'icon' => 'fas fa-pen-fancy',
                'parent' => 'main3',
            ],
        ];

        foreach ($subCategoriesData as $key => $data) {
            $category = (new Category())
                ->setName($data['name'])
                ->setColor($data['color'])
                ->setIcon($data['icon']);

            $category->setParent($this->categories[$data['parent']]);

            $manager->persist($category);
            $this->categories[$key] = $category;
        }

        $manager->flush();
    }

    private function createUserGroupFixtures(ObjectManager $manager): void
    {
        $usersData = [
            'external' => [
                'name' => 'External Users',
                'roles' => ['ROLE_EXTERNAL'],
                'active' => true,
            ],
            'basic' => [
                'name' => 'Editor',
                'roles' => ['ROLE_EDITOR'],
                'active' => true,
            ],
            'advanced' => [
                'name' => 'Teamlead',
                'roles' => ['ROLE_TEAMLEAD'],
                'active' => true,
            ],
            'manager' => [
                'name' => 'Manager',
                'roles' => ['ROLE_MANAGER'],
                'active' => true,
            ],
            'admin' => [
                'name' => 'Admin',
                'roles' => ['ROLE_ADMIN'],
                'active' => true,
            ],
        ];

        foreach ($usersData as $key => $userData) {
            $userGroup = new UserGroup();
            $userGroup
                ->setName($userData['name'])
                ->setRoles($userData['roles'])
                ->setActive($userData['active'])
            ;

            $manager->persist($userGroup);
            $this->userGroups[$key] = $userGroup;
        }

        $manager->flush();
    }

    private function createUserFixtures(ObjectManager $manager): void
    {
        $usersData = [
            'admin' => [
                'email' => 'admin@example.com',
                'active' => true,
                'notes' => 'Administrator user with full access',
                'category' => 'main2', // Business Services
                'nameLast' => 'Admin',
                'nameFirst' => 'User',
                'userGroups' => ['Admin'],
            ],
            'editor' => [
                'email' => 'editor@example.com',
                'active' => true,
                'notes' => 'Demo user with limited access',
                'category' => 'sub1', // Web Development
                'nameLast' => 'Demo',
                'nameFirst' => 'User',
                'userGroups' => ['Editor'],
            ],
            'teamlead' => [
                'email' => 'teamlead@example.com',
                'active' => true,
                'notes' => 'Senior developer specializing in mobile apps',
                'category' => 'sub2', // Mobile Development
                'nameLast' => 'Developer',
                'nameFirst' => 'User',
                'userGroups' => ['Teamlead'],
            ],
            'manager' => [
                'email' => 'marketing@example.com',
                'active' => true,
                'notes' => 'Marketing specialist for digital campaigns',
                'category' => 'sub6', // Digital Marketing
                'nameLast' => 'Marketing',
                'nameFirst' => 'User',
                'userGroups' => ['Manager'],
            ],
            'external' => [
                'email' => 'external@example.com',
                'active' => true,
                'notes' => 'Business consultant for process optimization',
                'category' => 'main4', // Consulting
                'nameLast' => 'Consultant',
                'nameFirst' => 'User',
                'userGroups' => ['External Users'],
            ],
            'demo' => [
                'email' => 'demo@example.com',
                'active' => true,
                'notes' => 'Demo user with limited access',
                'category' => 'sub1', // Web Development
                'nameLast' => 'Demo',
                'nameFirst' => 'User',
                'userGroups' => ['Editor'],
            ],
        ];

        foreach ($usersData as $key => $userData) {
            $category = $this->categories[$userData['category']] ?? null;
            // get usergroups from entity manager
            $userGroups = $this->userGroupRepository->findBy(['name' => $userData['userGroups']]);

            // Add error handling to debug missing categories
            if (!$category) {
                throw new \Exception(
                    sprintf(
                        'Category "%s" not found for user "%s". Available categories: %s',
                        $userData['category'],
                        $key,
                        implode(', ', array_keys($this->categories))
                    )
                );
            }

            $user = new User();
            $user->setEmail($userData['email'])
                ->setPassword($this->hasher->hashPassword($user, self::DEFAULT_PASSWORD))
                ->setActive($userData['active'])
                ->setNotes($userData['notes'])
                ->setNameLast($userData['nameLast'])
                ->setNameFirst($userData['nameFirst'])
                ->setCategory($category)
            ;
            foreach ($userGroups as $userGroup) {
                $user->addUserGroup($userGroup);
            }

            $manager->persist($user);
            $this->users[$key] = $user;
        }

        $manager->flush();
    }

    private function createPermissionFixtures(ObjectManager $manager): void
    {
        // Permissions for admin (full rights)
        $adminPermissions = [
            'User' => ['read' => true, 'write' => true],
            'UserGroup' => ['read' => true, 'write' => true],
            'Company' => ['read' => true, 'write' => true],
            'DomainEntityPermission' => ['read' => true, 'write' => true],
            'CompanyGroup' => ['read' => true, 'write' => true],
            'Project' => ['read' => true, 'write' => true],
            'Category' => ['read' => true, 'write' => true],
        ];

        // Permissions for demo user (limited rights)
        $demoPermissions = [
            'User' => ['read' => true, 'write' => false],
            'UserGroup' => ['read' => true, 'write' => false],
            'Company' => ['read' => true, 'write' => true],
            'Project' => ['read' => true, 'write' => false],
            'Category' => ['read' => true, 'write' => true],
        ];

        // Permissions for developer (Mobile Development)
        $developerPermissions = [
            'User' => ['read' => true, 'write' => false],
            'Company' => ['read' => true, 'write' => false],
            'Project' => ['read' => true, 'write' => true],
        ];

        // Permissions for marketing (Digital Marketing)
        $marketingPermissions = [
            'User' => ['read' => true, 'write' => false],
            'UserGroup' => ['read' => true, 'write' => false],
            'Company' => ['read' => true, 'write' => true],
            'Project' => ['read' => true, 'write' => true],
        ];

        // Permissions for consultant (Business Consulting)
        $consultantPermissions = [
            'User' => ['read' => true, 'write' => false],
            'UserGroup' => ['read' => true, 'write' => false],
            'Company' => ['read' => true, 'write' => true],
            'Project' => ['read' => true, 'write' => true],
            'CompanyGroup' => ['read' => true, 'write' => false],
        ];

        $this->createUserGroupPermissions($manager, $this->userGroups['external'], $adminPermissions);
        $this->createUserGroupPermissions($manager, $this->userGroups['basic'], $demoPermissions);
        $this->createUserGroupPermissions($manager, $this->userGroups['advanced'], $developerPermissions);
        $this->createUserGroupPermissions($manager, $this->userGroups['manager'], $marketingPermissions);
        $this->createUserGroupPermissions($manager, $this->userGroups['admin'], $consultantPermissions);

        $manager->flush();
    }

    private function createUserGroupPermissions(ObjectManager $manager, UserGroup $userGroup, array $permissions): void
    {
        foreach ($permissions as $entityCode => $rights) {
            $systemEntity = $this->systemEntities[$entityCode] ?? null;
            if (!$systemEntity) {
                continue;
            }

            // Check if permission already exists
            $existingPermission = $manager->getRepository(UserGroupDomainEntityPermission::class)
                ->findOneBy(['userGroup' => $userGroup, 'domainEntityPermission' => $systemEntity]);

            if (!$existingPermission) {
                $permission = (new UserGroupDomainEntityPermission())
                    ->setUserGroup($userGroup)
                    ->setDomainEntityPermission($systemEntity)
                    ->setCanRead($rights['read'])
                    ->setCanWrite($rights['write']);

                $manager->persist($permission);
            }
        }
    }

    private function createCompanyFixtures(ObjectManager $manager): void
    {
        $companiesData = [
            [
                'name' => 'Acme Corporation',
                'email' => 'contact@acme.com',
                'country' => 'DE',
                'category' => 'main1', // Technology
                'phone' => '+49 30 12345678',
                'url' => 'https://acme-corp.com',
                'street' => 'Technologiestraße 15',
                'city' => 'Berlin',
                'zipCode' => '10117',
            ],
            [
                'name' => 'Global Solutions Ltd',
                'email' => 'info@globalsolutions.com',
                'country' => 'UK',
                'category' => 'main2', // Business Services
                'phone' => '+44 20 7946 0958',
                'url' => 'https://globalsolutions.co.uk',
                'street' => '123 Business Street',
                'city' => 'London',
                'zipCode' => 'SW1A 1AA',
            ],
            [
                'name' => 'TechStart GmbH',
                'email' => 'hello@techstart.de',
                'country' => 'DE',
                'category' => 'sub1', // Web Development (Subcategory of Technology)
                'phone' => '+49 89 87654321',
                'url' => 'https://techstart.de',
                'street' => 'Startup Allee 42',
                'city' => 'München',
                'zipCode' => '80331',
            ],
            [
                'name' => 'Digital Marketing Pro',
                'email' => 'contact@digitalmarketing.com',
                'country' => 'DE',
                'category' => 'main3', // Marketing & Sales
                'phone' => '+49 40 11223344',
                'url' => 'https://digitalmarketing.com',
                'street' => 'Marketingplatz 7',
                'city' => 'Hamburg',
                'zipCode' => '20095',
            ],
            [
                'name' => 'Mobile Innovations Inc',
                'email' => 'info@mobileinnovations.com',
                'country' => 'US',
                'category' => 'sub2', // Mobile Development (Subcategory of Technology)
                'phone' => '+1 555 123 4567',
                'url' => 'https://mobileinnovations.com',
                'street' => '456 Innovation Drive',
                'city' => 'San Francisco',
                'zipCode' => '94105',
            ],
        ];

        foreach ($companiesData as $index => $companyData) {
            $category = $this->categories[$companyData['category']] ?? null;

            $company = (new Company())
                ->setName($companyData['name'])
                ->setEmail($companyData['email'])
                ->setCountryCode($companyData['country'])
                ->setCategory($category)
                ->setPhone($companyData['phone'])
                ->setUrl($companyData['url'])
                ->setStreet($companyData['street'])
                ->setCity($companyData['city'])
                ->setZip($companyData['zipCode']);

            $manager->persist($company);
            $this->companies["company_{$index}"] = $company; // Store reference
        }

        $manager->flush();
    }

    private function createContactFixtures(ObjectManager $manager): void
    {
        $contactsData = [
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'john.doe@example.com',
            ],
            [
                'firstName' => 'Jane',
                'lastName' => 'Smith',
                'email' => 'jane.smith@example.com',
            ],
            [
                'firstName' => 'Alice',
                'lastName' => 'Johnson',
                'email' => 'alice.johnson@example.com',
            ],
        ];

        foreach ($contactsData as $index => $contactData) {
            $contact = (new Contact())
                ->setNameFirst($contactData['firstName'])
                ->setNameLast($contactData['lastName'])
                ->setEmail($contactData['email']);

            $manager->persist($contact);
            $this->contacts["contact_{$index}"] = $contact; // Store reference
        }

        $manager->flush();
    }

    private function createProjectFixtures(ObjectManager $manager): void
    {
        $projectsData = [
            [
                'name' => 'E-Commerce Platform',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Modern e-commerce platform with advanced features',
                'client' => 'company_0', // Acme Corporation
                'assignee' => 'demo', // Web Development specialist
                'category' => 'sub1', // Web Development
            ],
            [
                'name' => 'Mobile Banking App',
                'status' => ProjectStatus::PLANNING,
                'description' => 'Secure mobile banking application with biometric authentication',
                'client' => 'company_4', // Mobile Innovations Inc
                'assignee' => 'developer', // Mobile Development specialist
                'category' => 'sub2', // Mobile Development
            ],
            [
                'name' => 'Digital Marketing Campaign',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Comprehensive digital marketing strategy implementation',
                'client' => 'company_3', // Digital Marketing Pro
                'assignee' => 'marketing', // Marketing specialist
                'category' => 'sub6', // Digital Marketing
            ],
            [
                'name' => 'Business Process Optimization',
                'status' => ProjectStatus::ON_HOLD,
                'description' => 'Analysis and optimization of business workflows',
                'client' => 'company_1', // Global Solutions Ltd
                'assignee' => 'consultant', // Business consultant
                'category' => 'main4', // Consulting
            ],
            [
                'name' => 'Financial Dashboard',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Real-time financial reporting and analytics dashboard',
                'client' => 'company_1', // Global Solutions Ltd
                'assignee' => 'demo', // Web Development
                'category' => 'sub4', // Financial Services
            ],
            [
                'name' => 'Content Management System',
                'status' => ProjectStatus::COMPLETED,
                'description' => 'Custom CMS for blog and article management',
                'client' => 'company_3', // Digital Marketing Pro
                'assignee' => 'demo', // Web Development
                'category' => 'sub7', // Content Creation
            ],
            [
                'name' => 'Mobile Game Development',
                'status' => ProjectStatus::PLANNING,
                'description' => 'Casual mobile game with social features',
                'client' => 'company_4', // Mobile Innovations Inc
                'assignee' => 'developer', // Mobile Development
                'category' => 'sub2', // Mobile Development
            ],
            [
                'name' => 'ERP System Integration',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Integration of existing systems with new ERP solution',
                'client' => 'company_0', // Acme Corporation
                'assignee' => 'admin', // Business Services
                'category' => 'sub3', // Software Solutions
            ],
        ];

        foreach ($projectsData as $projectData) {
            $client = $this->companies[$projectData['client']] ?? null;
            $assignee = $this->users[$projectData['assignee']] ?? null;
            $category = $this->categories[$projectData['category']] ?? null;

            $project = (new Project())
                ->setName($projectData['name'])
                ->setStatus($projectData['status'])
                ->setDescription($projectData['description'])
                ->setClient($client)
                ->setAssignee($assignee)
                ->setCategory($category);

            $manager->persist($project);
        }

        $manager->flush();
    }

    private function createThreadFixtures(ObjectManager $manager): void
    {
        // Choose available enum cases for resourceType safely
        $cases = method_exists(SystemEntityEnum::class, 'cases') ? SystemEntityEnum::cases() : [];

        if (empty($cases)) {
            // No enum cases available; skip creating threads to avoid runtime errors
            return;
        }

        // Create a few threads, cycling over available enum cases if needed
        $count = min(3, max(1, count($cases)));
        for ($i = 1; $i <= $count; $i++) {
            $resourceType = $cases[($i - 1) % count($cases)];
            $thread = (new Thread())
                ->setResourceType($resourceType)
                ->setResourceId(sprintf('resource_%d', $i))
                ->setTitle(sprintf('Discussion %d', $i));

            $manager->persist($thread);
            $this->threads["t{$i}"] = $thread;
        }

        $manager->flush();
    }

    private function createCommentFixtures(ObjectManager $manager): void
    {
        if (empty($this->threads) || empty($this->users)) {
            return;
        }

        $userKeys = array_keys($this->users);
        $uCount = count($userKeys);
        if ($uCount === 0) {
            return;
        }

        $i = 0;
        foreach ($this->threads as $tKey => $thread) {
            // Create two comments per thread
            for ($c = 1; $c <= 2; $c++) {
                $authorKey = $userKeys[$i % $uCount];
                $author = $this->users[$authorKey];

                $comment = (new Comment())
                    ->setThread($thread)
                    ->setAuthor($author)
                    ->setContent(sprintf('Sample comment %d on %s by %s', $c, $thread->getTitle() ?? $tKey, $author->getEmail()));

                $manager->persist($comment);
                $this->comments[$tKey . "_c{$c}"] = $comment;
                $i++;
            }
        }

        $manager->flush();
    }

    private function createVoteFixtures(ObjectManager $manager): void
    {
        if (empty($this->comments) || empty($this->users)) {
            return;
        }

        $userKeys = array_keys($this->users);
        $uCount = count($userKeys);
        if ($uCount === 0) {
            return;
        }

        $idx = 0;
        foreach ($this->comments as $ckey => $comment) {
            // Two votes per comment from distinct users, not the author
            $created = 0;
            $tried = 0;
            while ($created < 2 && $tried < $uCount * 2) {
                $voterKey = $userKeys[$idx % $uCount];
                $idx++;
                $tried++;

                $voter = $this->users[$voterKey];
                if ($comment->getAuthor() && $voter->getId() === $comment->getAuthor()->getId()) {
                    continue;
                }

                $vote = (new Vote())
                    ->setComment($comment)
                    ->setVoter($voter)
                    ->setValue($created === 0 ? 1 : -1);

                $manager->persist($vote);
                $created++;
            }
        }

        $manager->flush();
    }
}
