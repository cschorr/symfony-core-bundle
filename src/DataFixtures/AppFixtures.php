<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Campaign;
use C3net\CoreBundle\Entity\CategorizableEntity;
use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\CompanyGroup;
use C3net\CoreBundle\Entity\Contact;
use C3net\CoreBundle\Entity\Department;
use C3net\CoreBundle\Entity\Project;
use C3net\CoreBundle\Entity\Transaction;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Entity\UserGroup;
use C3net\CoreBundle\Enum\DomainEntityType;
use C3net\CoreBundle\Enum\ProjectStatus;
use C3net\CoreBundle\Enum\TransactionType;
use C3net\CoreBundle\Repository\UserGroupRepository;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends AbstractCategorizableFixture
{
    private const string DEFAULT_PASSWORD = 'pass_1234';

    /** @var array<string, User> */
    protected array $users = [];

    /** @var array<string, UserGroup> */
    protected array $userGroups = [];

    /** @var array<string, Category> */
    protected array $categories = [];

    /** @var array<string, Company> */
    protected array $companies = [];

    /** @var array<string, Department> */
    protected array $departments = [];

    /** @var array<string, Contact> */
    protected array $contacts = [];

    /** @var array<string, CompanyGroup> */
    protected array $companyGroups = [];

    /** @var array<string, Campaign> */
    protected array $campaigns = [];

    /** @var array<string, Project> */
    protected array $projects = [];

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
        $this->createDepartmentFixtures($manager); // Create departments after companies
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
                'lastName' => 'Admin',
                'firstName' => 'System',
                'userGroups' => ['Admin'],
                'company' => null, // Admin not assigned to specific company
            ],
            'editor' => [
                'email' => 'editor@example.com',
                'active' => true,
                'notes' => 'Web developer working on e-commerce projects',
                'category' => 'sub1',
                'lastName' => 'Wilson',
                'firstName' => 'Sarah',
                'userGroups' => ['Editor'],
                'company' => 'company_0', // Cyberdyne Systems
            ],
            'teamlead' => [
                'email' => 'teamlead@example.com',
                'active' => true,
                'notes' => 'Senior developer specializing in mobile apps',
                'category' => 'sub2',
                'lastName' => 'Johnson',
                'firstName' => 'Michael',
                'userGroups' => ['Teamlead'],
                'roles' => ['ROLE_QUALITY'],
                'company' => 'company_1', // Stark Industries
            ],
            'manager' => [
                'email' => 'marketing@example.com',
                'active' => true,
                'notes' => 'Marketing specialist for digital campaigns',
                'category' => 'sub6',
                'lastName' => 'Davis',
                'firstName' => 'Emma',
                'userGroups' => ['Manager'],
                'company' => 'company_5', // Umbrella Corporation
            ],
            'external' => [
                'email' => 'external@example.com',
                'active' => true,
                'notes' => 'Business consultant for process optimization',
                'category' => 'main4',
                'lastName' => 'Thompson',
                'firstName' => 'Robert',
                'userGroups' => ['External Users'],
                'company' => 'company_2', // Wayne Enterprises
            ],
            'demo' => [
                'email' => 'demo@example.com',
                'active' => true,
                'notes' => 'Full-stack developer with React and PHP expertise',
                'category' => 'sub1',
                'lastName' => 'Anderson',
                'firstName' => 'Alex',
                'userGroups' => ['Editor'],
                'company' => 'company_3', // Oscorp
            ],
            // Additional employees for the expanded companies
            'dev1' => [
                'email' => 'dev1@example.com',
                'active' => true,
                'notes' => 'Frontend specialist focusing on React and Vue.js',
                'category' => 'sub1',
                'lastName' => 'Brown',
                'firstName' => 'Jessica',
                'userGroups' => ['Editor'],
                'company' => 'company_7', // Parker Industries
            ],
            'dev2' => [
                'email' => 'dev2@example.com',
                'active' => true,
                'notes' => 'Mobile app developer with iOS and Android experience',
                'category' => 'sub2',
                'lastName' => 'Garcia',
                'firstName' => 'Carlos',
                'userGroups' => ['Teamlead'],
                'company' => 'company_8', // Pym Technologies
            ],
            'consultant1' => [
                'email' => 'consultant1@example.com',
                'active' => true,
                'notes' => 'Business process optimization specialist',
                'category' => 'main4',
                'lastName' => 'Miller',
                'firstName' => 'Amanda',
                'userGroups' => ['Manager'],
                'company' => 'company_10', // Queen Industries
            ],
            'marketing1' => [
                'email' => 'marketing1@example.com',
                'active' => true,
                'notes' => 'Digital marketing strategist and content creator',
                'category' => 'sub6',
                'lastName' => 'Williams',
                'firstName' => 'David',
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
                ->setLastName($userData['lastName'])
                ->setFirstName($userData['firstName'])
                ->setRoles($userData['roles'] ?? [])
            ;

            if ($company) {
                $user->setCompany($company);
            }

            foreach ($userGroups as $userGroup) {
                $user->addUserGroup($userGroup);
            }

            $this->persistAndFlush($manager, $user); // Persist and flush to get ID for category assignment

            // Add category after entity is persisted (category is guaranteed non-null by check above)
            $userId = $user->getId();
            if (null === $userId) {
                throw new \LogicException('User ID should not be null after persist and flush');
            }

            $assignment = new CategorizableEntity();
            $assignment->setCategory($category);
            $assignment->setEntityType(DomainEntityType::User);
            $assignment->setEntityId($userId->toString());
            $manager->persist($assignment);

            $this->users[$key] = $user;
        }

        $this->flushSafely($manager);
    }

    private function createCompanyGroupFixtures(ObjectManager $manager): void
    {
        // Themed groups from comics and motion pictures
        $groups = [
            'skynet' => ['name' => 'Skynet Group', 'shortcode' => 'SKYNET'],
            'marvel' => ['name' => 'Marvel Group', 'shortcode' => 'MARVEL'],
            'dc' => ['name' => 'DC Group', 'shortcode' => 'DC'],
            'weyland' => ['name' => 'Weyland-Yutani Group', 'shortcode' => 'WEYLAND'],
            'umbrella' => ['name' => 'Umbrella Group', 'shortcode' => 'UMBRELLA'],
        ];

        foreach ($groups as $key => $data) {
            $group = (new CompanyGroup())
                ->setName($data['name'])
                ->setShortcode($data['shortcode']);
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
                ->setPhone($data['phone'])
                ->setUrl($data['url'])
                ->setStreet($data['street'])
                ->setCity($data['city'])
                ->setZip($data['zipCode'])
                ->setCompanyGroup($group)
                ->setImagePath($randomLogo);

            $this->persistAndFlush($manager, $company); // Persist and flush to get ID for category assignment

            // Add category after entity is persisted
            if ($category) {
                $companyId = $company->getId();
                if (null === $companyId) {
                    throw new \LogicException('Company ID should not be null after persist and flush');
                }

                $assignment = new CategorizableEntity();
                $assignment->setCategory($category);
                $assignment->setEntityType(DomainEntityType::Company);
                $assignment->setEntityId($companyId->toString());
                $manager->persist($assignment);
            }

            $this->companies['company_' . $index] = $company;
        }

        $this->flushSafely($manager);
    }

    private function createDepartmentFixtures(ObjectManager $manager): void
    {
        // Define departments for each company based on contact data
        $departmentsData = [
            // Cyberdyne Systems (company_0)
            ['company' => 'company_0', 'name' => 'Technology', 'shortcode' => 'TECH'],
            ['company' => 'company_0', 'name' => 'Engineering', 'shortcode' => 'ENG'],

            // Stark Industries (company_1)
            ['company' => 'company_1', 'name' => 'Engineering', 'shortcode' => 'ENG'],
            ['company' => 'company_1', 'name' => 'Research & Development', 'shortcode' => 'RD'],

            // Wayne Enterprises (company_2)
            ['company' => 'company_2', 'name' => 'Research & Development', 'shortcode' => 'RD'],
            ['company' => 'company_2', 'name' => 'Finance', 'shortcode' => 'FIN'],

            // Oscorp Industries (company_3)
            ['company' => 'company_3', 'name' => 'Product Management', 'shortcode' => 'PM'],

            // Weyland-Yutani Corporation (company_4)
            ['company' => 'company_4', 'name' => 'Sales', 'shortcode' => 'SALES'],

            // Umbrella Corporation (company_5)
            ['company' => 'company_5', 'name' => 'Marketing', 'shortcode' => 'MKT'],

            // Tyrell Corporation (company_6)
            ['company' => 'company_6', 'name' => 'IT Operations', 'shortcode' => 'IT'],

            // Soylent Corporation (company_7)
            ['company' => 'company_7', 'name' => 'Design', 'shortcode' => 'DESIGN'],

            // Rekall Incorporated (company_8)
            ['company' => 'company_8', 'name' => 'Quality Assurance', 'shortcode' => 'QA'],

            // Initech (company_9)
            ['company' => 'company_9', 'name' => 'Analytics', 'shortcode' => 'ANLYTCS'],

            // Veridian Dynamics (company_10)
            ['company' => 'company_10', 'name' => 'Business Development', 'shortcode' => 'BD'],

            // Massive Dynamic (company_11)
            ['company' => 'company_11', 'name' => 'Human Resources', 'shortcode' => 'HR'],

            // Abstergo Industries (company_12)
            ['company' => 'company_12', 'name' => 'Legal', 'shortcode' => 'LEGAL'],

            // Aperture Science (company_13)
            ['company' => 'company_13', 'name' => 'Project Management', 'shortcode' => 'PM'],

            // Black Mesa (company_14)
            ['company' => 'company_14', 'name' => 'Research & Development', 'shortcode' => 'RD'],

            // Initrode (company_15)
            ['company' => 'company_15', 'name' => 'Compliance', 'shortcode' => 'CMPLNC'],

            // Globex Corporation (company_16)
            ['company' => 'company_16', 'name' => 'Sales', 'shortcode' => 'SALES'],

            // Hooli (company_17)
            ['company' => 'company_17', 'name' => 'Marketing', 'shortcode' => 'MKT'],

            // Pied Piper (company_18)
            ['company' => 'company_18', 'name' => 'Creative', 'shortcode' => 'CRTV'],
        ];

        foreach ($departmentsData as $data) {
            $company = $this->companies[$data['company']] ?? null;

            if (!$company) {
                continue; // Skip if company doesn't exist
            }

            $department = new Department();
            $department
                ->setName($data['name'])
                ->setShortcode($data['shortcode'])
                ->setCompany($company);

            $manager->persist($department);

            // Create a unique key for departments: company_X_department_name
            $key = $data['company'] . '_' . strtolower(str_replace([' ', '&'], ['_', 'and'], $data['name']));
            $this->departments[$key] = $department;
        }

        $manager->flush();
    }

    private function createContactFixtures(ObjectManager $manager): void
    {
        $contactsData = [
            // Original contacts
            [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'john.doe@example.com',
                'phone' => '+1 555 1001',
                'cell' => '+1 555 2001',
                'company' => 'company_0',
                'academicTitle' => null,
                'position' => 'Chief Technology Officer',
                'department' => 'Technology',
            ],
            [
                'firstName' => 'Jane',
                'lastName' => 'Smith',
                'email' => 'jane.smith@example.com',
                'phone' => '+1 555 1002',
                'cell' => '+1 555 2002',
                'company' => 'company_1',
                'academicTitle' => 'Ms.',
                'position' => 'Senior Software Engineer',
                'department' => 'Engineering',
            ],
            [
                'firstName' => 'Alice',
                'lastName' => 'Johnson',
                'email' => 'alice.johnson@example.com',
                'phone' => '+1 555 1003',
                'cell' => '+1 555 2003',
                'company' => 'company_2',
                'academicTitle' => 'Dr.',
                'position' => 'Director of Research',
                'department' => 'Research & Development',
            ],
            // 50 additional contacts
            [
                'firstName' => 'Michael',
                'lastName' => 'Brown',
                'email' => 'michael.brown@cyberdyne.example',
                'phone' => '+1 555 1004',
                'cell' => '+1 555 2004',
                'company' => 'company_0',
                'academicTitle' => null,
                'position' => 'Systems Architect',
                'department' => 'Engineering',
            ],
            [
                'firstName' => 'Sarah',
                'lastName' => 'Davis',
                'email' => 'sarah.davis@stark.example',
                'phone' => '+1 555 1005',
                'cell' => '+1 555 2005',
                'company' => 'company_1',
                'academicTitle' => 'Dr.',
                'position' => 'VP of Innovation',
                'department' => 'Research & Development',
            ],
            [
                'firstName' => 'Robert',
                'lastName' => 'Wilson',
                'email' => 'robert.wilson@wayne.example',
                'phone' => '+1 555 1006',
                'cell' => '+1 555 2006',
                'company' => 'company_2',
                'academicTitle' => 'Prof.',
                'position' => 'Chief Financial Officer',
                'department' => 'Finance',
            ],
            [
                'firstName' => 'Emma',
                'lastName' => 'Martinez',
                'email' => 'emma.martinez@oscorp.example',
                'phone' => '+1 555 1007',
                'cell' => '+1 555 2007',
                'company' => 'company_3',
                'academicTitle' => null,
                'position' => 'Product Manager',
                'department' => 'Product Management',
            ],
            [
                'firstName' => 'David',
                'lastName' => 'Anderson',
                'email' => 'david.anderson@weyland.example',
                'phone' => '+44 20 7946 1001',
                'cell' => '+44 7700 900001',
                'company' => 'company_4',
                'academicTitle' => 'Mr.',
                'position' => 'Sales Director',
                'department' => 'Sales',
            ],
            [
                'firstName' => 'Jessica',
                'lastName' => 'Taylor',
                'email' => 'jessica.taylor@umbrella.example',
                'phone' => '+49 30 123457',
                'cell' => '+49 170 123456',
                'company' => 'company_5',
                'academicTitle' => 'Dr.',
                'position' => 'Head of Marketing',
                'department' => 'Marketing',
            ],
            [
                'firstName' => 'Christopher',
                'lastName' => 'Thomas',
                'email' => 'christopher.thomas@genedyne.example',
                'phone' => '+1 416 555 0201',
                'cell' => '+1 416 555 0301',
                'company' => 'company_6',
                'academicTitle' => null,
                'position' => 'DevOps Engineer',
                'department' => 'IT Operations',
            ],
            [
                'firstName' => 'Amanda',
                'lastName' => 'Jackson',
                'email' => 'amanda.jackson@neurallink.example',
                'phone' => '+81 3 5555 0301',
                'cell' => '+81 90 1234 5678',
                'company' => 'company_7',
                'academicTitle' => 'Ms.',
                'position' => 'UX Designer',
                'department' => 'Design',
            ],
            [
                'firstName' => 'Daniel',
                'lastName' => 'White',
                'email' => 'daniel.white@parker.example',
                'phone' => '+1 555 0401',
                'cell' => '+1 555 0501',
                'company' => 'company_8',
                'academicTitle' => null,
                'position' => 'Quality Assurance Lead',
                'department' => 'Quality Assurance',
            ],
            [
                'firstName' => 'Lisa',
                'lastName' => 'Harris',
                'email' => 'lisa.harris@pym.example',
                'phone' => '+1 415 555 0501',
                'cell' => '+1 415 555 0601',
                'company' => 'company_9',
                'academicTitle' => 'Dr.',
                'position' => 'Data Scientist',
                'department' => 'Analytics',
            ],
            [
                'firstName' => 'Matthew',
                'lastName' => 'Martin',
                'email' => 'matthew.martin@rand.example',
                'phone' => '+1 555 0601',
                'cell' => '+1 555 0701',
                'company' => 'company_10',
                'academicTitle' => null,
                'position' => 'Business Analyst',
                'department' => 'Business Development',
            ],
            [
                'firstName' => 'Ashley',
                'lastName' => 'Garcia',
                'email' => 'ashley.garcia@queen.example',
                'phone' => '+1 206 555 0701',
                'cell' => '+1 206 555 0801',
                'company' => 'company_11',
                'academicTitle' => 'Ms.',
                'position' => 'HR Manager',
                'department' => 'Human Resources',
            ],
            [
                'firstName' => 'James',
                'lastName' => 'Rodriguez',
                'email' => 'james.rodriguez@lexcorp.example',
                'phone' => '+1 555 0801',
                'cell' => '+1 555 0901',
                'company' => 'company_12',
                'academicTitle' => 'Prof.',
                'position' => 'Legal Counsel',
                'department' => 'Legal',
            ],
            [
                'firstName' => 'Michelle',
                'lastName' => 'Lewis',
                'email' => 'michelle.lewis@kord.example',
                'phone' => '+1 773 555 0901',
                'cell' => '+1 773 555 1001',
                'company' => 'company_13',
                'academicTitle' => null,
                'position' => 'Project Manager',
                'department' => 'Project Management',
            ],
            [
                'firstName' => 'Ryan',
                'lastName' => 'Lee',
                'email' => 'ryan.lee@tyrell.example',
                'phone' => '+1 213 555 1001',
                'cell' => '+1 213 555 1101',
                'company' => 'company_14',
                'academicTitle' => 'Dr.',
                'position' => 'Research Scientist',
                'department' => 'Research & Development',
            ],
            [
                'firstName' => 'Stephanie',
                'lastName' => 'Walker',
                'email' => 'stephanie.walker@seegson.example',
                'phone' => '+33 1 55 55 1101',
                'cell' => '+33 6 12 34 56 78',
                'company' => 'company_15',
                'academicTitle' => 'Ms.',
                'position' => 'Compliance Officer',
                'department' => 'Compliance',
            ],
            [
                'firstName' => 'Kevin',
                'lastName' => 'Hall',
                'email' => 'kevin.hall@tricell.example',
                'phone' => '+27 11 555 1201',
                'cell' => '+27 82 123 4567',
                'company' => 'company_16',
                'academicTitle' => null,
                'position' => 'Account Manager',
                'department' => 'Sales',
            ],
            [
                'firstName' => 'Nicole',
                'lastName' => 'Allen',
                'email' => 'nicole.allen@terrasave.example',
                'phone' => '+61 2 5555 1301',
                'cell' => '+61 4 1234 5678',
                'company' => 'company_17',
                'academicTitle' => 'Dr.',
                'position' => 'Content Strategist',
                'department' => 'Marketing',
            ],
            [
                'firstName' => 'Brandon',
                'lastName' => 'Young',
                'email' => 'brandon.young@blueumbrella.example',
                'phone' => '+44 20 7555 1401',
                'cell' => '+44 7700 900002',
                'company' => 'company_18',
                'academicTitle' => null,
                'position' => 'Creative Director',
                'department' => 'Creative',
            ],
            // Additional contacts for larger companies
            [
                'firstName' => 'Samantha',
                'lastName' => 'King',
                'email' => 'samantha.king@cyberdyne.example',
                'phone' => '+1 555 1008',
                'cell' => '+1 555 2008',
                'company' => 'company_0',
                'academicTitle' => 'Ms.',
            ],
            [
                'firstName' => 'Jonathan',
                'lastName' => 'Wright',
                'email' => 'jonathan.wright@stark.example',
                'phone' => '+1 555 1009',
                'cell' => '+1 555 2009',
                'company' => 'company_1',
                'academicTitle' => null,
            ],
            [
                'firstName' => 'Rebecca',
                'lastName' => 'Lopez',
                'email' => 'rebecca.lopez@wayne.example',
                'phone' => '+1 555 1010',
                'cell' => '+1 555 2010',
                'company' => 'company_2',
                'academicTitle' => 'Dr.',
            ],
            [
                'firstName' => 'Tyler',
                'lastName' => 'Hill',
                'email' => 'tyler.hill@oscorp.example',
                'phone' => '+1 555 1011',
                'cell' => '+1 555 2011',
                'company' => 'company_3',
                'academicTitle' => null,
            ],
            [
                'firstName' => 'Megan',
                'lastName' => 'Scott',
                'email' => 'megan.scott@weyland.example',
                'phone' => '+44 20 7946 1002',
                'cell' => '+44 7700 900003',
                'company' => 'company_4',
                'academicTitle' => 'Prof.',
            ],
            [
                'firstName' => 'Gregory',
                'lastName' => 'Green',
                'email' => 'gregory.green@umbrella.example',
                'phone' => '+49 30 123458',
                'cell' => '+49 170 123457',
                'company' => 'company_5',
                'academicTitle' => null,
            ],
            [
                'firstName' => 'Rachel',
                'lastName' => 'Adams',
                'email' => 'rachel.adams@genedyne.example',
                'phone' => '+1 416 555 0202',
                'cell' => '+1 416 555 0302',
                'company' => 'company_6',
                'academicTitle' => 'Ms.',
            ],
            [
                'firstName' => 'Nathan',
                'lastName' => 'Baker',
                'email' => 'nathan.baker@neurallink.example',
                'phone' => '+81 3 5555 0302',
                'cell' => '+81 90 1234 5679',
                'company' => 'company_7',
                'academicTitle' => 'Dr.',
            ],
            [
                'firstName' => 'Katherine',
                'lastName' => 'Gonzalez',
                'email' => 'katherine.gonzalez@parker.example',
                'phone' => '+1 555 0402',
                'cell' => '+1 555 0502',
                'company' => 'company_8',
                'academicTitle' => null,
            ],
            [
                'firstName' => 'Jacob',
                'lastName' => 'Nelson',
                'email' => 'jacob.nelson@pym.example',
                'phone' => '+1 415 555 0502',
                'cell' => '+1 415 555 0602',
                'company' => 'company_9',
                'academicTitle' => null,
            ],
            [
                'firstName' => 'Victoria',
                'lastName' => 'Carter',
                'email' => 'victoria.carter@rand.example',
                'phone' => '+1 555 0602',
                'cell' => '+1 555 0702',
                'company' => 'company_10',
                'academicTitle' => 'Ms.',
            ],
            [
                'firstName' => 'Alexander',
                'lastName' => 'Mitchell',
                'email' => 'alexander.mitchell@queen.example',
                'phone' => '+1 206 555 0702',
                'cell' => '+1 206 555 0802',
                'company' => 'company_11',
                'academicTitle' => 'Dr.',
            ],
            [
                'firstName' => 'Hannah',
                'lastName' => 'Perez',
                'email' => 'hannah.perez@lexcorp.example',
                'phone' => '+1 555 0802',
                'cell' => '+1 555 0902',
                'company' => 'company_12',
                'academicTitle' => null,
            ],
            [
                'firstName' => 'Ethan',
                'lastName' => 'Roberts',
                'email' => 'ethan.roberts@kord.example',
                'phone' => '+1 773 555 0902',
                'cell' => '+1 773 555 1002',
                'company' => 'company_13',
                'academicTitle' => null,
            ],
            [
                'firstName' => 'Olivia',
                'lastName' => 'Turner',
                'email' => 'olivia.turner@tyrell.example',
                'phone' => '+1 213 555 1002',
                'cell' => '+1 213 555 1102',
                'company' => 'company_14',
                'academicTitle' => 'Prof.',
            ],
            [
                'firstName' => 'Joshua',
                'lastName' => 'Phillips',
                'email' => 'joshua.phillips@seegson.example',
                'phone' => '+33 1 55 55 1102',
                'cell' => '+33 6 12 34 56 79',
                'company' => 'company_15',
                'academicTitle' => null,
            ],
            [
                'firstName' => 'Lauren',
                'lastName' => 'Campbell',
                'email' => 'lauren.campbell@tricell.example',
                'phone' => '+27 11 555 1202',
                'cell' => '+27 82 123 4568',
                'company' => 'company_16',
                'academicTitle' => 'Dr.',
            ],
            [
                'firstName' => 'Andrew',
                'lastName' => 'Parker',
                'email' => 'andrew.parker@terrasave.example',
                'phone' => '+61 2 5555 1302',
                'cell' => '+61 4 1234 5679',
                'company' => 'company_17',
                'academicTitle' => null,
            ],
            [
                'firstName' => 'Grace',
                'lastName' => 'Evans',
                'email' => 'grace.evans@blueumbrella.example',
                'phone' => '+44 20 7555 1402',
                'cell' => '+44 7700 900004',
                'company' => 'company_18',
                'academicTitle' => 'Ms.',
            ],
            [
                'firstName' => 'Benjamin',
                'lastName' => 'Edwards',
                'email' => 'benjamin.edwards@cyberdyne.example',
                'phone' => '+1 555 1012',
                'cell' => '+1 555 2012',
                'company' => 'company_0',
                'academicTitle' => null,
            ],
            [
                'firstName' => 'Chloe',
                'lastName' => 'Collins',
                'email' => 'chloe.collins@stark.example',
                'phone' => '+1 555 1013',
                'cell' => '+1 555 2013',
                'company' => 'company_1',
                'academicTitle' => 'Dr.',
            ],
            [
                'firstName' => 'Noah',
                'lastName' => 'Stewart',
                'email' => 'noah.stewart@wayne.example',
                'phone' => '+1 555 1014',
                'cell' => '+1 555 2014',
                'company' => 'company_2',
                'academicTitle' => null,
            ],
            [
                'firstName' => 'Sophia',
                'lastName' => 'Sanchez',
                'email' => 'sophia.sanchez@oscorp.example',
                'phone' => '+1 555 1015',
                'cell' => '+1 555 2015',
                'company' => 'company_3',
                'academicTitle' => 'Prof.',
            ],
            [
                'firstName' => 'Mason',
                'lastName' => 'Morris',
                'email' => 'mason.morris@weyland.example',
                'phone' => '+44 20 7946 1003',
                'cell' => '+44 7700 900005',
                'company' => 'company_4',
                'academicTitle' => null,
            ],
            [
                'firstName' => 'Isabella',
                'lastName' => 'Rogers',
                'email' => 'isabella.rogers@umbrella.example',
                'phone' => '+49 30 123459',
                'cell' => '+49 170 123458',
                'company' => 'company_5',
                'academicTitle' => 'Ms.',
            ],
            [
                'firstName' => 'Liam',
                'lastName' => 'Reed',
                'email' => 'liam.reed@genedyne.example',
                'phone' => '+1 416 555 0203',
                'cell' => '+1 416 555 0303',
                'company' => 'company_6',
                'academicTitle' => 'Dr.',
            ],
            [
                'firstName' => 'Ava',
                'lastName' => 'Cook',
                'email' => 'ava.cook@neurallink.example',
                'phone' => '+81 3 5555 0303',
                'cell' => '+81 90 1234 5680',
                'company' => 'company_7',
                'academicTitle' => null,
            ],
            [
                'firstName' => 'William',
                'lastName' => 'Morgan',
                'email' => 'william.morgan@parker.example',
                'phone' => '+1 555 0403',
                'cell' => '+1 555 0503',
                'company' => 'company_8',
                'academicTitle' => null,
            ],
            [
                'firstName' => 'Charlotte',
                'lastName' => 'Bailey',
                'email' => 'charlotte.bailey@pym.example',
                'phone' => '+1 415 555 0503',
                'cell' => '+1 415 555 0603',
                'company' => 'company_9',
                'academicTitle' => 'Prof.',
            ],
            [
                'firstName' => 'Henry',
                'lastName' => 'Rivera',
                'email' => 'henry.rivera@rand.example',
                'phone' => '+1 555 0603',
                'cell' => '+1 555 0703',
                'company' => 'company_10',
                'academicTitle' => null,
            ],
            [
                'firstName' => 'Amelia',
                'lastName' => 'Cooper',
                'email' => 'amelia.cooper@queen.example',
                'phone' => '+1 206 555 0703',
                'cell' => '+1 206 555 0803',
                'company' => 'company_11',
                'academicTitle' => 'Ms.',
            ],
            [
                'firstName' => 'Lucas',
                'lastName' => 'Richardson',
                'email' => 'lucas.richardson@lexcorp.example',
                'phone' => '+1 555 0803',
                'cell' => '+1 555 0903',
                'company' => 'company_12',
                'academicTitle' => 'Dr.',
            ],
            [
                'firstName' => 'Harper',
                'lastName' => 'Cox',
                'email' => 'harper.cox@kord.example',
                'phone' => '+1 773 555 0903',
                'cell' => '+1 773 555 1003',
                'company' => 'company_13',
                'academicTitle' => null,
            ],
            [
                'firstName' => 'Owen',
                'lastName' => 'Ward',
                'email' => 'owen.ward@tyrell.example',
                'phone' => '+1 213 555 1003',
                'cell' => '+1 213 555 1103',
                'company' => 'company_14',
                'academicTitle' => null,
            ],
            [
                'firstName' => 'Ella',
                'lastName' => 'Torres',
                'email' => 'ella.torres@seegson.example',
                'phone' => '+33 1 55 55 1103',
                'cell' => '+33 6 12 34 56 80',
                'company' => 'company_15',
                'academicTitle' => 'Prof.',
            ],
        ];

        foreach ($contactsData as $index => $contactData) {
            $company = $this->companies[$contactData['company']] ?? null;

            $contact = (new Contact())
                ->setFirstName($contactData['firstName'])
                ->setLastName($contactData['lastName'])
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

            // Set department relationship if provided
            if (isset($contactData['department']) && isset($contactData['company'])) {
                // Build department key: company_X_department_name
                $departmentKey = $contactData['company'] . '_' . strtolower(str_replace([' ', '&'], ['_', 'and'], $contactData['department']));
                $department = $this->departments[$departmentKey] ?? null;

                if ($department) {
                    $contact->setDepartment($department);
                }
            }

            $manager->persist($contact);
            $this->contacts['contact_' . $index] = $contact;
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
                ->setDescription($projectData['description'])
                ->setTransaction($transaction)
                ->setAssignee($assignee)
                ->setDueDate($projectData['dueDate']);

            $this->persistAndFlush($manager, $project); // Persist and flush to get ID for category assignment

            // Add category after entity is persisted
            if ($category) {
                $projectId = $project->getId();
                if (null === $projectId) {
                    throw new \LogicException('Project ID should not be null after persist and flush');
                }

                $assignment = new CategorizableEntity();
                $assignment->setCategory($category);
                $assignment->setEntityType(DomainEntityType::Project);
                $assignment->setEntityId($projectId->toString());
                $manager->persist($assignment);
            }

            $this->projects['project_' . $index] = $project;
        }

        $this->flushSafely($manager);
    }

    private function createCampaignFixtures(ObjectManager $manager): void
    {
        $campaignsData = [
            [
                'name' => 'Digital Transformation 2025',
                'shortcode' => 'DT2025',
                'description' => 'Comprehensive digital transformation initiative focusing on modernizing legacy systems and implementing cutting-edge technologies across multiple client organizations. This strategic campaign encompasses AI integration, cloud migration, and automation solutions.',
                'category' => 'main1', // Technology
                'startDate' => new \DateTimeImmutable('2024-01-15'),
                'endDate' => new \DateTimeImmutable('2025-12-31'),
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
                'shortcode' => 'GME2024',
                'description' => 'Multi-company marketing campaign focusing on brand management, digital marketing automation, and content creation strategies for international markets. Includes social media optimization, SEO enhancement, and customer engagement analytics.',
                'category' => 'main3', // Marketing & Sales
                'startDate' => new \DateTimeImmutable('2024-03-01'),
                'endDate' => new \DateTimeImmutable('2024-11-30'),
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
                'shortcode' => 'ESC2024',
                'description' => 'Strategic initiative to enhance security infrastructure and ensure regulatory compliance across all client operations. Includes legal document management, corporate intelligence systems, and comprehensive audit trail implementation.',
                'category' => 'main2', // Business Services
                'startDate' => new \DateTimeImmutable('2024-02-01'),
                'endDate' => new \DateTimeImmutable('2025-01-31'),
                'projects' => [
                    'project_2', // Automated Defense Network
                    'project_6', // Corporate Security Upgrade
                    'project_22', // Legal Document Management
                    'project_23', // Corporate Intelligence System
                    'project_26', // Legal Compliance Platform
                    'project_5', // Business Process Optimization
                ],
            ],
            [
                'name' => 'Innovation Lab 2024',
                'shortcode' => 'INNO2024',
                'description' => 'Research and development initiative focused on emerging technologies and innovative solutions. Exploring quantum computing, neural interfaces, blockchain integration, and advanced data analytics for next-generation applications.',
                'category' => 'main1', // Technology
                'startDate' => new \DateTimeImmutable('2024-01-01'),
                'endDate' => new \DateTimeImmutable('2024-12-31'),
                'projects' => [
                    'project_14', // Neural Interface Development
                    'project_16', // Microservices Architecture
                    'project_17', // Quantum Realm Analytics
                    'project_27', // AI Content Generation
                ],
            ],
            [
                'name' => 'Customer Experience Revolution',
                'shortcode' => 'CXR2024',
                'description' => 'Comprehensive customer experience enhancement campaign focusing on user interface optimization, customer journey mapping, and personalization technologies. Aimed at increasing customer satisfaction and retention across all touchpoints.',
                'category' => 'main3', // Marketing & Sales
                'startDate' => new \DateTimeImmutable('2024-04-01'),
                'endDate' => new \DateTimeImmutable('2025-03-31'),
                'projects' => [
                    'project_8', // Enterprise CMS
                    'project_12', // Pharmaceutical Research Portal
                    'project_16', // Mobile Commerce App
                    'project_24', // Web Portal Redesign
                ],
            ],
            [
                'name' => 'Sustainable Operations Initiative',
                'shortcode' => 'SOI2024',
                'description' => 'Environmental sustainability and operational efficiency campaign focusing on green technologies, energy optimization, and sustainable business practices. Includes carbon footprint reduction and renewable energy integration projects.',
                'category' => 'main4', // Consulting
                'startDate' => new \DateTimeImmutable('2024-06-01'),
                'endDate' => new \DateTimeImmutable('2025-05-31'),
                'projects' => [
                    'project_21', // Green Arrow Logistics
                    'project_5', // Business Process Optimization
                    'project_20', // Iron Fist Training Platform
                ],
            ],
            [
                'name' => 'Financial Technology Modernization',
                'shortcode' => 'FINTECH2024',
                'description' => 'Comprehensive financial technology upgrade campaign focusing on banking solutions, payment processing, and financial analytics. Includes blockchain integration, cryptocurrency support, and advanced fraud detection systems.',
                'category' => 'sub4', // Financial Services
                'startDate' => new \DateTimeImmutable('2024-05-01'),
                'endDate' => new \DateTimeImmutable('2025-04-30'),
                'projects' => [
                    'project_4', // Mobile Banking App
                    'project_7', // Financial Portfolio Management
                    'project_19', // Financial Analytics Platform
                ],
            ],
            [
                'name' => 'Healthcare Technology Advancement',
                'shortcode' => 'HEALTH2024',
                'description' => 'Medical and healthcare technology enhancement initiative focusing on patient care optimization, medical data analytics, and telemedicine solutions. Includes clinical trial management and pharmaceutical research platforms.',
                'category' => 'main2', // Business Services
                'startDate' => new \DateTimeImmutable('2024-07-01'),
                'endDate' => new \DateTimeImmutable('2025-06-30'),
                'projects' => [
                    'project_12', // Pharmaceutical Research Portal
                    'project_25', // Pharmaceutical CRM
                    'project_26', // Clinical Trial Management
                ],
            ],
        ];

        foreach ($campaignsData as $index => $campaignData) {
            $category = $this->categories[$campaignData['category']] ?? null;

            $campaign = (new Campaign())
                ->setName($campaignData['name'])
                ->setShortcode($campaignData['shortcode'])
                ->setDescription($campaignData['description'])
            ;

            // Set start and end dates (always present in campaign data)
            $campaign->setStartedAt($campaignData['startDate']);
            $campaign->setEndedAt($campaignData['endDate']);

            // Assign projects to campaign
            foreach ($campaignData['projects'] as $projectKey) {
                $project = $this->projects[$projectKey] ?? null;
                if ($project) {
                    $campaign->addProject($project);
                }
            }

            $this->persistAndFlush($manager, $campaign); // Persist and flush to get ID for category assignment

            // Add category after entity is persisted
            if ($category) {
                $campaignId = $campaign->getId();
                if (null === $campaignId) {
                    throw new \LogicException('Campaign ID should not be null after persist and flush');
                }

                $assignment = new CategorizableEntity();
                $assignment->setCategory($category);
                $assignment->setEntityType(DomainEntityType::Campaign);
                $assignment->setEntityId($campaignId->toString());
                $manager->persist($assignment);
            }

            $this->campaigns['campaign_' . $index] = $campaign;
        }

        $this->flushSafely($manager);
    }
}
