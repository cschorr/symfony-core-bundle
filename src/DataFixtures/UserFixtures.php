<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Entity\UserGroup;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    private const string DEFAULT_PASSWORD = 'pass_1234';

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $usersData = [
            'admin' => ['email' => 'admin@example.com', 'active' => true, 'notes' => 'Administrator user with full access', 'category' => 'Business Services', 'nameLast' => 'Admin', 'nameFirst' => 'System', 'userGroups' => ['Admin'], 'company' => null],
            'editor' => ['email' => 'editor@example.com', 'active' => true, 'notes' => 'Web developer working on e-commerce projects', 'category' => 'Web Development', 'nameLast' => 'Wilson', 'nameFirst' => 'Sarah', 'userGroups' => ['Editor'], 'company' => 'Cyberdyne Systems'],
            'teamlead' => ['email' => 'teamlead@example.com', 'active' => true, 'notes' => 'Senior developer specializing in mobile apps', 'category' => 'Mobile Development', 'nameLast' => 'Johnson', 'nameFirst' => 'Michael', 'userGroups' => ['Teamlead'], 'roles' => ['ROLE_QUALITY'], 'company' => 'Stark Industries'],
            'manager' => ['email' => 'marketing@example.com', 'active' => true, 'notes' => 'Marketing specialist for digital campaigns', 'category' => 'Digital Marketing', 'nameLast' => 'Davis', 'nameFirst' => 'Emma', 'userGroups' => ['Manager'], 'company' => 'Umbrella Corporation'],
            'external' => ['email' => 'external@example.com', 'active' => true, 'notes' => 'Business consultant for process optimization', 'category' => 'Consulting', 'nameLast' => 'Thompson', 'nameFirst' => 'Robert', 'userGroups' => ['External Users'], 'company' => 'Wayne Enterprises'],
            'demo' => ['email' => 'demo@example.com', 'active' => true, 'notes' => 'Full-stack developer with React and PHP expertise', 'category' => 'Web Development', 'nameLast' => 'Anderson', 'nameFirst' => 'Alex', 'userGroups' => ['Editor'], 'company' => 'Oscorp'],
            'dev1' => ['email' => 'dev1@example.com', 'active' => true, 'notes' => 'Frontend specialist focusing on React and Vue.js', 'category' => 'Web Development', 'nameLast' => 'Brown', 'nameFirst' => 'Jessica', 'userGroups' => ['Editor'], 'company' => 'NeuralLink Systems'],
            'dev2' => ['email' => 'dev2@example.com', 'active' => true, 'notes' => 'Mobile app developer with iOS and Android experience', 'category' => 'Mobile Development', 'nameLast' => 'Garcia', 'nameFirst' => 'Carlos', 'userGroups' => ['Teamlead'], 'company' => 'Parker Industries'],
            'consultant1' => ['email' => 'consultant1@example.com', 'active' => true, 'notes' => 'Business process optimization specialist', 'category' => 'Consulting', 'nameLast' => 'Miller', 'nameFirst' => 'Amanda', 'userGroups' => ['Manager'], 'company' => 'Rand Corporation'],
            'marketing1' => ['email' => 'marketing1@example.com', 'active' => true, 'notes' => 'Digital marketing strategist and content creator', 'category' => 'Digital Marketing', 'nameLast' => 'Williams', 'nameFirst' => 'David', 'userGroups' => ['Manager'], 'company' => 'Seegson Corporation'],
        ];

        foreach ($usersData as $key => $userData) {
            $category = $manager->getRepository(Category::class)->findOneBy(['name' => $userData['category']]);
            $userGroups = $manager->getRepository(UserGroup::class)->findBy(['name' => $userData['userGroups']]);
            $company = null;

            // Get company if specified
            if (isset($userData['company']) && $userData['company']) {
                $company = $manager->getRepository(Company::class)->findOneBy(['name' => $userData['company']]);
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
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
            CompanyFixtures::class,
            UserGroupFixtures::class,
        ];
    }
}
