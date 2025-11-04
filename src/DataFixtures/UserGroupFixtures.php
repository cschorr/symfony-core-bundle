<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\UserGroup;
use C3net\CoreBundle\Enum\DomainEntityType;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class UserGroupFixtures extends AbstractCategorizableFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $userGroupsData = [
            'external' => [
                'name' => 'External Users',
                'roles' => ['ROLE_EXTERNAL'],
                'active' => true,
                'categories' => ['Consulting', 'Business Services'],
            ],
            'basic' => [
                'name' => 'Editor',
                'roles' => ['ROLE_EDITOR'],
                'active' => true,
                'categories' => ['Web Development', 'Content Creation', 'UI/UX Design'],
            ],
            'advanced' => [
                'name' => 'Teamlead',
                'roles' => ['ROLE_TEAMLEAD', 'ROLE_FINANCE', 'ROLE_QUALITY', 'ROLE_PROJECT_MANAGEMENT'],
                'active' => true,
                'categories' => ['Software Solutions', 'DevOps & Infrastructure', 'Management Consulting'],
            ],
            'manager' => [
                'name' => 'Manager',
                'roles' => ['ROLE_MANAGER'],
                'active' => true,
                'categories' => ['Marketing & Sales', 'Strategy Consulting', 'Business Services'],
            ],
            'admin' => [
                'name' => 'Admin',
                'roles' => ['ROLE_ADMIN'],
                'active' => true,
                'categories' => ['Business Services', 'IT Consulting', 'Management Consulting'],
            ],
        ];

        foreach ($userGroupsData as $userData) {
            $categories = $this->findCategoriesByNames($manager, $userData['categories']);

            $userGroup = new UserGroup();
            $userGroup
                ->setName($userData['name'])
                ->setRoles($userData['roles'])
                ->setActive($userData['active'])
            ;

            // Persist and flush to get ID
            $this->persistAndFlush($manager, $userGroup);

            // Assign multiple categories
            $this->assignCategories($manager, $userGroup, $categories, DomainEntityType::UserGroup);
        }

        $this->flushSafely($manager);
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
        ];
    }
}
