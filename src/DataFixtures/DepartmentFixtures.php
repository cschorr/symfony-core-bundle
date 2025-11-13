<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\CompanyGroup;
use C3net\CoreBundle\Entity\Department;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DepartmentFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $departmentsData = [
            // Cyberdyne Systems Departments (Skynet Group)
            [
                'name' => 'Research & Development',
                'shortcode' => 'RND',
                'description' => 'Advanced AI and robotics research division focused on cutting-edge technology development',
                'email' => 'rnd@cyberdyne.example',
                'phone' => '+1-555-0101',
                'company' => 'Cyberdyne Systems',
                'companyGroup' => 'Skynet Group',
            ],
            [
                'name' => 'Marketing',
                'shortcode' => 'MKT',
                'description' => 'Product marketing and brand management team responsible for market strategy',
                'email' => 'marketing@cyberdyne.example',
                'phone' => '+1-555-0102',
                'company' => 'Cyberdyne Systems',
                'companyGroup' => 'Skynet Group',
            ],
            [
                'name' => 'Engineering',
                'shortcode' => 'ENG',
                'description' => 'Systems engineering and product development department',
                'email' => 'engineering@cyberdyne.example',
                'phone' => '+1-555-0103',
                'company' => 'Cyberdyne Systems',
                'companyGroup' => 'Skynet Group',
            ],

            // Stark Industries Departments (Marvel Group)
            [
                'name' => 'Advanced Technology',
                'shortcode' => 'ADVTECH',
                'description' => 'Next-generation technology development and innovation lab',
                'email' => 'advtech@stark.example',
                'phone' => '+1-555-0201',
                'cell' => '+1-555-0202',
                'company' => 'Stark Industries',
                'companyGroup' => 'Marvel Group',
            ],
            [
                'name' => 'Public Relations',
                'shortcode' => 'PR',
                'description' => 'Media relations and corporate communications department',
                'email' => 'pr@stark.example',
                'phone' => '+1-555-0203',
                'url' => 'https://stark.example/pr',
                'company' => 'Stark Industries',
                'companyGroup' => 'Marvel Group',
            ],

            // Wayne Enterprises Departments (DC Group)
            [
                'name' => 'Applied Sciences',
                'shortcode' => 'SCI',
                'description' => 'Scientific research and development division specializing in experimental technology',
                'email' => 'sciences@wayne.example',
                'phone' => '+1-555-0301',
                'company' => 'Wayne Enterprises',
                'companyGroup' => 'DC Group',
            ],
            [
                'name' => 'Corporate',
                'shortcode' => 'CORP',
                'description' => 'Corporate management and strategic planning department',
                'email' => 'corporate@wayne.example',
                'phone' => '+1-555-0302',
                'company' => 'Wayne Enterprises',
                'companyGroup' => 'DC Group',
            ],
        ];

        foreach ($departmentsData as $data) {
            $company = $manager->getRepository(Company::class)
                ->findOneBy(['name' => $data['company']]);

            if (null === $company) {
                continue;
            }

            // Find company group if specified
            $companyGroup = null;
            if (isset($data['companyGroup'])) {
                $companyGroup = $manager->getRepository(CompanyGroup::class)
                    ->findOneBy(['name' => $data['companyGroup']]);
            }

            $department = new Department();
            $department->setName($data['name']);
            $department->setShortcode($data['shortcode']);
            $department->setDescription($data['description']);
            $department->setCompany($company);

            // Set communication fields
            if (isset($data['email'])) {
                $department->setEmail($data['email']);
            }
            if (isset($data['phone'])) {
                $department->setPhone($data['phone']);
            }
            if (isset($data['cell'])) {
                $department->setCell($data['cell']);
            }
            if (isset($data['url'])) {
                $department->setUrl($data['url']);
            }

            // Set company group if found
            if (null !== $companyGroup) {
                $department->setCompanyGroup($companyGroup);
            }

            $manager->persist($department);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CompanyFixtures::class,
            CompanyGroupFixtures::class,
        ];
    }
}
