<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Campaign;
use C3net\CoreBundle\Entity\Project;
use C3net\CoreBundle\Entity\Transaction;
use C3net\CoreBundle\Enum\DomainEntityType;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class CampaignFixtures extends AbstractCategorizableFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $campaignsData = [
            [
                'name' => 'Digital Transformation 2025',
                'description' => 'Comprehensive digital transformation initiative focusing on modernizing legacy systems and implementing cutting-edge technologies across multiple client organizations.',
                'categories' => ['Technology', 'Software Solutions', 'AI & Machine Learning'],
                'projects' => ['E-Commerce Platform', 'AI Security System', 'Mobile Banking App', 'Scientific Data Analysis', 'Quantum Computing Research'],
                'transaction' => 'TXN-2025-0001', // E-Commerce Platform Development
            ],
            [
                'name' => 'Global Marketing Excellence',
                'description' => 'Multi-company marketing campaign focusing on brand management, digital marketing automation, and content creation strategies for international markets.',
                'categories' => ['Marketing & Sales', 'Digital Marketing', 'Content Creation'],
                'projects' => ['Digital Marketing Campaign', 'Global Distribution Network'],
                'transaction' => 'TXN-2025-0005', // Digital Marketing Campaign
            ],
            [
                'name' => 'Enterprise Security & Compliance',
                'description' => 'Strategic initiative to enhance security infrastructure and ensure regulatory compliance across all client operations.',
                'categories' => ['Business Services', 'Cybersecurity', 'Technology'],
                'projects' => ['Automated Defense Network', 'Corporate Security Upgrade', 'Arc Reactor Monitoring'],
                'transaction' => 'TXN-2025-0003', // Corporate Security Upgrade
            ],
        ];

        foreach ($campaignsData as $campaignData) {
            $categories = $this->findCategoriesByNames($manager, $campaignData['categories']);

            $campaign = (new Campaign())
                ->setName($campaignData['name'])
                ->setDescription($campaignData['description'])
            ;

            // Assign transaction to campaign if specified
            // @phpstan-ignore-next-line isset.offset, booleanAnd.alwaysTrue, notIdentical.alwaysTrue (Defensive check for fixture data integrity)
            if (isset($campaignData['transaction']) && null !== $campaignData['transaction']) {
                $transaction = $manager->getRepository(Transaction::class)
                    ->findOneBy(['transactionNumber' => $campaignData['transaction']]);
                if ($transaction !== null) {
                    $campaign->setTransaction($transaction);
                } else {
                    // Transaction not found - this should not happen if fixtures are loaded correctly
                    error_log(sprintf('Warning: Transaction %s not found for campaign %s', $campaignData['transaction'], $campaignData['name']));
                }
            }

            // Assign projects to campaign
            foreach ($campaignData['projects'] as $projectName) {
                $project = $manager->getRepository(Project::class)->findOneBy(['name' => $projectName]);
                if ($project !== null) {
                    $campaign->addProject($project);
                }
            }

            // Persist and flush to get ID
            $this->persistAndFlush($manager, $campaign);

            // Assign multiple categories
            $this->assignCategories($manager, $campaign, $categories, DomainEntityType::Campaign);
        }

        $this->flushSafely($manager);
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
            ProjectFixtures::class,
            TransactionFixtures::class,
        ];
    }
}
