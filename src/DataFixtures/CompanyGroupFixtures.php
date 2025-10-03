<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\CompanyGroup;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CompanyGroupFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Themed groups from comics and motion pictures
        $groups = [
            'skynet' => ['name' => 'Skynet Group', 'code' => 'SKYNET'],
            'marvel' => ['name' => 'Marvel Group', 'code' => 'MARVEL'],
            'dc' => ['name' => 'DC Group', 'code' => 'DC'],
            'weyland' => ['name' => 'Weyland-Yutani Group', 'code' => 'WEYLAND'],
            'umbrella' => ['name' => 'Umbrella Group', 'code' => 'UMBRELLA'],
        ];

        foreach ($groups as $key => $data) {
            $group = (new CompanyGroup())
                ->setName($data['name'])
                ->setCode($data['code']);
            $manager->persist($group);
            $this->addReference('company_group_' . $key, $group);
        }

        $manager->flush();
    }
}
