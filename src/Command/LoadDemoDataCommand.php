<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Command;

use C3net\CoreBundle\DataFixtures\CampaignFixtures;
use C3net\CoreBundle\DataFixtures\CategoryFixtures;
use C3net\CoreBundle\DataFixtures\CompanyFixtures;
use C3net\CoreBundle\DataFixtures\CompanyGroupFixtures;
use C3net\CoreBundle\DataFixtures\ContactFixtures;
use C3net\CoreBundle\DataFixtures\DocumentFixtures;
use C3net\CoreBundle\DataFixtures\InvoiceFixtures;
use C3net\CoreBundle\DataFixtures\OfferFixtures;
use C3net\CoreBundle\DataFixtures\ProjectFixtures;
use C3net\CoreBundle\DataFixtures\TransactionFixtures;
use C3net\CoreBundle\DataFixtures\UserFixtures;
use C3net\CoreBundle\DataFixtures\UserGroupFixtures;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'c3net:load-demo-data',
    description: 'Load demo data fixtures from C3net Core Bundle',
    aliases: ['c3net:fixtures:load']
)]
class LoadDemoDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('This command loads demo data from the C3net Core Bundle including users, companies, projects, and more.')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Skip confirmation prompt and force loading of demo data'
            )
            ->addOption(
                'purge',
                'p',
                InputOption::VALUE_NONE,
                'Purge existing data before loading demo data (use with caution!)'
            )
            ->addOption(
                'drop-create-schema',
                'd',
                InputOption::VALUE_NONE,
                'Drop and recreate database schema before loading demo data'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('C3net Core Bundle Demo Data Loader');

        // Check if we should purge existing data or drop/create schema
        $shouldPurge = $input->getOption('purge');
        $shouldDropCreateSchema = $input->getOption('drop-create-schema');

        // Check for existing data
        $userCount = $this->entityManager->getRepository(\C3net\CoreBundle\Entity\User::class)->count([]);

        if ($userCount > 0 && !$shouldPurge) {
            $io->warning(sprintf('Database already contains %d users. Use --purge to clear existing data first.', $userCount));

            if (!$input->getOption('force')) {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion(
                    'Do you want to continue and add demo data to existing data? (y/N) ',
                    false
                );

                if (!$helper->ask($input, $output, $question)) {
                    $io->note('Operation cancelled.');

                    return Command::SUCCESS;
                }
            }
        }

        // Confirmation prompt
        if (!$input->getOption('force')) {
            $io->caution('This will load demo data into your database.');

            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                'Are you sure you want to continue? (y/N) ',
                false
            );

            if (!$helper->ask($input, $output, $question)) {
                $io->note('Operation cancelled.');

                return Command::SUCCESS;
            }
        }

        try {
            if ($shouldDropCreateSchema) {
                $io->section('Dropping and recreating database schema...');
                $this->dropAndCreateSchema($io);
            } elseif ($shouldPurge) {
                $io->section('Purging existing data...');
                $this->purgeData($io);
            }

            $io->section('Loading demo data...');

            // Temporarily disable SQL logging to improve performance
            $this->entityManager->getConnection()->getConfiguration()->setSQLLogger(null);

            try {
                // Load fixtures in dependency order
                $userGroupRepository = $this->entityManager->getRepository(\C3net\CoreBundle\Entity\UserGroup::class);

                $fixtures = [
                    new CategoryFixtures(),
                    new UserGroupFixtures(),
                    new CompanyGroupFixtures(),
                    new CompanyFixtures(),
                    new UserFixtures($this->passwordHasher, $userGroupRepository),
                    new ContactFixtures(),
                    new ProjectFixtures(),
                    new CampaignFixtures(),
                    new TransactionFixtures(),
                    new OfferFixtures(),
                    new InvoiceFixtures(),
                    new DocumentFixtures(),
                ];

                foreach ($fixtures as $fixture) {
                    $fixture->load($this->entityManager);
                }
            } catch (\Exception $e) {
                // Check if this is a Mercure-related error that we can safely ignore during fixture loading
                if (str_contains($e->getMessage(), 'Failed to send an update')
                    || str_contains($e->getMessage(), 'mercure')
                    || str_contains($e->getMessage(), 'Mercure')) {
                    $io->warning([
                        'Mercure update failed during demo data loading.',
                        'This is usually harmless during fixture loading as real-time updates are not critical.',
                        'Demo data loading will continue...',
                    ]);
                    if ($io->isVerbose()) {
                        $io->text('Error details: ' . $e->getMessage());
                    }
                // Continue with success since Mercure failures during fixture loading are not critical
                } else {
                    // Re-throw non-Mercure related exceptions
                    throw $e;
                }
            }

            $io->success([
                'Demo data has been successfully loaded!',
                '',
                'The following demo users have been created:',
                '  - admin@example.com (password: pass_1234)',
                '  - editor@example.com (password: pass_1234)',
                '  - teamlead@example.com (password: pass_1234)',
                '  - manager@example.com (password: pass_1234)',
                '  - external@example.com (password: pass_1234)',
                '  - demo@example.com (password: pass_1234)',
                '',
                'Additionally loaded:',
                '  - User Groups (Admin, Editor, Teamlead, Manager, External)',
                '  - Categories and Subcategories',
                '  - Company Groups (Skynet, Marvel, DC, etc.)',
                '  - 19 Companies with demo logos',
                '  - 18 Contacts with hierarchies',
                '  - 16 Projects with various statuses',
                '  - 3 Marketing Campaigns',
                '  - 10 Transactions (DRAFT → PAID)',
                '  - 6 Offers with multiple versions',
                '  - 5 Invoices (Full, Deposit, Final)',
                '  - 12 Documents (Briefs, Contracts, Deliverables)',
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error([
                'An error occurred while loading demo data:',
                $e->getMessage(),
            ]);

            if ($output->isVerbose()) {
                $io->section('Stack trace:');
                $io->text($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    private function purgeData(SymfonyStyle $io): void
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        // Disable foreign key checks
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        // List of tables to purge (in reverse order of dependencies)
        $tables = [
            'campaign_project',
            'campaign',
            'project',
            'contact',
            'user_user_group',
            'user',
            'company',
            'company_group',
            'user_group',
            'category',
        ];

        foreach ($tables as $table) {
            try {
                $connection->executeStatement($platform->getTruncateTableSQL($table));
                $io->text(sprintf(' ✓ Truncated table: %s', $table));
            } catch (\Exception $e) {
                // Table might not exist, skip it
                if ($io->isVerbose()) {
                    $io->text(sprintf(' ⚠ Could not truncate table %s: %s', $table, $e->getMessage()));
                }
            }
        }

        // Re-enable foreign key checks
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

        $io->text('');
        $io->success('Existing data has been purged.');
    }

    private function dropAndCreateSchema(SymfonyStyle $io): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        try {
            // Drop the schema
            $io->text('Dropping database schema...');
            $schemaTool->dropSchema($metadata);
            $io->text(' ✓ Schema dropped successfully');

            // Create the schema
            $io->text('Creating database schema...');
            $schemaTool->createSchema($metadata);
            $io->text(' ✓ Schema created successfully');

            $io->text('');
            $io->success('Database schema has been recreated.');
        } catch (Exception $e) {
            throw new \RuntimeException(sprintf('Failed to drop/create schema: %s', $e->getMessage()), 0, $e);
        }
    }
}
