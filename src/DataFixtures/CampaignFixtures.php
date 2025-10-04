<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Campaign;
use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Entity\Project;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CampaignFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $campaignsData = [
            [
                'name' => 'Digital Transformation 2025',
                'description' => 'Comprehensive digital transformation initiative focusing on modernizing legacy systems and implementing cutting-edge technologies across multiple client organizations.',
                'category' => 'Technology',
                'projects' => ['E-Commerce Platform', 'AI Security System', 'Mobile Banking App', 'Scientific Data Analysis', 'Quantum Computing Research'],
            ],
            [
                'name' => 'Global Marketing Excellence',
                'description' => 'Multi-company marketing campaign focusing on brand management, digital marketing automation, and content creation strategies for international markets.',
                'category' => 'Marketing & Sales',
                'projects' => ['Digital Marketing Campaign', 'Global Distribution Network'],
            ],
            [
                'name' => 'Enterprise Security & Compliance',
                'description' => 'Strategic initiative to enhance security infrastructure and ensure regulatory compliance across all client operations.',
                'category' => 'Business Services',
                'projects' => ['Automated Defense Network', 'Corporate Security Upgrade', 'Arc Reactor Monitoring'],
            ],
        ];

        foreach ($campaignsData as $index => $campaignData) {
            $category = $manager->getRepository(Category::class)->findOneBy(['name' => $campaignData['category']]);

            $campaign = (new Campaign())
                ->setName($campaignData['name'])
                ->setDescription($campaignData['description'])
                ->setCategory($category)
            ;

            // Assign projects to campaign
            foreach ($campaignData['projects'] as $projectName) {
                $project = $manager->getRepository(Project::class)->findOneBy(['name' => $projectName]);
                if ($project) {
                    $campaign->addProject($project);
                }
            }

            $manager->persist($campaign);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
            ProjectFixtures::class,
        ];
    }
}
