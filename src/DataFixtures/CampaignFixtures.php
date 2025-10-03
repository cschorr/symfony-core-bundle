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
                'category' => 'main1',
                'projects' => ['project_0', 'project_1', 'project_3', 'project_9', 'project_15'],
            ],
            [
                'name' => 'Global Marketing Excellence',
                'description' => 'Multi-company marketing campaign focusing on brand management, digital marketing automation, and content creation strategies for international markets.',
                'category' => 'main3',
                'projects' => ['project_11', 'project_13'],
            ],
            [
                'name' => 'Enterprise Security & Compliance',
                'description' => 'Strategic initiative to enhance security infrastructure and ensure regulatory compliance across all client operations.',
                'category' => 'main2',
                'projects' => ['project_2', 'project_6', 'project_5'],
            ],
        ];

        foreach ($campaignsData as $index => $campaignData) {
            $category = $this->getReference($campaignData['category'], Category::class);

            $campaign = (new Campaign())
                ->setName($campaignData['name'])
                ->setDescription($campaignData['description'])
                ->setCategory($category)
            ;

            // Assign projects to campaign
            foreach ($campaignData['projects'] as $projectKey) {
                if ($this->hasReference($projectKey)) {
                    $project = $this->getReference($projectKey, Project::class);
                    $campaign->addProject($project);
                }
            }

            $manager->persist($campaign);
            $this->addReference('campaign_' . $index, $campaign);
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
