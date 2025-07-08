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
            $user->setCreatedAt(new \DateTime());
            $user->setUpdatedAt(new \DateTime());
            $user->setActive(true);
            $user->setNotes('This is a default user.');

            $manager->persist($user);
        }
        $manager->flush();
    }

    public function createModuleFixtures(ObjectManager $manager): void
    {
        $modules = [
            // Add your module data here
            ['name' => 'Benutzer', 'text' => 'Benutzerverwaltung'],
            ['name' => 'Unternehmen', 'text' => 'Kunden, Lieferanten, Partner etc.'],
        ];

        // Create and persist module entities here
        // Example:
        foreach ($modules as $moduleData) {
            $module = new Module();
            $module->setName($moduleData['name']);
            $module->setText($moduleData['text']);
            $module->setCreatedAt(new \DateTime());
            $module->setUpdatedAt(new \DateTime());
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

        if ($adminUser && $userModule) {
            // Admin has full access to user module
            $permission = new UserModulePermission();
            $permission->setUser($adminUser);
            $permission->setModule($userModule);
            $permission->setCanRead(true);
            $permission->setCanWrite(true);
            $permission->setCreatedAt(new \DateTime());
            $permission->setUpdatedAt(new \DateTime());
            $manager->persist($permission);
        }

        if ($adminUser && $companyModule) {
            // Admin has full access to company module
            $permission = new UserModulePermission();
            $permission->setUser($adminUser);
            $permission->setModule($companyModule);
            $permission->setCanRead(true);
            $permission->setCanWrite(true);
            $permission->setCreatedAt(new \DateTime());
            $permission->setUpdatedAt(new \DateTime());
            $manager->persist($permission);
        }

        if ($demoUser && $userModule) {
            // Demo user has read-only access to user module
            $permission = new UserModulePermission();
            $permission->setUser($demoUser);
            $permission->setModule($userModule);
            $permission->setCanRead(true);
            $permission->setCanWrite(false);
            $permission->setCreatedAt(new \DateTime());
            $permission->setUpdatedAt(new \DateTime());
            $manager->persist($permission);
        }

        if ($demoUser && $companyModule) {
            // Demo user has read and write access to company module
            $permission = new UserModulePermission();
            $permission->setUser($demoUser);
            $permission->setModule($companyModule);
            $permission->setCanRead(true);
            $permission->setCanWrite(true);
            $permission->setCreatedAt(new \DateTime());
            $permission->setUpdatedAt(new \DateTime());
            $manager->persist($permission);
        }

        $manager->flush();
    }
}
