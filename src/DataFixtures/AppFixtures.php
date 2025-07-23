<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Company;
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

    // Referenzen für später verwendete Entities
    private array $users = [];
    private array $systemEntities = [];
    private array $categories = [];

    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Reihenfolge ist wichtig für Abhängigkeiten
        $this->createSystemEntityFixtures($manager);
        $this->createUserFixtures($manager);
        $this->createPermissionFixtures($manager);
        $this->createCategoryFixtures($manager);
        $this->createCompanyFixtures($manager);
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
                'text' => 'Benutzerverwaltung',
                'icon' => 'fas fa-users',
            ],
            'Company' => [
                'name' => 'Companies',
                'text' => 'Kunden, Lieferanten, Partner etc.',
                'icon' => 'fas fa-building',
            ],
            'CompanyGroup' => [
                'name' => 'CompanyGroups',
                'text' => 'Gruppen von Unternehmen',
                'icon' => 'fas fa-layer-group',
            ],
            'Project' => [
                'name' => 'Projects',
                'text' => 'Projekte verwalten',
                'icon' => 'fas fa-project-diagram',
            ],
            'Category' => [
                'name' => 'Category',
                'text' => 'Kategorien verwalten',
                'icon' => 'fas fa-project-diagram',
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

    private function createUserFixtures(ObjectManager $manager): void
    {
        $usersData = [
            'admin' => [
                'email' => 'admin@example.com',
                'roles' => ['ROLE_ADMIN'],
                'active' => true,
                'notes' => 'Administrator user with full access',
            ],
            'demo' => [
                'email' => 'demo@example.com',
                'roles' => ['ROLE_USER'],
                'active' => true,
                'notes' => 'Demo user with limited access',
            ],
        ];

        foreach ($usersData as $key => $userData) {
            $user = new User();
            $user->setEmail($userData['email'])
                ->setPassword($this->hasher->hashPassword($user, self::DEFAULT_PASSWORD))
                ->setRoles($userData['roles'])
                ->setActive($userData['active'])
                ->setNotes($userData['notes']);

            $manager->persist($user);
            $this->users[$key] = $user;
        }

        $manager->flush();
    }

    private function createPermissionFixtures(ObjectManager $manager): void
    {
        // Permissions für Admin (alle Rechte)
        $adminPermissions = [
            'User' => ['read' => true, 'write' => true],
            'Company' => ['read' => true, 'write' => true],
            'SystemEntity' => ['read' => true, 'write' => true],
            'CompanyGroup' => ['read' => true, 'write' => true],
            'Project' => ['read' => true, 'write' => true],
            'Category' => ['read' => true, 'write' => true],
        ];

        // Permissions für Demo User (begrenzte Rechte)
        $demoPermissions = [
            'User' => ['read' => true, 'write' => false],
            'Company' => ['read' => true, 'write' => true],
            'Project' => ['read' => true, 'write' => false],
            'Category' => ['read' => true, 'write' => true],
        ];

        $this->createUserPermissions($manager, $this->users['admin'], $adminPermissions);
        $this->createUserPermissions($manager, $this->users['demo'], $demoPermissions);

        $manager->flush();
    }

    private function createUserPermissions(ObjectManager $manager, User $user, array $permissions): void
    {
        foreach ($permissions as $entityCode => $rights) {
            $systemEntity = $this->systemEntities[$entityCode] ?? null;
            if (!$systemEntity) {
                continue;
            }

            // Prüfen ob Permission bereits existiert
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

    private function createCategoryFixtures(ObjectManager $manager): void
    {
        $categoriesData = [
            'main1' => [
                'name' => 'Technology',
                'color' => 'blue',
                'icon' => 'fas fa-laptop-code',
            ],
            'main2' => [
                'name' => 'Business',
                'color' => 'red',
                'icon' => 'fas fa-briefcase',
            ],
            'main3' => [
                'name' => 'Marketing',
                'color' => 'green',
                'icon' => 'fas fa-bullhorn',
            ],
        ];

        // Erst die Hauptkategorien erstellen
        foreach ($categoriesData as $key => $data) {
            $category = (new Category())
                ->setName($data['name'])
                ->setColor($data['color'])
                ->setIcon($data['icon']);

            $manager->persist($category);
            $this->categories[$key] = $category;
        }

        $manager->flush();

        // Dann Unterkategorien
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
                'name' => 'Sales',
                'color' => 'lightred',
                'icon' => 'fas fa-chart-line',
                'parent' => 'main2',
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
                'category' => 'main2', // Business
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
                'category' => 'main3', // Marketing
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

        foreach ($companiesData as $companyData) {
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
            ],
            [
                'name' => 'Mobile App Development',
                'status' => ProjectStatus::PLANNING,
                'description' => 'Cross-platform mobile application for client management',
            ],
            [
                'name' => 'Data Migration Project',
                'status' => ProjectStatus::ON_HOLD,
                'description' => 'Legacy system data migration to new infrastructure',
            ],
            [
                'name' => 'API Integration',
                'status' => ProjectStatus::IN_PROGRESS,
                'description' => 'Third-party API integration and documentation',
            ],
        ];

        foreach ($projectsData as $projectData) {
            $project = (new Project())
                ->setName($projectData['name'])
                ->setStatus($projectData['status'])
                ->setDescription($projectData['description']);

            $manager->persist($project);
        }

        $manager->flush();
    }
}
