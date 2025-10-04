<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\Department;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DepartmentFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $departmentsData = [
            // Cyberdyne Systems Departments
            [
                'name' => 'Research & Development',
                'code' => 'RND',
                'notes' => 'Advanced AI and robotics research',
                'company' => 'Cyberdyne Systems',
            ],
            [
                'name' => 'Marketing',
                'code' => 'MKT',
                'notes' => 'Product marketing and brand management',
                'company' => 'Cyberdyne Systems',
            ],
            [
                'name' => 'Engineering',
                'code' => 'ENG',
                'notes' => 'Systems engineering and product development',
                'company' => 'Cyberdyne Systems',
            ],

            // Stark Industries Departments
            [
                'name' => 'Advanced Technology',
                'code' => 'ADVTECH',
                'notes' => 'Next-generation technology development',
                'company' => 'Stark Industries',
            ],
            [
                'name' => 'Public Relations',
                'code' => 'PR',
                'notes' => 'Media relations and corporate communications',
                'company' => 'Stark Industries',
            ],

            // Wayne Enterprises Departments
            [
                'name' => 'Applied Sciences',
                'code' => 'SCI',
                'notes' => 'Scientific research and development',
                'company' => 'Wayne Enterprises',
            ],
            [
                'name' => 'Corporate',
                'code' => 'CORP',
                'notes' => 'Corporate management and strategy',
                'company' => 'Wayne Enterprises',
            ],
        ];

        foreach ($departmentsData as $data) {
            $company = $manager->getRepository(Company::class)
                ->findOneBy(['name' => $data['company']]);

            if (!$company) {
                continue;
            }

            $department = new Department();
            $department->setName($data['name']);
            $department->setCode($data['code']);
            $department->setNotes($data['notes']);
            $department->setCompany($company);

            $manager->persist($department);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CompanyFixtures::class,
        ];
    }
}
