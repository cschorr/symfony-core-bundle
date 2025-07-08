<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

use App\Entity\Module;
use App\Entity\User;
use App\Entity\UserModulePermission;
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
        // each module representing one entity
        $modules = [
            // Add your module data here
            ['name' => 'Benutzer', 'text' => 'Benutzerverwaltung'],
            ['name' => 'Unternehmen', 'text' => 'Kunden, Lieferanten, Partner etc.'],
            ['name' => 'Module', 'text' => 'System modules and configuration'],
        ];

        // Create and persist module entities here
        foreach ($modules as $moduleData) {
            $module = new Module();
            $module->setName($moduleData['name']);
            $module->setText($moduleData['text']);
            $manager->persist($module);
        }
        $manager->flush();
    }

    public function createPermissionFixtures(ObjectManager $manager): void
    {
        // Get users and modules from the database
        $adminUser = $manager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);
        $demoUser = $manager->getRepository(User::class)->findOneBy(['email' => 'demo@example.com']);
        
        $userModule = $manager->getRepository(Module::class)->findOneBy(['name' => 'Benutzer']);
        $companyModule = $manager->getRepository(Module::class)->findOneBy(['name' => 'Unternehmen']);
        $moduleModule = $manager->getRepository(Module::class)->findOneBy(['name' => 'Module']);

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

        // Demo user permissions
        $createPermissionIfNotExists($demoUser, $userModule, true, false); // Read-only access to user module
        $createPermissionIfNotExists($demoUser, $companyModule, true, true); // Full access to company module

        $manager->flush();
    }
}
