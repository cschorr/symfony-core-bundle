<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

use App\Entity\Module;
use App\Entity\User;
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
}
