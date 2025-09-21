<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Campaign;
use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\CompanyGroup;
use C3net\CoreBundle\Entity\Contact;
use C3net\CoreBundle\Entity\Project;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Entity\UserGroup;
use C3net\CoreBundle\Enum\ProjectStatus;
use C3net\CoreBundle\Repository\UserGroupRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CoreDefaultsFixtures extends Fixture
{
    private const string DEFAULT_PASSWORD = 'pass_1234';

    /** @var array<string, User> */
    protected array $users = [];

    /** @var array<string, UserGroup> */
    private array $userGroups = [];

    /** @var array<string, Category> */
    protected array $categories = [];

    /** @var array<string, Company> */
    protected array $companies = [];

    /** @var array<string, Contact> */
    private array $contacts = [];

    /** @var array<string, CompanyGroup> */
    private array $companyGroups = [];

    /** @var array<string, Campaign> */
    private array $campaigns = [];

    /** @var array<string, Project> */
    private array $projects = [];

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly UserGroupRepository $userGroupRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Order is important for dependencies
        $this->createCategoryFixtures($manager);
        $this->createUserGroupFixtures($manager);
        $this->createCompanyGroupFixtures($manager); // Create groups before companies
        $this->createCompanyFixtures($manager); // Create companies before users for proper assignment
        $this->createUserFixtures($manager);
        $this->createContactFixtures($manager);
        $this->createProjectFixtures($manager);
        $this->createCampaignFixtures($manager); // Create campaigns after projects
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
                'nameFirst' => 'System',
                'userGroups' => ['Admin'],
                'company' => null, // Admin not assigned to specific company
            ],
            'editor' => [
                'email' => 'editor@example.com',
                'active' => true,
                'notes' => 'Web developer working on e-commerce projects',
                'category' => 'sub1',
                'nameLast' => 'Wilson',
                'nameFirst' => 'Sarah',
                'userGroups' => ['Editor'],
                'company' => 'company_0', // Cyberdyne Systems
            ],
            'teamlead' => [
                'email' => 'teamlead@example.com',
                'active' => true,
                'notes' => 'Senior developer specializing in mobile apps',
                'category' => 'sub2',
                'nameLast' => 'Johnson',
                'nameFirst' => 'Michael',
                'userGroups' => ['Teamlead'],
                'roles' => ['ROLE_QUALITY'],
                'company' => 'company_1', // Stark Industries
            ],
            'manager' => [
                'email' => 'marketing@example.com',
                'active' => true,
                'notes' => 'Marketing specialist for digital campaigns',
                'category' => 'sub6',
                'nameLast' => 'Davis',
                'nameFirst' => 'Emma',
                'userGroups' => ['Manager'],
                'company' => 'company_5', // Umbrella Corporation
            ],
            'external' => [
                'email' => 'external@example.com',
                'active' => true,
                'notes' => 'Business consultant for process optimization',
                'category' => 'main4',
                'nameLast' => 'Thompson',
                'nameFirst' => 'Robert',
                'userGroups' => ['External Users'],
                'company' => 'company_2', // Wayne Enterprises
            ],
            'demo' => [
                'email' => 'demo@example.com',
                'active' => true,
                'notes' => 'Full-stack developer with React and PHP expertise',
                'category' => 'sub1',
                'nameLast' => 'Anderson',
                'nameFirst' => 'Alex',
                'userGroups' => ['Editor'],
                'company' => 'company_3', // Oscorp
            ],
            // Additional employees for the expanded companies
            'dev1' => [
                'email' => 'dev1@example.com',
                'active' => true,
                'notes' => 'Frontend specialist focusing on React and Vue.js',
                'category' => 'sub1',
                'nameLast' => 'Brown',
                'nameFirst' => 'Jessica',
                'userGroups' => ['Editor'],
                'company' => 'company_7', // Parker Industries
            ],
            'dev2' => [
                'email' => 'dev2@example.com',
                'active' => true,
                'notes' => 'Mobile app developer with iOS and Android experience',
                'category' => 'sub2',
                'nameLast' => 'Garcia',
                'nameFirst' => 'Carlos',
                'userGroups' => ['Teamlead'],
                'company' => 'company_8', // Pym Technologies
            ],
            'consultant1' => [
                'email' => 'consultant1@example.com',
                'active' => true,
                'notes' => 'Business process optimization specialist',
                'category' => 'main4',
                'nameLast' => 'Miller',
                'nameFirst' => 'Amanda',
                'userGroups' => ['Manager'],
                'company' => 'company_10', // Queen Industries
            ],
            'marketing1' => [
                'email' => 'marketing1@example.com',
                'active' => true,
                'notes' => 'Digital marketing strategist and content creator',
                'category' => 'sub6',
                'nameLast' => 'Williams',
                'nameFirst' => 'David',
                'userGroups' => ['Manager'],
                'company' => 'company_15', // Tricell Pharmaceuticals
            ],
        ];

        foreach ($usersData as $key => $userData) {
            $category = $this->categories[$userData['category']] ?? null;
            $userGroups = $this->userGroupRepository->findBy(['name' => $userData['userGroups']]);
            $company = null;

            // Get company if specified
            if (isset($userData['company'])) {
                $company = $this->companies[$userData['company']] ?? null;
            }

            if (!$category) {
                throw new \Exception(sprintf('Category "%s" not found for user "%s". Available categories: %s', $userData['category'], $key, implode(', ', array_keys($this->categories))));
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

            if ($company) {
                $user->setCompany($company);
            }

            foreach ($userGroups as $userGroup) {
                $user->addUserGroup($userGroup);
            }

            $manager->persist($user);
            $this->users[$key] = $user;
        }

        $manager->flush();
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
        // Available demo logos
        $demoLogos = [
            'images/demo-logos/atlas-square.svg',
            'images/demo-logos/aurora-square.svg',
            'images/demo-logos/bitwave-square.svg',
            'images/demo-logos/drift-square.svg',
            'images/demo-logos/echo-square.svg',
            'images/demo-logos/flux-square.svg',
            'images/demo-logos/forge-square.svg',
            'images/demo-logos/harbor-square.svg',
            'images/demo-logos/lumen-square.svg',
            'images/demo-logos/nimbus-square.svg',
            'images/demo-logos/nova-square.svg',
            'images/demo-logos/orbit-square.svg',
            'images/demo-logos/pulse-square.svg',
            'images/demo-logos/quantum-square.svg',
            'images/demo-logos/vertex-square.svg',
            'images/demo-logos/zephyr-square.svg',
        ];

        // Companies themed from comics/movies and additional diverse demo companies
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
            // Additional Skynet Group companies
            [
                'display' => 'GeneDyne Technologies',
                'email' => 'info@genedyne.example',
                'country' => 'CA',
                'category' => 'sub3', // Software Solutions
                'phone' => '+1 416 555 0200',
                'url' => 'https://genedyne.example',
                'street' => '2500 Tech Valley Dr',
                'city' => 'Toronto',
                'zipCode' => 'M5V 3A8',
                'group' => 'skynet',
            ],
            [
                'display' => 'NeuralLink Systems',
                'email' => 'contact@neurallink.example',
                'country' => 'JP',
                'category' => 'main1', // Technology
                'phone' => '+81 3 5555 0300',
                'url' => 'https://neurallink.example',
                'street' => '1-1-1 Shibuya',
                'city' => 'Tokyo',
                'zipCode' => '150-0002',
                'group' => 'skynet',
            ],
            // Additional Marvel Group companies
            [
                'display' => 'Parker Industries',
                'email' => 'hello@parker.example',
                'country' => 'US',
                'category' => 'sub2', // Mobile Development
                'phone' => '+1 555 0400',
                'url' => 'https://parker.example',
                'street' => '20 Ingram Street',
                'city' => 'New York',
                'zipCode' => '10038',
                'group' => 'marvel',
            ],
            [
                'display' => 'Pym Technologies',
                'email' => 'info@pym.example',
                'country' => 'US',
                'category' => 'sub3', // Software Solutions
                'phone' => '+1 415 555 0500',
                'url' => 'https://pym.example',
                'street' => '1955 Quantum Ave',
                'city' => 'San Francisco',
                'zipCode' => '94102',
                'group' => 'marvel',
            ],
            [
                'display' => 'Rand Corporation',
                'email' => 'contact@rand.example',
                'country' => 'US',
                'category' => 'main2', // Business Services
                'phone' => '+1 555 0600',
                'url' => 'https://rand.example',
                'street' => '200 Iron Fist Plaza',
                'city' => 'New York',
                'zipCode' => '10013',
                'group' => 'marvel',
            ],
            // Additional DC Group companies
            [
                'display' => 'Queen Industries',
                'email' => 'admin@queen.example',
                'country' => 'US',
                'category' => 'sub4', // Financial Services
                'phone' => '+1 206 555 0700',
                'url' => 'https://queen.example',
                'street' => '1701 Green Arrow Way',
                'city' => 'Star City',
                'zipCode' => '98101',
                'group' => 'dc',
            ],
            [
                'display' => 'LexCorp',
                'email' => 'info@lexcorp.example',
                'country' => 'US',
                'category' => 'main4', // Consulting
                'phone' => '+1 555 0800',
                'url' => 'https://lexcorp.example',
                'street' => '1000 LexCorp Plaza',
                'city' => 'Metropolis',
                'zipCode' => '10001',
                'group' => 'dc',
            ],
            [
                'display' => 'Kord Industries',
                'email' => 'hello@kord.example',
                'country' => 'US',
                'category' => 'sub1', // Web Development
                'phone' => '+1 773 555 0900',
                'url' => 'https://kord.example',
                'street' => '42 Beetle Drive',
                'city' => 'Chicago',
                'zipCode' => '60601',
                'group' => 'dc',
            ],
            // Additional Weyland Group companies
            [
                'display' => 'Tyrell Corporation',
                'email' => 'corp@tyrell.example',
                'country' => 'US',
                'category' => 'main1', // Technology
                'phone' => '+1 213 555 1000',
                'url' => 'https://tyrell.example',
                'street' => '2019 Replicant Blvd',
                'city' => 'Los Angeles',
                'zipCode' => '90028',
                'group' => 'weyland',
            ],
            [
                'display' => 'Seegson Corporation',
                'email' => 'contact@seegson.example',
                'country' => 'FR',
                'category' => 'sub5', // Legal Services
                'phone' => '+33 1 55 55 1100',
                'url' => 'https://seegson.example',
                'street' => '77 Rue de la Paix',
                'city' => 'Paris',
                'zipCode' => '75001',
                'group' => 'weyland',
            ],
            // Additional Umbrella Group companies
            [
                'display' => 'Tricell Pharmaceuticals',
                'email' => 'info@tricell.example',
                'country' => 'ZA',
                'category' => 'sub6', // Digital Marketing
                'phone' => '+27 11 555 1200',
                'url' => 'https://tricell.example',
                'street' => '15 Kijuju Business Park',
                'city' => 'Johannesburg',
                'zipCode' => '2000',
                'group' => 'umbrella',
            ],
            [
                'display' => 'TerraSave International',
                'email' => 'hello@terrasave.example',
                'country' => 'AU',
                'category' => 'sub7', // Content Creation
                'phone' => '+61 2 5555 1300',
                'url' => 'https://terrasave.example',
                'street' => '88 Resident Way',
                'city' => 'Sydney',
                'zipCode' => '2000',
                'group' => 'umbrella',
            ],
            [
                'display' => 'Blue Umbrella Ltd',
                'email' => 'contact@blueumbrella.example',
                'country' => 'GB',
                'category' => 'main3', // Marketing & Sales
                'phone' => '+44 20 7555 1400',
                'url' => 'https://blueumbrella.example',
                'street' => '10 Downing Street',
                'city' => 'London',
                'zipCode' => 'SW1A 2AA',
                'group' => 'umbrella',
            ],
        ];

        foreach ($companiesData as $index => $data) {
            $category = $this->categories[$data['category']] ?? null;
            $group = $this->companyGroups[$data['group']] ?? null;

            // Randomly assign a demo logo
            $randomLogo = $demoLogos[array_rand($demoLogos)];

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
                ->setCompanyGroup($group)
                ->setImagePath($randomLogo);

            $manager->persist($company);
            $this->companies['company_' . $index] = $company;
        }

        $manager->flush();
    }

    private function createContactFixtures(ObjectManager $manager): void
    {
        $contactsData = [
            // 3-level hierarchical contacts within companies

            // COMPANY 0 (Cyberdyne Systems) - Technology Hierarchy
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'john.doe@cyberdyne.example',
                'phone' => '+1 555 1001',
                'cell' => '+1 555 2001',
                'company' => 'company_0',
                'academicTitle' => null,
                'position' => 'Chief Executive Officer',
                'department' => 'Executive',
                'hierarchy_level' => 1,
                'parent_key' => null,
            ],
            [
                'firstName' => 'Michael',
                'lastName' => 'Brown',
                'email' => 'michael.brown@cyberdyne.example',
                'phone' => '+1 555 1004',
                'cell' => '+1 555 2004',
                'company' => 'company_0',
                'academicTitle' => null,
                'position' => 'Chief Technology Officer',
                'department' => 'Technology',
                'hierarchy_level' => 2,
                'parent_key' => 'contact_0', // John Doe
            ],
            [
                'firstName' => 'Sarah',
                'lastName' => 'Connor',
                'email' => 'sarah.connor@cyberdyne.example',
                'phone' => '+1 555 1024',
                'cell' => '+1 555 2024',
                'company' => 'company_0',
                'academicTitle' => 'Ms.',
                'position' => 'VP of Operations',
                'department' => 'Operations',
                'hierarchy_level' => 2,
                'parent_key' => 'contact_0', // John Doe
            ],
            [
                'firstName' => 'Kyle',
                'lastName' => 'Reese',
                'email' => 'kyle.reese@cyberdyne.example',
                'phone' => '+1 555 1025',
                'cell' => '+1 555 2025',
                'company' => 'company_0',
                'academicTitle' => null,
                'position' => 'Lead Software Engineer',
                'department' => 'Technology',
                'hierarchy_level' => 3,
                'parent_key' => 'contact_1', // Michael Brown
            ],
            [
                'firstName' => 'Miles',
                'lastName' => 'Dyson',
                'email' => 'miles.dyson@cyberdyne.example',
                'phone' => '+1 555 1026',
                'cell' => '+1 555 2026',
                'company' => 'company_0',
                'academicTitle' => 'Dr.',
                'position' => 'Senior Research Manager',
                'department' => 'Technology',
                'hierarchy_level' => 3,
                'parent_key' => 'contact_1', // Michael Brown
            ],
            [
                'firstName' => 'Catherine',
                'lastName' => 'Brewster',
                'email' => 'catherine.brewster@cyberdyne.example',
                'phone' => '+1 555 1027',
                'cell' => '+1 555 2027',
                'company' => 'company_0',
                'academicTitle' => 'Dr.',
                'position' => 'Operations Manager',
                'department' => 'Operations',
                'hierarchy_level' => 3,
                'parent_key' => 'contact_2', // Sarah Connor
            ],

            // COMPANY 1 (Stark Industries) - Innovation Hierarchy
            [
                'firstName' => 'Jane',
                'lastName' => 'Smith',
                'email' => 'jane.smith@stark.example',
                'phone' => '+1 555 1002',
                'cell' => '+1 555 2002',
                'company' => 'company_1',
                'academicTitle' => 'Ms.',
                'position' => 'Chief Executive Officer',
                'department' => 'Executive',
                'hierarchy_level' => 1,
                'parent_key' => null,
            ],
            [
                'firstName' => 'Tony',
                'lastName' => 'Stark',
                'email' => 'tony.stark@stark.example',
                'phone' => '+1 555 1005',
                'cell' => '+1 555 2005',
                'company' => 'company_1',
                'academicTitle' => 'Dr.',
                'position' => 'Chief Innovation Officer',
                'department' => 'Research & Development',
                'hierarchy_level' => 2,
                'parent_key' => 'contact_6', // Jane Smith
            ],
            [
                'firstName' => 'Pepper',
                'lastName' => 'Potts',
                'email' => 'pepper.potts@stark.example',
                'phone' => '+1 555 1028',
                'cell' => '+1 555 2028',
                'company' => 'company_1',
                'academicTitle' => 'Ms.',
                'position' => 'Chief Operating Officer',
                'department' => 'Operations',
                'hierarchy_level' => 2,
                'parent_key' => 'contact_6', // Jane Smith
            ],
            [
                'firstName' => 'James',
                'lastName' => 'Rhodes',
                'email' => 'james.rhodes@stark.example',
                'phone' => '+1 555 1029',
                'cell' => '+1 555 2029',
                'company' => 'company_1',
                'academicTitle' => 'Col.',
                'position' => 'Senior Engineering Manager',
                'department' => 'Research & Development',
                'hierarchy_level' => 3,
                'parent_key' => 'contact_7', // Tony Stark
            ],
            [
                'firstName' => 'Bruce',
                'lastName' => 'Banner',
                'email' => 'bruce.banner@stark.example',
                'phone' => '+1 555 1030',
                'cell' => '+1 555 2030',
                'company' => 'company_1',
                'academicTitle' => 'Dr.',
                'position' => 'Lead Research Scientist',
                'department' => 'Research & Development',
                'hierarchy_level' => 3,
                'parent_key' => 'contact_7', // Tony Stark
            ],
            [
                'firstName' => 'Happy',
                'lastName' => 'Hogan',
                'email' => 'happy.hogan@stark.example',
                'phone' => '+1 555 1031',
                'cell' => '+1 555 2031',
                'company' => 'company_1',
                'academicTitle' => null,
                'position' => 'Operations Team Lead',
                'department' => 'Operations',
                'hierarchy_level' => 3,
                'parent_key' => 'contact_8', // Pepper Potts
            ],

            // COMPANY 2 (Wayne Enterprises) - Security & Finance Hierarchy
            [
                'firstName' => 'Alice',
                'lastName' => 'Johnson',
                'email' => 'alice.johnson@wayne.example',
                'phone' => '+1 555 1003',
                'cell' => '+1 555 2003',
                'company' => 'company_2',
                'academicTitle' => 'Dr.',
                'position' => 'Chief Executive Officer',
                'department' => 'Executive',
                'hierarchy_level' => 1,
                'parent_key' => null,
            ],
            [
                'firstName' => 'Bruce',
                'lastName' => 'Wayne',
                'email' => 'bruce.wayne@wayne.example',
                'phone' => '+1 555 1006',
                'cell' => '+1 555 2006',
                'company' => 'company_2',
                'academicTitle' => 'Mr.',
                'position' => 'Chairman of the Board',
                'department' => 'Executive',
                'hierarchy_level' => 2,
                'parent_key' => 'contact_12', // Alice Johnson
            ],
            [
                'firstName' => 'Lucius',
                'lastName' => 'Fox',
                'email' => 'lucius.fox@wayne.example',
                'phone' => '+1 555 1032',
                'cell' => '+1 555 2032',
                'company' => 'company_2',
                'academicTitle' => 'Mr.',
                'position' => 'Chief Technology Officer',
                'department' => 'Technology',
                'hierarchy_level' => 2,
                'parent_key' => 'contact_12', // Alice Johnson
            ],
            [
                'firstName' => 'Alfred',
                'lastName' => 'Pennyworth',
                'email' => 'alfred.pennyworth@wayne.example',
                'phone' => '+1 555 1033',
                'cell' => '+1 555 2033',
                'company' => 'company_2',
                'academicTitle' => 'Mr.',
                'position' => 'Executive Assistant Manager',
                'department' => 'Executive',
                'hierarchy_level' => 3,
                'parent_key' => 'contact_13', // Bruce Wayne
            ],
            [
                'firstName' => 'Barbara',
                'lastName' => 'Gordon',
                'email' => 'barbara.gordon@wayne.example',
                'phone' => '+1 555 1034',
                'cell' => '+1 555 2034',
                'company' => 'company_2',
                'academicTitle' => 'Ms.',
                'position' => 'IT Security Manager',
                'department' => 'Technology',
                'hierarchy_level' => 3,
                'parent_key' => 'contact_14', // Lucius Fox
            ],
            [
                'firstName' => 'Harvey',
                'lastName' => 'Dent',
                'email' => 'harvey.dent@wayne.example',
                'phone' => '+1 555 1035',
                'cell' => '+1 555 2035',
                'company' => 'company_2',
                'academicTitle' => 'Mr.',
                'position' => 'Legal Affairs Manager',
                'department' => 'Legal',
                'hierarchy_level' => 3,
                'parent_key' => 'contact_14', // Lucius Fox
            ],


            // Additional flat contacts for other companies
            [
                'firstName' => 'Emma',
                'lastName' => 'Martinez',
                'email' => 'emma.martinez@oscorp.example',
                'phone' => '+1 555 1007',
                'cell' => '+1 555 2007',
                'company' => 'company_3',
                'academicTitle' => null,
                'position' => 'Systems Architect',
                'department' => 'Engineering',
            ],
        ];

        // Create contacts in two passes to support hierarchy
        // Pass 1: Create all contacts without parent relationships
        foreach ($contactsData as $index => $contactData) {
            $company = $this->companies[$contactData['company']] ?? null;

            $contact = (new Contact())
                ->setNameFirst($contactData['firstName'])
                ->setNameLast($contactData['lastName'])
                ->setEmail($contactData['email'])
                ->setPhone($contactData['phone'])
                ->setCell($contactData['cell'])
                ->setCompany($company);

            if (isset($contactData['academicTitle'])) {
                $contact->setAcademicTitle($contactData['academicTitle']);
            }

            if (isset($contactData['position'])) {
                $contact->setPosition($contactData['position']);
            }

            if (isset($contactData['department'])) {
                $contact->setDepartment($contactData['department']);
            }

            $manager->persist($contact);
            $this->contacts['contact_' . $index] = $contact;
        }

        $manager->flush();

        // Pass 2: Set up parent-child relationships for hierarchy
        foreach ($contactsData as $index => $contactData) {
            if (isset($contactData['parent_key']) && $contactData['parent_key']) {
                $contact = $this->contacts['contact_' . $index];
                $parent = $this->contacts[$contactData['parent_key']] ?? null;

                if ($parent) {
                    $contact->setParent($parent);
                    $manager->persist($contact);
                }
            }
        }

        $manager->flush();
    }

    private function createProjectFixtures(ObjectManager $manager): void
    {
        $projectsData = [
            // Cyberdyne Systems - Multiple projects (company_0)
            [
                'name' => 'E-Commerce Platform',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Modern e-commerce platform with advanced features',
                'client' => 'company_0',
                'assignee' => 'editor', // Sarah Wilson (employee of Cyberdyne)
                'category' => 'sub1',
                'dueDate' => new \DateTimeImmutable('Monday this week 9:00'),
            ],
            [
                'name' => 'AI Security System',
                'status' => ProjectStatus::PLANNING,
                'description' => 'Advanced AI-powered security and surveillance system',
                'client' => 'company_0',
                'assignee' => 'editor',
                'category' => 'main1',
                'dueDate' => new \DateTimeImmutable('Monday this week 14:30'),
            ],
            [
                'name' => 'Automated Defense Network',
                'status' => ProjectStatus::COMPLETED,
                'description' => 'Fully automated defense and monitoring network',
                'client' => 'company_0',
                'assignee' => 'admin',
                'category' => 'sub3',
                'dueDate' => new \DateTimeImmutable('Tuesday this week 10:15'),
            ],

            // Stark Industries - Multiple projects (company_1)
            [
                'name' => 'Mobile Banking App',
                'status' => ProjectStatus::PLANNING,
                'description' => 'Secure mobile banking application with biometric authentication',
                'client' => 'company_1',
                'assignee' => 'teamlead', // Michael Johnson (employee of Stark)
                'category' => 'sub2',
                'dueDate' => new \DateTimeImmutable('Tuesday this week 16:00'),
            ],
            [
                'name' => 'Arc Reactor Monitoring',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Real-time monitoring system for arc reactor technology',
                'client' => 'company_1',
                'assignee' => 'teamlead',
                'category' => 'sub3',
                'dueDate' => new \DateTimeImmutable('Wednesday this week 11:45'),
            ],

            // Wayne Enterprises - Multiple projects (company_2)
            [
                'name' => 'Business Process Optimization',
                'status' => ProjectStatus::ON_HOLD,
                'description' => 'Analysis and optimization of business workflows',
                'client' => 'company_2',
                'assignee' => 'external', // Robert Thompson (employee of Wayne)
                'category' => 'main4',
                'dueDate' => new \DateTimeImmutable('Wednesday this week 15:20'),
            ],
            [
                'name' => 'Corporate Security Upgrade',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Enterprise-wide security system upgrade',
                'client' => 'company_2',
                'assignee' => 'external',
                'category' => 'main2',
                'dueDate' => new \DateTimeImmutable('Thursday this week 9:30'),
            ],
            [
                'name' => 'Financial Portfolio Management',
                'status' => ProjectStatus::COMPLETED,
                'description' => 'Advanced portfolio management and analysis system',
                'client' => 'company_2',
                'assignee' => 'consultant1',
                'category' => 'sub4',
                'dueDate' => new \DateTimeImmutable('Thursday this week 13:10'),
            ],

            // Oscorp - Multiple projects (company_3)
            [
                'name' => 'R&D Dashboard',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Real-time R&D analytics and reporting',
                'client' => 'company_3',
                'assignee' => 'demo', // Alex Anderson (employee of Oscorp)
                'category' => 'sub3',
                'dueDate' => new \DateTimeImmutable('Thursday this week 16:45'),
            ],
            [
                'name' => 'Scientific Data Analysis',
                'status' => ProjectStatus::PLANNING,
                'description' => 'Advanced data analysis platform for scientific research',
                'client' => 'company_3',
                'assignee' => 'demo',
                'category' => 'main1',
                'dueDate' => new \DateTimeImmutable('Friday this week 10:00'),
            ],

            // Weyland-Yutani (company_4)
            [
                'name' => 'Enterprise CMS',
                'status' => ProjectStatus::COMPLETED,
                'description' => 'Enterprise-grade content management solution',
                'client' => 'company_4',
                'assignee' => 'admin',
                'category' => 'sub7',
                'dueDate' => new \DateTimeImmutable('Friday this week 14:15'),
            ],

            // Umbrella Corporation - Multiple projects (company_5)
            [
                'name' => 'Digital Marketing Campaign',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Comprehensive digital marketing strategy implementation',
                'client' => 'company_5',
                'assignee' => 'manager', // Emma Davis (employee of Umbrella)
                'category' => 'sub6',
                'dueDate' => new \DateTimeImmutable('Friday this week 17:00'),
            ],
            [
                'name' => 'Pharmaceutical Research Portal',
                'status' => ProjectStatus::PLANNING,
                'description' => 'Web portal for pharmaceutical research and development',
                'client' => 'company_5',
                'assignee' => 'manager',
                'category' => 'sub1',
                'dueDate' => new \DateTimeImmutable('Monday next week 9:15'),
            ],
            [
                'name' => 'Global Distribution Network',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Worldwide distribution and logistics management system',
                'client' => 'company_5',
                'assignee' => 'marketing1',
                'category' => 'main3',
                'dueDate' => new \DateTimeImmutable('Monday next week 12:30'),
            ],

            // GeneDyne Technologies (company_6)
            [
                'name' => 'Neural Interface Development',
                'status' => ProjectStatus::PLANNING,
                'description' => 'Advanced AI-driven neural interface system',
                'client' => 'company_6',
                'assignee' => 'teamlead',
                'category' => 'main1',
                'dueDate' => new \DateTimeImmutable('Monday next week 15:45'),
            ],

            // NeuralLink Systems (company_7)
            [
                'name' => 'Quantum Computing Research',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Research and development of quantum computing solutions',
                'client' => 'company_7',
                'assignee' => 'admin',
                'category' => 'sub3',
                'dueDate' => new \DateTimeImmutable('Tuesday next week 10:20'),
            ],

            // Parker Industries - Multiple projects (company_8)
            [
                'name' => 'Mobile Commerce App',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Cross-platform mobile commerce application',
                'client' => 'company_8',
                'assignee' => 'dev1', // Jessica Brown (employee of Parker)
                'category' => 'sub2',
                'dueDate' => new \DateTimeImmutable('Tuesday next week 13:50'),
            ],
            [
                'name' => 'Web Crawler Technology',
                'status' => ProjectStatus::COMPLETED,
                'description' => 'Advanced web crawling and indexing system',
                'client' => 'company_8',
                'assignee' => 'dev1',
                'category' => 'sub1',
                'dueDate' => new \DateTimeImmutable('Tuesday next week 16:25'),
            ],

            // Pym Technologies - Multiple projects (company_9)
            [
                'name' => 'Microservices Architecture',
                'status' => ProjectStatus::PLANNING,
                'description' => 'Migration to microservices architecture',
                'client' => 'company_9',
                'assignee' => 'dev2', // Carlos Garcia (employee of Pym)
                'category' => 'sub3',
                'dueDate' => new \DateTimeImmutable('Wednesday next week 9:40'),
            ],
            [
                'name' => 'Quantum Realm Analytics',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Quantum-level data analysis and processing system',
                'client' => 'company_9',
                'assignee' => 'dev2',
                'category' => 'main1',
                'dueDate' => new \DateTimeImmutable('Wednesday next week 11:55'),
            ],

            // Rand Corporation (company_10)
            [
                'name' => 'Iron Fist Training Platform',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Digital training and certification platform',
                'client' => 'company_10',
                'assignee' => 'admin',
                'category' => 'sub1',
                'dueDate' => new \DateTimeImmutable('Wednesday next week 14:10'),
            ],

            // Queen Industries - Multiple projects (company_11)
            [
                'name' => 'Financial Analytics Platform',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Real-time financial data analytics and reporting platform',
                'client' => 'company_11',
                'assignee' => 'consultant1', // Amanda Miller (employee of Queen)
                'category' => 'sub4',
                'dueDate' => new \DateTimeImmutable('Wednesday next week 16:35'),
            ],
            [
                'name' => 'Green Arrow Logistics',
                'status' => ProjectStatus::PLANNING,
                'description' => 'Sustainable logistics and supply chain management system',
                'client' => 'company_11',
                'assignee' => 'consultant1',
                'category' => 'main2',
                'dueDate' => new \DateTimeImmutable('Thursday next week 9:25'),
            ],

            // LexCorp - Multiple projects (company_12)
            [
                'name' => 'Legal Document Management',
                'status' => ProjectStatus::COMPLETED,
                'description' => 'Automated legal document processing and management system',
                'client' => 'company_12',
                'assignee' => 'external',
                'category' => 'sub5',
                'dueDate' => new \DateTimeImmutable('Thursday next week 12:15'),
            ],
            [
                'name' => 'Corporate Intelligence System',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Advanced business intelligence and analytics platform',
                'client' => 'company_12',
                'assignee' => 'admin',
                'category' => 'main4',
                'dueDate' => new \DateTimeImmutable('Thursday next week 15:00'),
            ],

            // Kord Industries (company_13)
            [
                'name' => 'Web Portal Redesign',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Complete redesign of corporate web portal with modern UI/UX',
                'client' => 'company_13',
                'assignee' => 'editor',
                'category' => 'sub1',
                'dueDate' => new \DateTimeImmutable('Thursday next week 17:00'),
            ],

            // Tyrell Corporation (company_14)
            [
                'name' => 'Replicant Database System',
                'status' => ProjectStatus::PLANNING,
                'description' => 'Advanced database system for synthetic biology research',
                'client' => 'company_14',
                'assignee' => 'teamlead',
                'category' => 'sub3',
                'dueDate' => new \DateTimeImmutable('Friday next week 10:30'),
            ],

            // Seegson Corporation (company_15)
            [
                'name' => 'Legal Compliance Platform',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Multi-jurisdictional legal compliance management system',
                'client' => 'company_15',
                'assignee' => 'external',
                'category' => 'sub5',
                'dueDate' => new \DateTimeImmutable('Friday next week 13:20'),
            ],

            // Tricell Pharmaceuticals - Multiple projects (company_16)
            [
                'name' => 'Pharmaceutical CRM',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Customer relationship management system for pharmaceutical industry',
                'client' => 'company_16',
                'assignee' => 'marketing1', // David Williams (employee of Tricell)
                'category' => 'sub6',
                'dueDate' => new \DateTimeImmutable('Friday next week 15:40'),
            ],
            [
                'name' => 'Clinical Trial Management',
                'status' => ProjectStatus::PLANNING,
                'description' => 'Comprehensive clinical trial tracking and management system',
                'client' => 'company_16',
                'assignee' => 'marketing1',
                'category' => 'main2',
                'dueDate' => new \DateTimeImmutable('Monday this week 11:00'),
            ],

            // TerraSave International (company_17)
            [
                'name' => 'AI Content Generation',
                'status' => ProjectStatus::PLANNING,
                'description' => 'AI-powered content creation and management system',
                'client' => 'company_17',
                'assignee' => 'admin',
                'category' => 'sub7',
                'dueDate' => new \DateTimeImmutable('Tuesday this week 12:45'),
            ],

            // Blue Umbrella Ltd - Multiple projects (company_18)
            [
                'name' => 'Global Marketing Automation',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Multi-channel marketing automation platform',
                'client' => 'company_18',
                'assignee' => 'manager',
                'category' => 'main3',
                'dueDate' => new \DateTimeImmutable('Wednesday this week 10:30'),
            ],
            [
                'name' => 'Brand Management System',
                'status' => ProjectStatus::COMPLETED,
                'description' => 'Comprehensive brand management and tracking platform',
                'client' => 'company_18',
                'assignee' => 'marketing1',
                'category' => 'sub6',
                'dueDate' => new \DateTimeImmutable('Friday this week 11:30'),
            ],
        ];

        foreach ($projectsData as $index => $projectData) {
            $client = $this->companies[$projectData['client']] ?? null;
            $assignee = $this->users[$projectData['assignee']] ?? null;
            $category = $this->categories[$projectData['category']] ?? null;

            $project = (new Project())
                ->setName($projectData['name'])
                ->setStatus($projectData['status'])
                ->setDescription($projectData['description'])
                ->setClient($client)
                ->setAssignee($assignee)
                ->setCategory($category)
                ->setDueDate($projectData['dueDate']);

            $manager->persist($project);
            $this->projects['project_' . $index] = $project;
        }

        $manager->flush();
    }

    private function createCampaignFixtures(ObjectManager $manager): void
    {
        $campaignsData = [
            [
                'name' => 'Digital Transformation 2025',
                'description' => 'Comprehensive digital transformation initiative focusing on modernizing legacy systems and implementing cutting-edge technologies across multiple client organizations.',
                'category' => 'main1', // Technology
                'manager' => 'admin',
                'projects' => [
                    'project_0', // E-Commerce Platform
                    'project_1', // AI Security System
                    'project_4', // Mobile Banking App
                    'project_7', // Scientific Data Analysis
                    'project_15', // Quantum Computing Research
                    'project_18', // Quantum Realm Analytics
                ],
            ],
            [
                'name' => 'Global Marketing Excellence',
                'description' => 'Multi-company marketing campaign focusing on brand management, digital marketing automation, and content creation strategies for international markets.',
                'category' => 'main3', // Marketing & Sales
                'manager' => 'manager', // Emma Davis
                'projects' => [
                    'project_11', // Digital Marketing Campaign
                    'project_13', // Global Distribution Network
                    'project_28', // Global Marketing Automation
                    'project_29', // Brand Management System
                    'project_25', // Pharmaceutical CRM
                ],
            ],
            [
                'name' => 'Enterprise Security & Compliance',
                'description' => 'Strategic initiative to enhance security infrastructure and ensure regulatory compliance across all client operations, including legal document management and corporate intelligence systems.',
                'category' => 'main2', // Business Services
                'manager' => 'external', // Robert Thompson
                'projects' => [
                    'project_2', // Automated Defense Network
                    'project_6', // Corporate Security Upgrade
                    'project_22', // Legal Document Management
                    'project_23', // Corporate Intelligence System
                    'project_26', // Legal Compliance Platform
                    'project_5', // Business Process Optimization
                ],
            ],
        ];

        foreach ($campaignsData as $index => $campaignData) {
            $category = $this->categories[$campaignData['category']] ?? null;
            $manager_user = $this->users[$campaignData['manager']] ?? null;

            $campaign = (new Campaign())
                ->setName($campaignData['name'])
                ->setDescription($campaignData['description'])
                ->setCategory($category)
            ;

            // Assign projects to campaign
            foreach ($campaignData['projects'] as $projectKey) {
                $project = $this->projects[$projectKey] ?? null;
                if ($project) {
                    $campaign->addProject($project);
                }
            }

            $manager->persist($campaign);
            $this->campaigns['campaign_' . $index] = $campaign;
        }

        $manager->flush();
    }
}
