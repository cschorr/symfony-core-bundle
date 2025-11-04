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
            'main5' => [
                'name' => 'Creative & Design',
                'color' => 'orange',
                'icon' => 'fas fa-palette',
            ],
            'main6' => [
                'name' => 'Media & Production',
                'color' => 'pink',
                'icon' => 'fas fa-video',
            ],
            'main7' => [
                'name' => 'Education & Training',
                'color' => 'teal',
                'icon' => 'fas fa-graduation-cap',
            ],
            'main8' => [
                'name' => 'Healthcare',
                'color' => 'cyan',
                'icon' => 'fas fa-heartbeat',
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
            // Technology subcategories
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
                'name' => 'DevOps & Infrastructure',
                'color' => 'lightblue',
                'icon' => 'fas fa-server',
                'parent' => 'Technology',
            ],
            'sub5' => [
                'name' => 'AI & Machine Learning',
                'color' => 'lightblue',
                'icon' => 'fas fa-brain',
                'parent' => 'Technology',
            ],
            'sub6' => [
                'name' => 'Cybersecurity',
                'color' => 'lightblue',
                'icon' => 'fas fa-shield-alt',
                'parent' => 'Technology',
            ],
            // Business Services subcategories
            'sub7' => [
                'name' => 'Financial Services',
                'color' => 'lightred',
                'icon' => 'fas fa-coins',
                'parent' => 'Business Services',
            ],
            'sub8' => [
                'name' => 'Legal Services',
                'color' => 'lightred',
                'icon' => 'fas fa-gavel',
                'parent' => 'Business Services',
            ],
            'sub9' => [
                'name' => 'Human Resources',
                'color' => 'lightred',
                'icon' => 'fas fa-users',
                'parent' => 'Business Services',
            ],
            'sub10' => [
                'name' => 'Accounting & Tax',
                'color' => 'lightred',
                'icon' => 'fas fa-calculator',
                'parent' => 'Business Services',
            ],
            // Marketing & Sales subcategories
            'sub11' => [
                'name' => 'Digital Marketing',
                'color' => 'lightgreen',
                'icon' => 'fas fa-chart-line',
                'parent' => 'Marketing & Sales',
            ],
            'sub12' => [
                'name' => 'Content Creation',
                'color' => 'lightgreen',
                'icon' => 'fas fa-pen-fancy',
                'parent' => 'Marketing & Sales',
            ],
            'sub13' => [
                'name' => 'Social Media',
                'color' => 'lightgreen',
                'icon' => 'fas fa-share-alt',
                'parent' => 'Marketing & Sales',
            ],
            'sub14' => [
                'name' => 'SEO & SEM',
                'color' => 'lightgreen',
                'icon' => 'fas fa-search',
                'parent' => 'Marketing & Sales',
            ],
            'sub15' => [
                'name' => 'Brand Strategy',
                'color' => 'lightgreen',
                'icon' => 'fas fa-copyright',
                'parent' => 'Marketing & Sales',
            ],
            // Consulting subcategories
            'sub16' => [
                'name' => 'Strategy Consulting',
                'color' => 'lightpurple',
                'icon' => 'fas fa-chess',
                'parent' => 'Consulting',
            ],
            'sub17' => [
                'name' => 'IT Consulting',
                'color' => 'lightpurple',
                'icon' => 'fas fa-laptop-medical',
                'parent' => 'Consulting',
            ],
            'sub18' => [
                'name' => 'Management Consulting',
                'color' => 'lightpurple',
                'icon' => 'fas fa-project-diagram',
                'parent' => 'Consulting',
            ],
            // Creative & Design subcategories
            'sub19' => [
                'name' => 'Graphic Design',
                'color' => 'lightorange',
                'icon' => 'fas fa-paint-brush',
                'parent' => 'Creative & Design',
            ],
            'sub20' => [
                'name' => 'UI/UX Design',
                'color' => 'lightorange',
                'icon' => 'fas fa-drafting-compass',
                'parent' => 'Creative & Design',
            ],
            'sub21' => [
                'name' => '3D Modeling',
                'color' => 'lightorange',
                'icon' => 'fas fa-cube',
                'parent' => 'Creative & Design',
            ],
            'sub22' => [
                'name' => 'Illustration',
                'color' => 'lightorange',
                'icon' => 'fas fa-pencil-ruler',
                'parent' => 'Creative & Design',
            ],
            // Media & Production subcategories
            'sub23' => [
                'name' => 'Video Production',
                'color' => 'lightpink',
                'icon' => 'fas fa-film',
                'parent' => 'Media & Production',
            ],
            'sub24' => [
                'name' => 'Audio Production',
                'color' => 'lightpink',
                'icon' => 'fas fa-microphone',
                'parent' => 'Media & Production',
            ],
            'sub25' => [
                'name' => 'Photography',
                'color' => 'lightpink',
                'icon' => 'fas fa-camera',
                'parent' => 'Media & Production',
            ],
            'sub26' => [
                'name' => 'Podcasting',
                'color' => 'lightpink',
                'icon' => 'fas fa-podcast',
                'parent' => 'Media & Production',
            ],
            'sub27' => [
                'name' => 'Animation',
                'color' => 'lightpink',
                'icon' => 'fas fa-magic',
                'parent' => 'Media & Production',
            ],
            // Education & Training subcategories
            'sub28' => [
                'name' => 'Corporate Training',
                'color' => 'lightteal',
                'icon' => 'fas fa-chalkboard-teacher',
                'parent' => 'Education & Training',
            ],
            'sub29' => [
                'name' => 'E-Learning',
                'color' => 'lightteal',
                'icon' => 'fas fa-laptop-code',
                'parent' => 'Education & Training',
            ],
            'sub30' => [
                'name' => 'Technical Training',
                'color' => 'lightteal',
                'icon' => 'fas fa-tools',
                'parent' => 'Education & Training',
            ],
            // Healthcare subcategories
            'sub31' => [
                'name' => 'Medical Technology',
                'color' => 'lightcyan',
                'icon' => 'fas fa-stethoscope',
                'parent' => 'Healthcare',
            ],
            'sub32' => [
                'name' => 'Telemedicine',
                'color' => 'lightcyan',
                'icon' => 'fas fa-phone-alt',
                'parent' => 'Healthcare',
            ],
            'sub33' => [
                'name' => 'Health IT',
                'color' => 'lightcyan',
                'icon' => 'fas fa-hospital',
                'parent' => 'Healthcare',
            ],
        ];

        foreach ($subCategoriesData as $data) {
            $parent = $manager->getRepository(Category::class)->findOneBy(['name' => $data['parent']]);

            if ($parent === null) {
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
