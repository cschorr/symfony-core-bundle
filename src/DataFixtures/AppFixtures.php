<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

use App\Entity\SystemEntity;
use App\Entity\User;
use App\Entity\UserSystemEntityPermission;
use App\Entity\Company;
use App\Entity\Project;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        // load entities
        $this->createSystemEntityFixtures($manager);
        $this->createUserFixtures($manager);
        $this->createPermissionFixtures($manager);
        $this->createCompanyFixtures($manager);
        $this->createProjectFixtures($manager);
    }

    public function createUserFixtures(ObjectManager $manager): void
    {
        $users = [
            ['email' => 'admin@example.com', 'password' => 'pass_1234', 'roles' => ['ROLE_ADMIN']],
            ['email' => 'demo@example.com', 'password' => 'pass_1234', 'roles' => ['ROLE_USER']],
        ];

        foreach ($users as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setPassword($this->hasher->hashPassword($user, $userData['password']));
            $user->setRoles($userData['roles']);
            $user->setActive(true);
            $user->setNotes('This is a default user.');

            $manager->persist($user);
        }
        $manager->flush();
    }

    public function createSystemEntityFixtures(ObjectManager $manager): void
    {
        // System entities in navigation order - they will be sorted by ID (UUID) in ascending order
        // The 'code' field is the singular form, 'name' field is the plural form
        // Navigation uses $systemEntity->getName() which returns the plural form for translation
        $systemEntities = [
            ['name' => 'SystemEntities', 'code' => 'SystemEntity', 'text' => 'System entities and configuration', 'icon' => 'fas fa-list'],
            ['name' => 'Users', 'code' => 'User', 'text' => 'Benutzerverwaltung', 'icon' => 'fas fa-users'],
            ['name' => 'Companies', 'code' => 'Company', 'text' => 'Kunden, Lieferanten, Partner etc.', 'icon' => 'fas fa-building'],
            ['name' => 'CompanyGroups', 'code' => 'CompanyGroup', 'text' => 'Gruppen von Unternehmen', 'icon' => 'fas fa-layer-group'],
            ['name' => 'Projects', 'code' => 'Project', 'text' => 'Projekte verwalten', 'icon' => 'fas fa-project-diagram'],
        ];

        // Create and persist system entity entities here
        foreach ($systemEntities as $systemEntityData) {
            $systemEntity = new SystemEntity();
            $systemEntity->setName($systemEntityData['name']);
            $systemEntity->setCode($systemEntityData['code']);
            $systemEntity->setText($systemEntityData['text']);
            $systemEntity->setIcon($systemEntityData['icon']);
            $manager->persist($systemEntity);
        }
        $manager->flush();
    }

    public function createPermissionFixtures(ObjectManager $manager): void
    {
        // Get users and system entities from the database
        $adminUser = $manager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);
        $demoUser = $manager->getRepository(User::class)->findOneBy(['email' => 'demo@example.com']);
        
        $userSystemEntity = $manager->getRepository(SystemEntity::class)->findOneBy(['code' => 'User']);
        $companySystemEntity = $manager->getRepository(SystemEntity::class)->findOneBy(['code' => 'Company']);
        $systemEntitySystemEntity = $manager->getRepository(SystemEntity::class)->findOneBy(['code' => 'SystemEntity']);
        $companyGroupSystemEntity = $manager->getRepository(SystemEntity::class)->findOneBy(['code' => 'CompanyGroup']);
        $projectSystemEntity = $manager->getRepository(SystemEntity::class)->findOneBy(['code' => 'Project']);

        // Helper function to create permission if it doesn't exist
        $createPermissionIfNotExists = function($user, $systemEntity, $canRead, $canWrite) use ($manager) {
            if (!$user || !$systemEntity) return;
            
            // Check if permission already exists
            $existingPermission = $manager->getRepository(UserSystemEntityPermission::class)
                ->findOneBy(['user' => $user, 'systemEntity' => $systemEntity]);
            
            if (!$existingPermission) {
                $permission = new UserSystemEntityPermission();
                $permission->setUser($user);
                $permission->setSystemEntity($systemEntity);
                $permission->setCanRead($canRead);
                $permission->setCanWrite($canWrite);
                $manager->persist($permission);
            }
        };

        // Admin has full access to all system entities
        $createPermissionIfNotExists($adminUser, $userSystemEntity, true, true);
        $createPermissionIfNotExists($adminUser, $companySystemEntity, true, true);
        $createPermissionIfNotExists($adminUser, $systemEntitySystemEntity, true, true);
        $createPermissionIfNotExists($adminUser, $companyGroupSystemEntity, true, true);
        $createPermissionIfNotExists($adminUser, $projectSystemEntity, true, true);

        // Demo user permissions
        $createPermissionIfNotExists($demoUser, $userSystemEntity, true, false); // Read-only access to user system entity
        $createPermissionIfNotExists($demoUser, $companySystemEntity, true, true); // Full access to company system entity

        $manager->flush();
    }

    public function createCompanyFixtures(ObjectManager $manager): void
    {
        // Example company data
        $companies = [
            ['name' => 'Stake holder', 'email' => 'info@example.com', 'country' => 'DE'],
            ['name' => 'Demo Client', 'email' => 'info@demo.com', 'country' => 'DE'],
            ['name' => 'Test Company', 'email' => 'info@test.com', 'country' => 'DE'],
        ];

        // Create and persist company entities here
        foreach ($companies as $companyData) {
            $company = new Company();
            $company->setName($companyData['name']);
            $company->setEmail($companyData['email']);
            $company->setCountryCode($companyData['country']);
            $manager->persist($company);
        }
        $manager->flush();
    }

    public function createProjectFixtures(ObjectManager $manager): void
    {
        // Example project data
        $projects = [
            ['name' => 'Project Alpha', 'status' => 1, 'description' => 'First project description'],
            ['name' => 'Project Beta', 'status' => 2, 'description' => 'Second project description'],
            ['name' => 'Project Gamma', 'status' => 0, 'description' => 'Third project description'],
        ];

        // Create and persist project entities here
        foreach ($projects as $projectData) {
            $project = new Project();
            $project->setName($projectData['name']);
            $project->setStatus($projectData['status']);
            $project->setDescription($projectData['description']);
            $manager->persist($project);
        }
        $manager->flush();
    }
}