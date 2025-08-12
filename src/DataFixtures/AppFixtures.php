<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Company;
use App\Entity\CompanyGroup;
use App\Entity\Contact;
use App\Entity\Project;
use App\Entity\DomainEntityPermission;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Entity\UserGroupDomainEntityPermission;
use App\Enum\ProjectStatus;
use App\Repository\UserGroupRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const DEFAULT_PASSWORD = 'pass_1234';
    private array $users = [];
    private array $userGroups = [];
    private array $domainEntityPermission = [];
    private array $categories = [];
    private array $companies = [];
    private array $contacts = [];
    private array $companyGroups = [];

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly UserGroupRepository $userGroupRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Order is important for dependencies
        $this->createDomainEntityFixtures($manager);
        $this->createCategoryFixtures($manager);
        $this->createUserGroupFixtures($manager);
        $this->createUserFixtures($manager);
        $this->createPermissionFixtures($manager);
        $this->createCompanyGroupFixtures($manager); // NEW: groups before companies
        $this->createCompanyFixtures($manager);
        $this->createContactFixtures($manager);
        $this->createProjectFixtures($manager);
    }

    private function createDomainEntityFixtures(ObjectManager $manager): void
    {
        $domainEntityData = [
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

        foreach ($domainEntityData as $code => $data) {
            $domainEntityPermission = (new DomainEntityPermission())
                ->setName($data['name'])
                ->setCode($code)
                ->setText($data['text'])
                ->setIcon($data['icon']);

            $manager->persist($domainEntityPermission);
            $this->domainEntityPermission[$code] = $domainEntityPermission;
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
                'roles' => ['ROLE_TEAMLEAD', 'ROLE_FINANCE', 'ROLE_QUALITY', 'ROLE_PROJECT_MANAGEMENT'],
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
                'category' => 'main2',
                'nameLast' => 'Admin',
                'nameFirst' => 'User',
                'userGroups' => ['Admin'],
            ],
            'editor' => [
                'email' => 'editor@example.com',
                'active' => true,
                'notes' => 'Demo user with limited access',
                'category' => 'sub1',
                'nameLast' => 'Demo',
                'nameFirst' => 'User',
                'userGroups' => ['Editor'],
            ],
            'teamlead' => [
                'email' => 'teamlead@example.com',
                'active' => true,
                'notes' => 'Senior developer specializing in mobile apps',
                'category' => 'sub2',
                'nameLast' => 'Developer',
                'nameFirst' => 'User',
                'userGroups' => ['Teamlead'],
                'roles' => ['ROLE_QUALITY'],
            ],
            'manager' => [
                'email' => 'marketing@example.com',
                'active' => true,
                'notes' => 'Marketing specialist for digital campaigns',
                'category' => 'sub6',
                'nameLast' => 'Marketing',
                'nameFirst' => 'User',
                'userGroups' => ['Manager'],
            ],
            'external' => [
                'email' => 'external@example.com',
                'active' => true,
                'notes' => 'Business consultant for process optimization',
                'category' => 'main4',
                'nameLast' => 'Consultant',
                'nameFirst' => 'User',
                'userGroups' => ['External Users'],
            ],
            'demo' => [
                'email' => 'demo@example.com',
                'active' => true,
                'notes' => 'Demo user with limited access',
                'category' => 'sub1',
                'nameLast' => 'Demo',
                'nameFirst' => 'User',
                'userGroups' => ['Editor'],
            ],
        ];

        foreach ($usersData as $key => $userData) {
            $category = $this->categories[$userData['category']] ?? null;
            $userGroups = $this->userGroupRepository->findBy(['name' => $userData['userGroups']]);

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
                ->setRoles($userData['roles'] ?? [])
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
        $adminPermissions = [
            'User' => ['read' => true, 'write' => true],
            'UserGroup' => ['read' => true, 'write' => true],
            'Company' => ['read' => true, 'write' => true],
            'DomainEntityPermission' => ['read' => true, 'write' => true],
            'CompanyGroup' => ['read' => true, 'write' => true],
            'Project' => ['read' => true, 'write' => true],
            'Category' => ['read' => true, 'write' => true],
        ];

        $demoPermissions = [
            'User' => ['read' => true, 'write' => false],
            'UserGroup' => ['read' => true, 'write' => false],
            'Company' => ['read' => true, 'write' => true],
            'Project' => ['read' => true, 'write' => false],
            'Category' => ['read' => true, 'write' => true],
        ];

        $developerPermissions = [
            'User' => ['read' => true, 'write' => false],
            'Company' => ['read' => true, 'write' => false],
            'Project' => ['read' => true, 'write' => true],
        ];

        $marketingPermissions = [
            'User' => ['read' => true, 'write' => false],
            'UserGroup' => ['read' => true, 'write' => false],
            'Company' => ['read' => true, 'write' => true],
            'Project' => ['read' => true, 'write' => true],
        ];

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
            $domainEntityPermission = $this->domainEntityPermission[$entityCode] ?? null;
            if (!$domainEntityPermission) {
                continue;
            }

            $existingPermission = $manager->getRepository(UserGroupDomainEntityPermission::class)
                ->findOneBy(['userGroup' => $userGroup, 'domainEntityPermission' => $domainEntityPermission]);

            if (!$existingPermission) {
                $permission = (new UserGroupDomainEntityPermission())
                    ->setUserGroup($userGroup)
                    ->setDomainEntityPermission($domainEntityPermission)
                    ->setCanRead($rights['read'])
                    ->setCanWrite($rights['write']);

                $manager->persist($permission);
            }
        }
    }

    private function createCompanyGroupFixtures(ObjectManager $manager): void
    {
        // Themed groups from comics and motion pictures
        $groups = [
            'skynet' => ['name' => 'Skynet Group', 'code' => 'SKYNET'],
            'marvel' => ['name' => 'Marvel Group', 'code' => 'MARVEL'],
            'dc' => ['name' => 'DC Group', 'code' => 'DC'],
            'weyland' => ['name' => 'Weyland-Yutani Group', 'code' => 'WEYLAND'],
            'umbrella' => ['name' => 'Umbrella Group', 'code' => 'UMBRELLA'],
        ];

        foreach ($groups as $key => $data) {
            $group = (new CompanyGroup())
                ->setName($data['name'])
                ->setCode($data['code']);
            $manager->persist($group);
            $this->companyGroups[$key] = $group;
        }

        $manager->flush();
    }

    private function createCompanyFixtures(ObjectManager $manager): void
    {
        // Companies themed from comics/movies, renamed to start with "Macht Group - ..."
        $companiesData = [
            [
                'display' => 'Cyberdyne Systems',
                'email' => 'contact@cyberdyne.example',
                'country' => 'US',
                'category' => 'main1', // Technology
                'phone' => '+1 555 0100',
                'url' => 'https://cyberdyne.example',
                'street' => '101 Skynet Blvd',
                'city' => 'Los Angeles',
                'zipCode' => '90001',
                'group' => 'skynet',
            ],
            [
                'display' => 'Stark Industries',
                'email' => 'info@stark.example',
                'country' => 'US',
                'category' => 'sub3', // Software Solutions
                'phone' => '+1 555 0101',
                'url' => 'https://stark.example',
                'street' => '1 Avengers Tower',
                'city' => 'New York',
                'zipCode' => '10001',
                'group' => 'marvel',
            ],
            [
                'display' => 'Wayne Enterprises',
                'email' => 'hello@wayne.example',
                'country' => 'US',
                'category' => 'main2', // Business Services
                'phone' => '+1 555 0102',
                'url' => 'https://wayne.example',
                'street' => '1007 Mountain Drive',
                'city' => 'Gotham',
                'zipCode' => '07001',
                'group' => 'dc',
            ],
            [
                'display' => 'Oscorp',
                'email' => 'contact@oscorp.example',
                'country' => 'US',
                'category' => 'sub1', // Web Development (as a placeholder tech category)
                'phone' => '+1 555 0103',
                'url' => 'https://oscorp.example',
                'street' => '500 Spider Ave',
                'city' => 'New York',
                'zipCode' => '10002',
                'group' => 'marvel',
            ],
            [
                'display' => 'Weyland-Yutani',
                'email' => 'corp@weyland.example',
                'country' => 'UK',
                'category' => 'main4', // Consulting (placeholder)
                'phone' => '+44 20 7946 0000',
                'url' => 'https://weyland.example',
                'street' => '1 Offworld Park',
                'city' => 'London',
                'zipCode' => 'SW1A 1AA',
                'group' => 'weyland',
            ],
            [
                'display' => 'Umbrella Corporation',
                'email' => 'hq@umbrella.example',
                'country' => 'DE',
                'category' => 'main3', // Marketing & Sales (placeholder)
                'phone' => '+49 30 123456',
                'url' => 'https://umbrella.example',
                'street' => '13 Hive Str.',
                'city' => 'Raccoon City',
                'zipCode' => '10117',
                'group' => 'umbrella',
            ],
        ];

        foreach ($companiesData as $index => $data) {
            $category = $this->categories[$data['category']] ?? null;
            $group = $this->companyGroups[$data['group']] ?? null;

            $company = (new Company())
                ->setName($data['display'])
                ->setEmail($data['email'])
                ->setCountryCode($data['country'])
                ->setCategory($category)
                ->setPhone($data['phone'])
                ->setUrl($data['url'])
                ->setStreet($data['street'])
                ->setCity($data['city'])
                ->setZip($data['zipCode'])
                ->setCompanyGroup($group);

            $manager->persist($company);
            $this->companies["company_{$index}"] = $company;
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
            $this->contacts["contact_{$index}"] = $contact;
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
                'client' => 'company_0', // Macht Group - Cyberdyne Systems
                'assignee' => 'demo',
                'category' => 'sub1',
            ],
            [
                'name' => 'Mobile Banking App',
                'status' => ProjectStatus::PLANNING,
                'description' => 'Secure mobile banking application with biometric authentication',
                'client' => 'company_1', // Macht Group - Stark Industries
                'assignee' => 'teamlead',
                'category' => 'sub2',
            ],
            [
                'name' => 'Digital Marketing Campaign',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Comprehensive digital marketing strategy implementation',
                'client' => 'company_5', // Macht Group - Umbrella Corporation
                'assignee' => 'manager',
                'category' => 'sub6',
            ],
            [
                'name' => 'Business Process Optimization',
                'status' => ProjectStatus::ON_HOLD,
                'description' => 'Analysis and optimization of business workflows',
                'client' => 'company_2', // Macht Group - Wayne Enterprises
                'assignee' => 'external',
                'category' => 'main4',
            ],
            [
                'name' => 'R&D Dashboard',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Real-time R&D analytics and reporting',
                'client' => 'company_3', // Macht Group - Oscorp
                'assignee' => 'demo',
                'category' => 'sub3',
            ],
            [
                'name' => 'Enterprise CMS',
                'status' => ProjectStatus::COMPLETED,
                'description' => 'Enterprise-grade content management solution',
                'client' => 'company_4', // Macht Group - Weyland-Yutani
                'assignee' => 'demo',
                'category' => 'sub7',
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
