<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Repository\UserGroupRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    private const string DEFAULT_PASSWORD = 'pass_1234';

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        private readonly UserGroupRepository $userGroupRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $usersData = [
            'admin' => ['email' => 'admin@example.com', 'active' => true, 'notes' => 'Administrator user with full access', 'category' => 'main2', 'nameLast' => 'Admin', 'nameFirst' => 'System', 'userGroups' => ['Admin'], 'company' => null],
            'editor' => ['email' => 'editor@example.com', 'active' => true, 'notes' => 'Web developer working on e-commerce projects', 'category' => 'sub1', 'nameLast' => 'Wilson', 'nameFirst' => 'Sarah', 'userGroups' => ['Editor'], 'company' => 'company_0'],
            'teamlead' => ['email' => 'teamlead@example.com', 'active' => true, 'notes' => 'Senior developer specializing in mobile apps', 'category' => 'sub2', 'nameLast' => 'Johnson', 'nameFirst' => 'Michael', 'userGroups' => ['Teamlead'], 'roles' => ['ROLE_QUALITY'], 'company' => 'company_1'],
            'manager' => ['email' => 'marketing@example.com', 'active' => true, 'notes' => 'Marketing specialist for digital campaigns', 'category' => 'sub6', 'nameLast' => 'Davis', 'nameFirst' => 'Emma', 'userGroups' => ['Manager'], 'company' => 'company_5'],
            'external' => ['email' => 'external@example.com', 'active' => true, 'notes' => 'Business consultant for process optimization', 'category' => 'main4', 'nameLast' => 'Thompson', 'nameFirst' => 'Robert', 'userGroups' => ['External Users'], 'company' => 'company_2'],
            'demo' => ['email' => 'demo@example.com', 'active' => true, 'notes' => 'Full-stack developer with React and PHP expertise', 'category' => 'sub1', 'nameLast' => 'Anderson', 'nameFirst' => 'Alex', 'userGroups' => ['Editor'], 'company' => 'company_3'],
            'dev1' => ['email' => 'dev1@example.com', 'active' => true, 'notes' => 'Frontend specialist focusing on React and Vue.js', 'category' => 'sub1', 'nameLast' => 'Brown', 'nameFirst' => 'Jessica', 'userGroups' => ['Editor'], 'company' => 'company_7'],
            'dev2' => ['email' => 'dev2@example.com', 'active' => true, 'notes' => 'Mobile app developer with iOS and Android experience', 'category' => 'sub2', 'nameLast' => 'Garcia', 'nameFirst' => 'Carlos', 'userGroups' => ['Teamlead'], 'company' => 'company_8'],
            'consultant1' => ['email' => 'consultant1@example.com', 'active' => true, 'notes' => 'Business process optimization specialist', 'category' => 'main4', 'nameLast' => 'Miller', 'nameFirst' => 'Amanda', 'userGroups' => ['Manager'], 'company' => 'company_10'],
            'marketing1' => ['email' => 'marketing1@example.com', 'active' => true, 'notes' => 'Digital marketing strategist and content creator', 'category' => 'sub6', 'nameLast' => 'Williams', 'nameFirst' => 'David', 'userGroups' => ['Manager'], 'company' => 'company_15'],
        ];

        foreach ($usersData as $key => $userData) {
            $category = $this->getReference($userData['category'], Category::class);
            $userGroups = $this->userGroupRepository->findBy(['name' => $userData['userGroups']]);
            $company = null;

            // Get company if specified
            if (isset($userData['company']) && $userData['company']) {
                $company = $this->getReference($userData['company'], Company::class);
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
            $this->addReference('user_' . $key, $user);
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
