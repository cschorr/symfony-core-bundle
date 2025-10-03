<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Campaign;
use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\CompanyGroup;
use C3net\CoreBundle\Entity\Contact;
use C3net\CoreBundle\Entity\Document;
use C3net\CoreBundle\Entity\Invoice;
use C3net\CoreBundle\Entity\InvoiceItem;
use C3net\CoreBundle\Entity\Offer;
use C3net\CoreBundle\Entity\OfferItem;
use C3net\CoreBundle\Entity\Project;
use C3net\CoreBundle\Entity\Transaction;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Entity\UserGroup;
use C3net\CoreBundle\Enum\DocumentType;
use C3net\CoreBundle\Enum\InvoicePaymentStatus;
use C3net\CoreBundle\Enum\InvoiceType;
use C3net\CoreBundle\Enum\OfferStatus;
use C3net\CoreBundle\Enum\ProjectStatus;
use C3net\CoreBundle\Enum\TransactionStatus;
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

    /** @var array<string, Transaction> */
    private array $transactions = [];

    /** @var array<string, Offer> */
    private array $offers = [];

    /** @var array<string, Invoice> */
    private array $invoices = [];

    /** @var array<string, Document> */
    private array $documents = [];

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
        $this->createTransactionFixtures($manager); // Create transactions
        $this->createOfferFixtures($manager); // Create offers for transactions
        $this->createInvoiceFixtures($manager); // Create invoices for transactions
        $this->createDocumentFixtures($manager); // Create documents for transactions
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

    private function createTransactionFixtures(ObjectManager $manager): void
    {
        $transactionsData = [
            // Complete workflow: DRAFT -> QUOTED -> ORDERED -> IN_PRODUCTION -> DELIVERED -> INVOICED -> PAID
            [
                'number' => 'TXN-2025-0001',
                'title' => 'E-Commerce Platform Development',
                'description' => 'Full-stack e-commerce platform with payment gateway integration',
                'status' => TransactionStatus::PAID,
                'customer' => 'company_0', // Cyberdyne Systems
                'contact' => 'contact_0', // John Doe
                'assignedUser' => 'editor',
                'category' => 'sub1',
                'currency' => 'USD',
                'project' => 'project_0',
            ],
            // Complete workflow
            [
                'number' => 'TXN-2025-0002',
                'title' => 'Mobile Banking Application',
                'description' => 'Secure mobile banking app with biometric authentication',
                'status' => TransactionStatus::PAID,
                'customer' => 'company_1', // Stark Industries
                'contact' => 'contact_6', // Jane Smith
                'assignedUser' => 'teamlead',
                'category' => 'sub2',
                'currency' => 'USD',
                'project' => 'project_3',
            ],
            // In production stage
            [
                'number' => 'TXN-2025-0003',
                'title' => 'Corporate Security Upgrade',
                'description' => 'Enterprise-wide security system implementation',
                'status' => TransactionStatus::IN_PRODUCTION,
                'customer' => 'company_2', // Wayne Enterprises
                'contact' => 'contact_12', // Alice Johnson
                'assignedUser' => 'external',
                'category' => 'main2',
                'currency' => 'USD',
                'project' => 'project_6',
            ],
            // Delivered, awaiting invoice
            [
                'number' => 'TXN-2025-0004',
                'title' => 'R&D Dashboard System',
                'description' => 'Real-time analytics and reporting dashboard',
                'status' => TransactionStatus::DELIVERED,
                'customer' => 'company_3', // Oscorp
                'contact' => 'contact_18', // Emma Martinez
                'assignedUser' => 'demo',
                'category' => 'sub3',
                'currency' => 'USD',
                'project' => 'project_8',
            ],
            // Invoiced but not yet paid
            [
                'number' => 'TXN-2025-0005',
                'title' => 'Digital Marketing Campaign',
                'description' => 'Comprehensive digital marketing strategy',
                'status' => TransactionStatus::INVOICED,
                'customer' => 'company_5', // Umbrella Corporation
                'contact' => null,
                'assignedUser' => 'manager',
                'category' => 'sub6',
                'currency' => 'EUR',
                'project' => 'project_11',
            ],
            // Ordered, starting production
            [
                'number' => 'TXN-2025-0006',
                'title' => 'Mobile Commerce App',
                'description' => 'Cross-platform mobile commerce application',
                'status' => TransactionStatus::ORDERED,
                'customer' => 'company_8', // Parker Industries
                'contact' => null,
                'assignedUser' => 'dev1',
                'category' => 'sub2',
                'currency' => 'USD',
                'project' => 'project_16',
            ],
            // Quoted, awaiting customer decision
            [
                'number' => 'TXN-2025-0007',
                'title' => 'Financial Analytics Platform',
                'description' => 'Real-time financial data analytics and reporting',
                'status' => TransactionStatus::QUOTED,
                'customer' => 'company_11', // Queen Industries
                'contact' => null,
                'assignedUser' => 'consultant1',
                'category' => 'sub4',
                'currency' => 'USD',
                'project' => 'project_21',
            ],
            // Draft stage
            [
                'number' => 'TXN-2025-0008',
                'title' => 'Legal Compliance Platform',
                'description' => 'Multi-jurisdictional legal compliance management',
                'status' => TransactionStatus::DRAFT,
                'customer' => 'company_15', // Seegson Corporation
                'contact' => null,
                'assignedUser' => 'external',
                'category' => 'sub5',
                'currency' => 'EUR',
                'project' => 'project_26',
            ],
            // Complete workflow
            [
                'number' => 'TXN-2025-0009',
                'title' => 'Pharmaceutical CRM System',
                'description' => 'Customer relationship management for pharmaceutical industry',
                'status' => TransactionStatus::PAID,
                'customer' => 'company_16', // Tricell Pharmaceuticals
                'contact' => null,
                'assignedUser' => 'marketing1',
                'category' => 'sub6',
                'currency' => 'EUR',
                'project' => 'project_25',
            ],
            // Quoted stage with multiple revisions
            [
                'number' => 'TXN-2025-0010',
                'title' => 'Quantum Computing Research',
                'description' => 'Advanced quantum computing solutions development',
                'status' => TransactionStatus::QUOTED,
                'customer' => 'company_7', // NeuralLink Systems
                'contact' => null,
                'assignedUser' => 'admin',
                'category' => 'sub3',
                'currency' => 'EUR',
                'project' => 'project_15',
            ],
            // In production
            [
                'number' => 'TXN-2025-0011',
                'title' => 'Web Portal Redesign',
                'description' => 'Complete corporate web portal redesign',
                'status' => TransactionStatus::IN_PRODUCTION,
                'customer' => 'company_13', // Kord Industries
                'contact' => null,
                'assignedUser' => 'editor',
                'category' => 'sub1',
                'currency' => 'USD',
                'project' => 'project_24',
            ],
            // Draft stage
            [
                'number' => 'TXN-2025-0012',
                'title' => 'Neural Interface Development',
                'description' => 'Advanced AI-driven neural interface system',
                'status' => TransactionStatus::DRAFT,
                'customer' => 'company_6', // GeneDyne Technologies
                'contact' => null,
                'assignedUser' => 'teamlead',
                'category' => 'main1',
                'currency' => 'USD',
                'project' => 'project_14',
            ],
            // Ordered
            [
                'number' => 'TXN-2025-0013',
                'title' => 'Global Marketing Automation',
                'description' => 'Multi-channel marketing automation platform',
                'status' => TransactionStatus::ORDERED,
                'customer' => 'company_18', // Blue Umbrella Ltd
                'contact' => null,
                'assignedUser' => 'manager',
                'category' => 'main3',
                'currency' => 'EUR',
                'project' => 'project_28',
            ],
            // Invoiced
            [
                'number' => 'TXN-2025-0014',
                'title' => 'Microservices Architecture Migration',
                'description' => 'Complete migration to microservices architecture',
                'status' => TransactionStatus::INVOICED,
                'customer' => 'company_9', // Pym Technologies
                'contact' => null,
                'assignedUser' => 'dev2',
                'category' => 'sub3',
                'currency' => 'USD',
                'project' => 'project_18',
            ],
            // Delivered
            [
                'number' => 'TXN-2025-0015',
                'title' => 'AI Content Generation System',
                'description' => 'AI-powered content creation and management',
                'status' => TransactionStatus::DELIVERED,
                'customer' => 'company_17', // TerraSave International
                'contact' => null,
                'assignedUser' => 'admin',
                'category' => 'sub7',
                'currency' => 'EUR',
                'project' => 'project_27',
            ],
        ];

        foreach ($transactionsData as $index => $data) {
            $transaction = (new Transaction())
                ->setTransactionNumber($data['number'])
                ->setTitle($data['title'])
                ->setDescription($data['description'])
                ->setStatus($data['status'])
                ->setCustomer($this->companies[$data['customer']])
                ->setAssignedUser($this->users[$data['assignedUser']])
                ->setCategory($this->categories[$data['category']])
                ->setCurrency($data['currency']);

            if (isset($data['contact']) && $data['contact']) {
                $transaction->setPrimaryContact($this->contacts[$data['contact']]);
            }

            if (isset($data['project'])) {
                $transaction->addProject($this->projects[$data['project']]);
            }

            $manager->persist($transaction);
            $this->transactions['transaction_' . $index] = $transaction;
        }

        $manager->flush();
    }

    private function createOfferFixtures(ObjectManager $manager): void
    {
        $offersData = [
            // TXN-2025-0001: PAID - Should have accepted offer
            [
                'transaction' => 'transaction_0',
                'offerNumber' => 'OFF-2025-0001-V1',
                'title' => 'E-Commerce Platform Development - Initial Offer',
                'status' => OfferStatus::ACCEPTED,
                'validUntil' => new \DateTimeImmutable('+30 days'),
                'items' => [
                    ['description' => 'Frontend Development (React/Next.js)', 'quantity' => '120', 'unitPrice' => '150.00'],
                    ['description' => 'Backend API Development (Symfony)', 'quantity' => '100', 'unitPrice' => '150.00'],
                    ['description' => 'Payment Gateway Integration', 'quantity' => '40', 'unitPrice' => '175.00'],
                    ['description' => 'Testing & Quality Assurance', 'quantity' => '30', 'unitPrice' => '125.00'],
                ],
            ],
            // TXN-2025-0002: PAID - Should have accepted offer
            [
                'transaction' => 'transaction_1',
                'offerNumber' => 'OFF-2025-0002-V1',
                'title' => 'Mobile Banking App - Initial Offer',
                'status' => OfferStatus::REJECTED,
                'validUntil' => new \DateTimeImmutable('+30 days'),
                'items' => [
                    ['description' => 'iOS Development', 'quantity' => '100', 'unitPrice' => '160.00'],
                    ['description' => 'Android Development', 'quantity' => '100', 'unitPrice' => '160.00'],
                    ['description' => 'Biometric Authentication Module', 'quantity' => '40', 'unitPrice' => '180.00'],
                ],
            ],
            [
                'transaction' => 'transaction_1',
                'offerNumber' => 'OFF-2025-0002-V2',
                'title' => 'Mobile Banking App - Revised Offer',
                'status' => OfferStatus::ACCEPTED,
                'validUntil' => new \DateTimeImmutable('+30 days'),
                'items' => [
                    ['description' => 'iOS Development', 'quantity' => '120', 'unitPrice' => '150.00'],
                    ['description' => 'Android Development', 'quantity' => '120', 'unitPrice' => '150.00'],
                    ['description' => 'Biometric Authentication Module', 'quantity' => '50', 'unitPrice' => '170.00'],
                    ['description' => 'Security Audit & Penetration Testing', 'quantity' => '20', 'unitPrice' => '200.00'],
                ],
            ],
            // TXN-2025-0003: IN_PRODUCTION - Should have accepted offer
            [
                'transaction' => 'transaction_2',
                'offerNumber' => 'OFF-2025-0003-V1',
                'title' => 'Corporate Security Upgrade - Proposal',
                'status' => OfferStatus::ACCEPTED,
                'validUntil' => new \DateTimeImmutable('+45 days'),
                'items' => [
                    ['description' => 'Security Infrastructure Assessment', 'quantity' => '40', 'unitPrice' => '200.00'],
                    ['description' => 'Security System Implementation', 'quantity' => '160', 'unitPrice' => '180.00'],
                    ['description' => 'Employee Training & Documentation', 'quantity' => '24', 'unitPrice' => '150.00'],
                ],
            ],
            // TXN-2025-0004: DELIVERED - Should have accepted offer
            [
                'transaction' => 'transaction_3',
                'offerNumber' => 'OFF-2025-0004-V1',
                'title' => 'R&D Dashboard System - Development Proposal',
                'status' => OfferStatus::ACCEPTED,
                'validUntil' => new \DateTimeImmutable('+30 days'),
                'items' => [
                    ['description' => 'Dashboard UI/UX Design', 'quantity' => '40', 'unitPrice' => '140.00'],
                    ['description' => 'Real-time Analytics Engine', 'quantity' => '80', 'unitPrice' => '165.00'],
                    ['description' => 'Data Visualization Components', 'quantity' => '50', 'unitPrice' => '155.00'],
                ],
            ],
            // TXN-2025-0005: INVOICED - Should have accepted offer
            [
                'transaction' => 'transaction_4',
                'offerNumber' => 'OFF-2025-0005-V1',
                'title' => 'Digital Marketing Campaign - Strategy & Implementation',
                'status' => OfferStatus::ACCEPTED,
                'validUntil' => new \DateTimeImmutable('+30 days'),
                'items' => [
                    ['description' => 'Marketing Strategy Development', 'quantity' => '30', 'unitPrice' => '180.00'],
                    ['description' => 'Content Creation & SEO', 'quantity' => '60', 'unitPrice' => '145.00'],
                    ['description' => 'Social Media Campaign Management', 'quantity' => '40', 'unitPrice' => '135.00'],
                    ['description' => 'Analytics & Reporting', 'quantity' => '20', 'unitPrice' => '150.00'],
                ],
            ],
            // TXN-2025-0006: ORDERED - Should have accepted offer
            [
                'transaction' => 'transaction_5',
                'offerNumber' => 'OFF-2025-0006-V1',
                'title' => 'Mobile Commerce App - Development Quote',
                'status' => OfferStatus::ACCEPTED,
                'validUntil' => new \DateTimeImmutable('+30 days'),
                'items' => [
                    ['description' => 'Cross-Platform Development (Flutter)', 'quantity' => '140', 'unitPrice' => '155.00'],
                    ['description' => 'E-commerce Integration', 'quantity' => '60', 'unitPrice' => '165.00'],
                    ['description' => 'Payment Processing Setup', 'quantity' => '30', 'unitPrice' => '180.00'],
                ],
            ],
            // TXN-2025-0007: QUOTED - Should have sent offers with revisions
            [
                'transaction' => 'transaction_6',
                'offerNumber' => 'OFF-2025-0007-V1',
                'title' => 'Financial Analytics Platform - Initial Proposal',
                'status' => OfferStatus::SENT,
                'validUntil' => new \DateTimeImmutable('+30 days'),
                'items' => [
                    ['description' => 'Platform Architecture & Design', 'quantity' => '60', 'unitPrice' => '190.00'],
                    ['description' => 'Analytics Engine Development', 'quantity' => '120', 'unitPrice' => '175.00'],
                    ['description' => 'Reporting Dashboard', 'quantity' => '70', 'unitPrice' => '160.00'],
                ],
            ],
            [
                'transaction' => 'transaction_6',
                'offerNumber' => 'OFF-2025-0007-V2',
                'title' => 'Financial Analytics Platform - Revised Proposal',
                'status' => OfferStatus::SENT,
                'validUntil' => new \DateTimeImmutable('+45 days'),
                'items' => [
                    ['description' => 'Platform Architecture & Design', 'quantity' => '50', 'unitPrice' => '190.00'],
                    ['description' => 'Analytics Engine Development', 'quantity' => '100', 'unitPrice' => '175.00'],
                    ['description' => 'Reporting Dashboard', 'quantity' => '60', 'unitPrice' => '160.00'],
                    ['description' => 'Data Integration Module', 'quantity' => '40', 'unitPrice' => '170.00'],
                ],
            ],
            // TXN-2025-0008: DRAFT - Draft offer
            [
                'transaction' => 'transaction_7',
                'offerNumber' => 'OFF-2025-0008-V1',
                'title' => 'Legal Compliance Platform - Preliminary Quote',
                'status' => OfferStatus::DRAFT,
                'validUntil' => new \DateTimeImmutable('+30 days'),
                'items' => [
                    ['description' => 'Compliance Requirements Analysis', 'quantity' => '40', 'unitPrice' => '200.00'],
                    ['description' => 'Platform Development', 'quantity' => '150', 'unitPrice' => '180.00'],
                ],
            ],
            // TXN-2025-0009: PAID - Should have accepted offer
            [
                'transaction' => 'transaction_8',
                'offerNumber' => 'OFF-2025-0009-V1',
                'title' => 'Pharmaceutical CRM System - Development Quote',
                'status' => OfferStatus::ACCEPTED,
                'validUntil' => new \DateTimeImmutable('+30 days'),
                'items' => [
                    ['description' => 'CRM System Development', 'quantity' => '100', 'unitPrice' => '165.00'],
                    ['description' => 'Industry-Specific Customization', 'quantity' => '60', 'unitPrice' => '175.00'],
                    ['description' => 'Integration & Migration', 'quantity' => '40', 'unitPrice' => '170.00'],
                    ['description' => 'Training & Support', 'quantity' => '20', 'unitPrice' => '140.00'],
                ],
            ],
            // TXN-2025-0010: QUOTED - Multiple revisions
            [
                'transaction' => 'transaction_9',
                'offerNumber' => 'OFF-2025-0010-V1',
                'title' => 'Quantum Computing Research - Initial Proposal',
                'status' => OfferStatus::REJECTED,
                'validUntil' => new \DateTimeImmutable('+60 days'),
                'items' => [
                    ['description' => 'Research & Feasibility Study', 'quantity' => '80', 'unitPrice' => '250.00'],
                    ['description' => 'Prototype Development', 'quantity' => '200', 'unitPrice' => '225.00'],
                ],
            ],
            [
                'transaction' => 'transaction_9',
                'offerNumber' => 'OFF-2025-0010-V2',
                'title' => 'Quantum Computing Research - Revised Proposal',
                'status' => OfferStatus::SENT,
                'validUntil' => new \DateTimeImmutable('+60 days'),
                'items' => [
                    ['description' => 'Research & Feasibility Study', 'quantity' => '60', 'unitPrice' => '250.00'],
                    ['description' => 'Prototype Development - Phase 1', 'quantity' => '120', 'unitPrice' => '225.00'],
                    ['description' => 'Prototype Development - Phase 2', 'quantity' => '100', 'unitPrice' => '225.00'],
                    ['description' => 'Documentation & Knowledge Transfer', 'quantity' => '30', 'unitPrice' => '200.00'],
                ],
            ],
            // TXN-2025-0011: IN_PRODUCTION - Should have accepted offer
            [
                'transaction' => 'transaction_10',
                'offerNumber' => 'OFF-2025-0011-V1',
                'title' => 'Web Portal Redesign - Design & Development',
                'status' => OfferStatus::ACCEPTED,
                'validUntil' => new \DateTimeImmutable('+30 days'),
                'items' => [
                    ['description' => 'UI/UX Design & Prototyping', 'quantity' => '60', 'unitPrice' => '145.00'],
                    ['description' => 'Frontend Development', 'quantity' => '90', 'unitPrice' => '155.00'],
                    ['description' => 'Backend Integration', 'quantity' => '50', 'unitPrice' => '160.00'],
                ],
            ],
            // TXN-2025-0013: ORDERED - Should have accepted offer
            [
                'transaction' => 'transaction_12',
                'offerNumber' => 'OFF-2025-0013-V1',
                'title' => 'Global Marketing Automation - Platform Setup',
                'status' => OfferStatus::ACCEPTED,
                'validUntil' => new \DateTimeImmutable('+30 days'),
                'items' => [
                    ['description' => 'Platform Setup & Configuration', 'quantity' => '40', 'unitPrice' => '170.00'],
                    ['description' => 'Multi-Channel Integration', 'quantity' => '80', 'unitPrice' => '165.00'],
                    ['description' => 'Workflow Automation Development', 'quantity' => '60', 'unitPrice' => '175.00'],
                    ['description' => 'Training & Documentation', 'quantity' => '20', 'unitPrice' => '145.00'],
                ],
            ],
            // TXN-2025-0014: INVOICED - Should have accepted offer
            [
                'transaction' => 'transaction_13',
                'offerNumber' => 'OFF-2025-0014-V1',
                'title' => 'Microservices Architecture Migration',
                'status' => OfferStatus::ACCEPTED,
                'validUntil' => new \DateTimeImmutable('+45 days'),
                'items' => [
                    ['description' => 'Architecture Planning & Design', 'quantity' => '50', 'unitPrice' => '185.00'],
                    ['description' => 'Microservices Development', 'quantity' => '150', 'unitPrice' => '170.00'],
                    ['description' => 'Migration & Testing', 'quantity' => '60', 'unitPrice' => '175.00'],
                ],
            ],
            // TXN-2025-0015: DELIVERED - Should have accepted offer
            [
                'transaction' => 'transaction_14',
                'offerNumber' => 'OFF-2025-0015-V1',
                'title' => 'AI Content Generation System',
                'status' => OfferStatus::ACCEPTED,
                'validUntil' => new \DateTimeImmutable('+30 days'),
                'items' => [
                    ['description' => 'AI Model Training & Integration', 'quantity' => '80', 'unitPrice' => '190.00'],
                    ['description' => 'Content Management System', 'quantity' => '70', 'unitPrice' => '165.00'],
                    ['description' => 'API Development & Documentation', 'quantity' => '40', 'unitPrice' => '170.00'],
                ],
            ],
        ];

        foreach ($offersData as $index => $offerData) {
            $transaction = $this->transactions[$offerData['transaction']];

            $offer = (new Offer())
                ->setOfferNumber($offerData['offerNumber'])
                ->setTitle($offerData['title'])
                ->setStatus($offerData['status'])
                ->setValidUntil($offerData['validUntil'])
                ->setTransaction($transaction);

            // Calculate totals
            $subtotal = '0.00';
            foreach ($offerData['items'] as $itemData) {
                $itemTotal = bcmul($itemData['quantity'], $itemData['unitPrice'], 2);
                $subtotal = bcadd($subtotal, $itemTotal, 2);

                $offerItem = (new OfferItem())
                    ->setDescription($itemData['description'])
                    ->setQuantity($itemData['quantity'])
                    ->setUnitPrice($itemData['unitPrice'])
                    ->setOffer($offer);

                $manager->persist($offerItem);
            }

            $offer->setSubtotal($subtotal);
            $offer->setTaxRate('19.00'); // Standard VAT
            $taxAmount = bcmul($subtotal, '0.19', 2);
            $offer->setTaxAmount($taxAmount);
            $offer->setTotal(bcadd($subtotal, $taxAmount, 2));

            $manager->persist($offer);
            $this->offers['offer_' . $index] = $offer;

            // Link accepted offer to transaction
            if ($offerData['status'] === OfferStatus::ACCEPTED) {
                $transaction->setAcceptedOffer($offer);
                $manager->persist($transaction);
            }
        }

        $manager->flush();
    }

    private function createInvoiceFixtures(ObjectManager $manager): void
    {
        $invoicesData = [
            // TXN-2025-0001: PAID - Full invoice, paid
            [
                'transaction' => 'transaction_0',
                'invoiceNumber' => 'INV-2025-0001',
                'title' => 'E-Commerce Platform Development - Final Invoice',
                'type' => InvoiceType::FULL,
                'paymentStatus' => InvoicePaymentStatus::PAID,
                'dueDate' => new \DateTimeImmutable('-10 days'),
                'offer' => 'offer_0',
            ],
            // TXN-2025-0002: PAID - Full invoice, paid
            [
                'transaction' => 'transaction_1',
                'invoiceNumber' => 'INV-2025-0002',
                'title' => 'Mobile Banking App - Final Invoice',
                'type' => InvoiceType::FULL,
                'paymentStatus' => InvoicePaymentStatus::PAID,
                'dueDate' => new \DateTimeImmutable('-5 days'),
                'offer' => 'offer_2',
            ],
            // TXN-2025-0005: INVOICED - Full invoice, unpaid
            [
                'transaction' => 'transaction_4',
                'invoiceNumber' => 'INV-2025-0003',
                'title' => 'Digital Marketing Campaign - Invoice',
                'type' => InvoiceType::FULL,
                'paymentStatus' => InvoicePaymentStatus::UNPAID,
                'dueDate' => new \DateTimeImmutable('+14 days'),
                'offer' => 'offer_5',
            ],
            // TXN-2025-0009: PAID - Deposit + Final invoices
            [
                'transaction' => 'transaction_8',
                'invoiceNumber' => 'INV-2025-0004',
                'title' => 'Pharmaceutical CRM System - Deposit Invoice',
                'type' => InvoiceType::DEPOSIT,
                'paymentStatus' => InvoicePaymentStatus::PAID,
                'dueDate' => new \DateTimeImmutable('-30 days'),
                'offer' => 'offer_10',
                'depositPercentage' => 30,
            ],
            [
                'transaction' => 'transaction_8',
                'invoiceNumber' => 'INV-2025-0005',
                'title' => 'Pharmaceutical CRM System - Final Invoice',
                'type' => InvoiceType::FINAL,
                'paymentStatus' => InvoicePaymentStatus::PAID,
                'dueDate' => new \DateTimeImmutable('-7 days'),
                'offer' => 'offer_10',
                'depositPercentage' => 70,
            ],
            // TXN-2025-0014: INVOICED - Partial payment
            [
                'transaction' => 'transaction_13',
                'invoiceNumber' => 'INV-2025-0006',
                'title' => 'Microservices Architecture Migration - Invoice',
                'type' => InvoiceType::FULL,
                'paymentStatus' => InvoicePaymentStatus::PARTIAL,
                'dueDate' => new \DateTimeImmutable('+7 days'),
                'offer' => 'offer_15',
            ],
        ];

        foreach ($invoicesData as $index => $invoiceData) {
            $transaction = $this->transactions[$invoiceData['transaction']];
            $offer = $this->offers[$invoiceData['offer']];

            $invoice = (new Invoice())
                ->setInvoiceNumber($invoiceData['invoiceNumber'])
                ->setTitle($invoiceData['title'])
                ->setType($invoiceData['type'])
                ->setPaymentStatus($invoiceData['paymentStatus'])
                ->setDueDate($invoiceData['dueDate'])
                ->setTransaction($transaction);

            // Calculate invoice amounts based on type
            $offerTotal = $offer->getTotal();
            $depositPercentage = $invoiceData['depositPercentage'] ?? 100;

            if ($invoiceData['type'] === InvoiceType::DEPOSIT) {
                $subtotal = bcmul($offer->getSubtotal(), (string)($depositPercentage / 100), 2);
            } elseif ($invoiceData['type'] === InvoiceType::FINAL) {
                $subtotal = bcmul($offer->getSubtotal(), (string)($depositPercentage / 100), 2);
            } else {
                $subtotal = $offer->getSubtotal();
            }

            $invoice->setSubtotal($subtotal);
            $invoice->setTaxRate($offer->getTaxRate());
            $taxAmount = bcmul($subtotal, bcdiv($offer->getTaxRate(), '100', 4), 2);
            $invoice->setTaxAmount($taxAmount);
            $invoice->setTotal(bcadd($subtotal, $taxAmount, 2));

            // Add invoice items matching the offer
            $itemCount = 0;
            foreach ($offer->getOfferItems() as $offerItem) {
                $quantity = $offerItem->getQuantity();
                $unitPrice = $offerItem->getUnitPrice();

                // Adjust quantity for deposit/final invoices
                if ($invoiceData['type'] === InvoiceType::DEPOSIT || $invoiceData['type'] === InvoiceType::FINAL) {
                    $quantity = bcmul($quantity, (string)($depositPercentage / 100), 2);
                }

                $invoiceItem = (new InvoiceItem())
                    ->setDescription($offerItem->getDescription())
                    ->setQuantity($quantity)
                    ->setUnitPrice($unitPrice)
                    ->setInvoice($invoice);

                $manager->persist($invoiceItem);
                $itemCount++;

                // Limit items to avoid too many in deposit invoices
                if ($itemCount >= 5) {
                    break;
                }
            }

            $manager->persist($invoice);
            $this->invoices['invoice_' . $index] = $invoice;
        }

        $manager->flush();
    }

    private function createDocumentFixtures(ObjectManager $manager): void
    {
        $documentsData = [
            // TXN-2025-0001: Complete project - multiple documents
            [
                'transaction' => 'transaction_0',
                'project' => 'project_0',
                'title' => 'Project Brief - E-Commerce Platform',
                'type' => DocumentType::BRIEF,
                'filePath' => '/documents/briefs/ecommerce-platform-brief.pdf',
            ],
            [
                'transaction' => 'transaction_0',
                'project' => 'project_0',
                'title' => 'Development Contract',
                'type' => DocumentType::CONTRACT,
                'filePath' => '/documents/contracts/contract-txn-2025-0001.pdf',
            ],
            [
                'transaction' => 'transaction_0',
                'project' => null,
                'title' => 'Offer Document OFF-2025-0001-V1',
                'type' => DocumentType::OFFER_PDF,
                'filePath' => '/documents/offers/offer-2025-0001-v1.pdf',
            ],
            [
                'transaction' => 'transaction_0',
                'project' => null,
                'title' => 'Invoice INV-2025-0001',
                'type' => DocumentType::INVOICE_PDF,
                'filePath' => '/documents/invoices/invoice-2025-0001.pdf',
            ],
            // TXN-2025-0002: Complete project
            [
                'transaction' => 'transaction_1',
                'project' => 'project_3',
                'title' => 'Mobile Banking App - Requirements',
                'type' => DocumentType::BRIEF,
                'filePath' => '/documents/briefs/mobile-banking-requirements.pdf',
            ],
            [
                'transaction' => 'transaction_1',
                'project' => 'project_3',
                'title' => 'Security Audit Report',
                'type' => DocumentType::DELIVERABLE,
                'filePath' => '/documents/deliverables/security-audit-mobile-banking.pdf',
            ],
            [
                'transaction' => 'transaction_1',
                'project' => null,
                'title' => 'Invoice INV-2025-0002',
                'type' => DocumentType::INVOICE_PDF,
                'filePath' => '/documents/invoices/invoice-2025-0002.pdf',
            ],
            // TXN-2025-0003: In production
            [
                'transaction' => 'transaction_2',
                'project' => 'project_6',
                'title' => 'Corporate Security - Project Brief',
                'type' => DocumentType::BRIEF,
                'filePath' => '/documents/briefs/corporate-security-brief.pdf',
            ],
            [
                'transaction' => 'transaction_2',
                'project' => null,
                'title' => 'Service Agreement',
                'type' => DocumentType::CONTRACT,
                'filePath' => '/documents/contracts/contract-txn-2025-0003.pdf',
            ],
            // TXN-2025-0004: Delivered
            [
                'transaction' => 'transaction_3',
                'project' => 'project_8',
                'title' => 'R&D Dashboard - Specifications',
                'type' => DocumentType::BRIEF,
                'filePath' => '/documents/briefs/rnd-dashboard-specs.pdf',
            ],
            [
                'transaction' => 'transaction_3',
                'project' => 'project_8',
                'title' => 'Dashboard User Manual',
                'type' => DocumentType::DELIVERABLE,
                'filePath' => '/documents/deliverables/dashboard-user-manual.pdf',
            ],
            // TXN-2025-0005: Invoiced
            [
                'transaction' => 'transaction_4',
                'project' => 'project_11',
                'title' => 'Marketing Campaign Strategy',
                'type' => DocumentType::BRIEF,
                'filePath' => '/documents/briefs/marketing-campaign-strategy.pdf',
            ],
            [
                'transaction' => 'transaction_4',
                'project' => null,
                'title' => 'Invoice INV-2025-0003',
                'type' => DocumentType::INVOICE_PDF,
                'filePath' => '/documents/invoices/invoice-2025-0003.pdf',
            ],
            // TXN-2025-0006: Ordered
            [
                'transaction' => 'transaction_5',
                'project' => 'project_16',
                'title' => 'Mobile Commerce App - Technical Specifications',
                'type' => DocumentType::BRIEF,
                'filePath' => '/documents/briefs/mobile-commerce-specs.pdf',
            ],
            [
                'transaction' => 'transaction_5',
                'project' => null,
                'title' => 'Development Agreement',
                'type' => DocumentType::CONTRACT,
                'filePath' => '/documents/contracts/contract-txn-2025-0006.pdf',
            ],
            // TXN-2025-0007: Quoted
            [
                'transaction' => 'transaction_6',
                'project' => 'project_21',
                'title' => 'Financial Analytics - Initial Brief',
                'type' => DocumentType::BRIEF,
                'filePath' => '/documents/briefs/financial-analytics-brief.pdf',
            ],
            [
                'transaction' => 'transaction_6',
                'project' => null,
                'title' => 'Offer Document OFF-2025-0007-V2',
                'type' => DocumentType::OFFER_PDF,
                'filePath' => '/documents/offers/offer-2025-0007-v2.pdf',
            ],
            // TXN-2025-0009: Paid
            [
                'transaction' => 'transaction_8',
                'project' => 'project_25',
                'title' => 'Pharmaceutical CRM - Requirements',
                'type' => DocumentType::BRIEF,
                'filePath' => '/documents/briefs/pharma-crm-requirements.pdf',
            ],
            [
                'transaction' => 'transaction_8',
                'project' => 'project_25',
                'title' => 'CRM Implementation Guide',
                'type' => DocumentType::DELIVERABLE,
                'filePath' => '/documents/deliverables/crm-implementation-guide.pdf',
            ],
            [
                'transaction' => 'transaction_8',
                'project' => null,
                'title' => 'Invoice INV-2025-0004 (Deposit)',
                'type' => DocumentType::INVOICE_PDF,
                'filePath' => '/documents/invoices/invoice-2025-0004.pdf',
            ],
            [
                'transaction' => 'transaction_8',
                'project' => null,
                'title' => 'Invoice INV-2025-0005 (Final)',
                'type' => DocumentType::INVOICE_PDF,
                'filePath' => '/documents/invoices/invoice-2025-0005.pdf',
            ],
            // TXN-2025-0011: In production
            [
                'transaction' => 'transaction_10',
                'project' => 'project_24',
                'title' => 'Web Portal Redesign - Design Mockups',
                'type' => DocumentType::DELIVERABLE,
                'filePath' => '/documents/deliverables/portal-design-mockups.pdf',
            ],
            // TXN-2025-0014: Invoiced
            [
                'transaction' => 'transaction_13',
                'project' => 'project_18',
                'title' => 'Microservices Architecture Plan',
                'type' => DocumentType::BRIEF,
                'filePath' => '/documents/briefs/microservices-architecture.pdf',
            ],
            [
                'transaction' => 'transaction_13',
                'project' => null,
                'title' => 'Invoice INV-2025-0006',
                'type' => DocumentType::INVOICE_PDF,
                'filePath' => '/documents/invoices/invoice-2025-0006.pdf',
            ],
        ];

        foreach ($documentsData as $index => $documentData) {
            $transaction = $this->transactions[$documentData['transaction']];
            $project = isset($documentData['project']) ? $this->projects[$documentData['project']] : null;

            $document = (new Document())
                ->setTitle($documentData['title'])
                ->setType($documentData['type'])
                ->setFilePath($documentData['filePath'])
                ->setTransaction($transaction);

            if ($project) {
                $document->setProject($project);
            }

            $manager->persist($document);
            $this->documents['document_' . $index] = $document;
        }

        $manager->flush();
    }
}
