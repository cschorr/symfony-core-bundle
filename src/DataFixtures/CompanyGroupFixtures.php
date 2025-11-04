<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\CompanyGroup;
use C3net\CoreBundle\Enum\DomainEntityType;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CompanyGroupFixtures extends AbstractCategorizableFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Themed groups from comics and motion pictures
        $groups = [
            'skynet' => [
                'name' => 'Skynet Group',
                'shortcode' => 'SKYNET',
                'categories' => ['Technology', 'AI & Machine Learning', 'Cybersecurity'],
            ],
            'marvel' => [
                'name' => 'Marvel Group',
                'shortcode' => 'MARVEL',
                'categories' => ['Technology', 'Software Solutions', 'DevOps & Infrastructure'],
            ],
            'dc' => [
                'name' => 'DC Group',
                'shortcode' => 'DC',
                'categories' => ['Business Services', 'Financial Services', 'Strategy Consulting'],
            ],
            'weyland' => [
                'name' => 'Weyland-Yutani Group',
                'shortcode' => 'WEYLAND',
                'categories' => ['Consulting', 'Legal Services', 'Business Services'],
            ],
            'umbrella' => [
                'name' => 'Umbrella Group',
                'shortcode' => 'UMBRELLA',
                'categories' => ['Marketing & Sales', 'Digital Marketing', 'Healthcare'],
            ],
        ];

        foreach ($groups as $data) {
            $categories = $this->findCategoriesByNames($manager, $data['categories']);

            $group = (new CompanyGroup())
                ->setName($data['name'])
                ->setShortcode($data['shortcode']);

            // Persist and flush to get ID
            $this->persistAndFlush($manager, $group);

            // Assign multiple categories
            $this->assignCategories($manager, $group, $categories, DomainEntityType::CompanyGroup);
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
