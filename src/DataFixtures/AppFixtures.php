<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

use App\Entity\Module;
use App\Entity\User;
use App\Entity\UserModulePermission;
use App\Entity\Company;
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
        $this->createModuleFixtures($manager);
        $this->createUserFixtures($manager);
        $this->createPermissionFixtures($manager);
        $this->createCompanyFixtures($manager);
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

    public function createModuleFixtures(ObjectManager $manager): void
    {
        // Modules in navigation order - they will be sorted by ID (UUID) in ascending order
        // The 'code' field is used for translation, 'name' field is for admin display
        $modules = [
            ['name' => 'System Module', 'code' => 'Module', 'text' => 'System modules and configuration', 'icon' => 'fas fa-list'],
            ['name' => 'Benutzer', 'code' => 'User', 'text' => 'Benutzerverwaltung', 'icon' => 'fas fa-users'],
            ['name' => 'Unternehmen', 'code' => 'Company', 'text' => 'Kunden, Lieferanten, Partner etc.', 'icon' => 'fas fa-building'],
            ['name' => 'Unternehmensgruppen', 'code' => 'CompanyGroup', 'text' => 'Gruppen von Unternehmen', 'icon' => 'fas fa-layer-group'],
            ['name' => 'Projekte', 'code' => 'Project', 'text' => 'Projekte verwalten', 'icon' => 'fas fa-project-diagram'],
        ];

        // Create and persist module entities here
        foreach ($modules as $moduleData) {
            $module = new Module();
            $module->setName($moduleData['name']);
            $module->setCode($moduleData['code']);
            $module->setText($moduleData['text']);
            $module->setIcon($moduleData['icon']);
            $manager->persist($module);
        }
        $manager->flush();
    }

    public function createPermissionFixtures(ObjectManager $manager): void
    {
        // Get users and modules from the database
        $adminUser = $manager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);
        $demoUser = $manager->getRepository(User::class)->findOneBy(['email' => 'demo@example.com']);
        
        $userModule = $manager->getRepository(Module::class)->findOneBy(['code' => 'User']);
        $companyModule = $manager->getRepository(Module::class)->findOneBy(['code' => 'Company']);
        $moduleModule = $manager->getRepository(Module::class)->findOneBy(['code' => 'Module']);
        $companyGroupModule = $manager->getRepository(Module::class)->findOneBy(['code' => 'CompanyGroup']);
        $projectModule = $manager->getRepository(Module::class)->findOneBy(['code' => 'Project']);

        // Helper function to create permission if it doesn't exist
        $createPermissionIfNotExists = function($user, $module, $canRead, $canWrite) use ($manager) {
            if (!$user || !$module) return;
            
            // Check if permission already exists
            $existingPermission = $manager->getRepository(UserModulePermission::class)
                ->findOneBy(['user' => $user, 'module' => $module]);
            
            if (!$existingPermission) {
                $permission = new UserModulePermission();
                $permission->setUser($user);
                $permission->setModule($module);
                $permission->setCanRead($canRead);
                $permission->setCanWrite($canWrite);
                $manager->persist($permission);
            }
        };

        // Admin has full access to all modules
        $createPermissionIfNotExists($adminUser, $userModule, true, true);
        $createPermissionIfNotExists($adminUser, $companyModule, true, true);
        $createPermissionIfNotExists($adminUser, $moduleModule, true, true);
        $createPermissionIfNotExists($adminUser, $companyGroupModule, true, true);
        $createPermissionIfNotExists($adminUser, $projectModule, true, true);

        // Demo user permissions
        $createPermissionIfNotExists($demoUser, $userModule, true, false); // Read-only access to user module
        $createPermissionIfNotExists($demoUser, $companyModule, true, true); // Full access to company module

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
}