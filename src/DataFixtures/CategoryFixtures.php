<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categoriesData = [
            'main1' => [
                'name' => 'Technology',
                'color' => 'blue',
                'icon' => 'fas fa-laptop-code',
            ],
            'main2' => [
                'name' => 'Business Services',
                'color' => 'red',
                'icon' => 'fas fa-briefcase',
            ],
            'main3' => [
                'name' => 'Marketing & Sales',
                'color' => 'green',
                'icon' => 'fas fa-bullhorn',
            ],
            'main4' => [
                'name' => 'Consulting',
                'color' => 'purple',
                'icon' => 'fas fa-user-tie',
            ],
        ];

        // Create main categories first
        foreach ($categoriesData as $key => $data) {
            $category = (new Category())
                ->setName($data['name'])
                ->setColor($data['color'])
                ->setIcon($data['icon']);

            $manager->persist($category);
        }

        $manager->flush();

        // Then create subcategories
        $subCategoriesData = [
            'sub1' => [
                'name' => 'Web Development',
                'color' => 'lightblue',
                'icon' => 'fas fa-globe',
                'parent' => 'Technology',
            ],
            'sub2' => [
                'name' => 'Mobile Development',
                'color' => 'lightblue',
                'icon' => 'fas fa-mobile-alt',
                'parent' => 'Technology',
            ],
            'sub3' => [
                'name' => 'Software Solutions',
                'color' => 'lightblue',
                'icon' => 'fas fa-code',
                'parent' => 'Technology',
            ],
            'sub4' => [
                'name' => 'Financial Services',
                'color' => 'lightred',
                'icon' => 'fas fa-coins',
                'parent' => 'Business Services',
            ],
            'sub5' => [
                'name' => 'Legal Services',
                'color' => 'lightred',
                'icon' => 'fas fa-gavel',
                'parent' => 'Business Services',
            ],
            'sub6' => [
                'name' => 'Digital Marketing',
                'color' => 'lightgreen',
                'icon' => 'fas fa-chart-line',
                'parent' => 'Marketing & Sales',
            ],
            'sub7' => [
                'name' => 'Content Creation',
                'color' => 'lightgreen',
                'icon' => 'fas fa-pen-fancy',
                'parent' => 'Marketing & Sales',
            ],
        ];

        foreach ($subCategoriesData as $key => $data) {
            $parent = $manager->getRepository(Category::class)->findOneBy(['name' => $data['parent']]);

            if (!$parent) {
                throw new \RuntimeException(sprintf('Parent category "%s" not found for subcategory "%s"', $data['parent'], $data['name']));
            }

            $category = (new Category())
                ->setName($data['name'])
                ->setColor($data['color'])
                ->setIcon($data['icon']);

            $category->setParent($parent);

            $manager->persist($category);
        }

        $manager->flush();
    }
}
