<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Company;
use App\Entity\Contact;
use App\Entity\Project;
use App\Entity\SystemEntity;
use App\Entity\User;
use App\Entity\UserSystemEntityPermission;
use App\Enum\ProjectStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const DEFAULT_PASSWORD = 'pass_1234';

    // References for later used entities
    private array $users = [];
    private array $systemEntities = [];
    private array $categories = [];
    private array $companies = [];
    private array $contacts = [];

    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Order is important for dependencies
        $this->createSystemEntityFixtures($manager);
        $this->createCategoryFixtures($manager);
        $this->createUserFixtures($manager);
        $this->createPermissionFixtures($manager);
        $this->createCompanyFixtures($manager);
        $this->createContactFixtures($manager);
        $this->createProjectFixtures($manager);
    }

    private function createSystemEntityFixtures(ObjectManager $manager): void
    {
        $systemEntitiesData = [
            'SystemEntity' => [
                'name' => 'SystemEntities',
                'text' => 'System entities and configuration',
                'icon' => 'fas fa-list',
            ],
            'User' => [
                'name' => 'Users',
                'text' => 'User management',
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
                'text' => 'Ansprechpartner etc.',
                'icon' => 'fas fa-users',
            ],
            'Project' => [
                'name' => 'Projects',
                'text' => 'Manage projects',
                'icon' => 'fas fa-project-diagram',
            ],
            'Category' => [
                'name' => 'Category',
                'text' => 'Manage categories',
                'icon' => 'fas fa-tags',
            ],
        ];

        foreach ($systemEntitiesData as $code => $data) {
            $systemEntity = (new SystemEntity())
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

    private function createUserFixtures(ObjectManager $manager): void
    {
        $usersData = [
            'admin' => [
                'email' => 'admin@example.com',
                'roles' => ['ROLE_ADMIN'],
                'active' => true,
                'notes' => 'Administrator user with full access',
                'category' => 'main2', // Business Services
            ],
            'demo' => [
                'email' => 'demo@example.com',
                'roles' => ['ROLE_USER'],
                'active' => true,
                'notes' => 'Demo user with limited access',
                'category' => 'sub1', // Web Development
            ],
            'developer' => [
                'email' => 'dev@example.com',
                'roles' => ['ROLE_USER'],
                'active' => true,
                'notes' => 'Senior developer specializing in mobile apps',
                'category' => 'sub2', // Mobile Development
            ],
            'marketing' => [
                'email' => 'marketing@example.com',
                'roles' => ['ROLE_USER'],
                'active' => true,
                'notes' => 'Marketing specialist for digital campaigns',
                'category' => 'sub6', // Digital Marketing
            ],
            'consultant' => [
                'email' => 'consultant@example.com',
                'roles' => ['ROLE_USER'],
                'active' => true,
                'notes' => 'Business consultant for process optimization',
                'category' => 'main4', // Consulting
            ],
        ];

        foreach ($usersData as $key => $userData) {
            $category = $this->categories[$userData['category']] ?? null;

            $user = new User();
            $user->setEmail($userData['email'])
                ->setPassword($this->hasher->hashPassword($user, self::DEFAULT_PASSWORD))
                ->setRoles($userData['roles'])
                ->setActive($userData['active'])
                ->setNotes($userData['notes'])
                ->setCategory($category);

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
            'Company' => ['read' => true, 'write' => true],
            'SystemEntity' => ['read' => true, 'write' => true],
            'CompanyGroup' => ['read' => true, 'write' => true],
            'Project' => ['read' => true, 'write' => true],
            'Category' => ['read' => true, 'write' => true],
        ];

        // Permissions for demo user (limited rights)
        $demoPermissions = [
            'User' => ['read' => true, 'write' => false],
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
            'Company' => ['read' => true, 'write' => true],
            'Project' => ['read' => true, 'write' => true],
        ];

        // Permissions for consultant (Business Consulting)
        $consultantPermissions = [
            'User' => ['read' => true, 'write' => false],
            'Company' => ['read' => true, 'write' => true],
            'Project' => ['read' => true, 'write' => true],
            'CompanyGroup' => ['read' => true, 'write' => false],
        ];

        $this->createUserPermissions($manager, $this->users['admin'], $adminPermissions);
        $this->createUserPermissions($manager, $this->users['demo'], $demoPermissions);
        $this->createUserPermissions($manager, $this->users['developer'], $developerPermissions);
        $this->createUserPermissions($manager, $this->users['marketing'], $marketingPermissions);
        $this->createUserPermissions($manager, $this->users['consultant'], $consultantPermissions);

        $manager->flush();
    }

    private function createUserPermissions(ObjectManager $manager, User $user, array $permissions): void
    {
        foreach ($permissions as $entityCode => $rights) {
            $systemEntity = $this->systemEntities[$entityCode] ?? null;
            if (!$systemEntity) {
                continue;
            }

            // Check if permission already exists
            $existingPermission = $manager->getRepository(UserSystemEntityPermission::class)
                ->findOneBy(['user' => $user, 'systemEntity' => $systemEntity]);

            if (!$existingPermission) {
                $permission = (new UserSystemEntityPermission())
                    ->setUser($user)
                    ->setSystemEntity($systemEntity)
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
}
