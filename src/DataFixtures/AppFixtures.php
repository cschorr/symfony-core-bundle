<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

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
        $user = new User();
        $user->setEmail('admin@example.com');
        $user->setPassword($this->hasher->hashPassword($user, 'pass_1234'));
        $user->setRoles(['ROLE_ADMIN']);

        //TODO: discuss $now = new \DateTimeImmutable();
        $now = new \DateTime();
        $user->setCreatedAt($now);
        $user->setUpdatedAt($now);
        $user->setActive(true);
        $user->setNotes('This is the default admin user.');

        $manager->persist($user);
        $manager->flush();
    }
}
