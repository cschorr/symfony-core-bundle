<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Command;

use C3net\CoreBundle\DataFixtures\CampaignFixtures;
use C3net\CoreBundle\DataFixtures\CategoryFixtures;
use C3net\CoreBundle\DataFixtures\CompanyFixtures;
use C3net\CoreBundle\DataFixtures\CompanyGroupFixtures;
use C3net\CoreBundle\DataFixtures\ContactFixtures;
use C3net\CoreBundle\DataFixtures\DepartmentFixtures;
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
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'c3net:load-demo-data', description: 'Load demo data fixtures from C3net Core Bundle', aliases: ['c3net:fixtures:load'], help: <<<'TXT'
This command loads demo data from the C3net Core Bundle including users, companies, projects, and more.
TXT)]
class LoadDemoDataCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager, private readonly ManagerRegistry $managerRegistry, private readonly UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Skip confirmation prompt and force loading of demo data')
            ->addOption('purge', 'p', InputOption::VALUE_NONE, 'Purge existing data before loading demo data (use with caution!)')
            ->addOption('drop-create-schema', 'd', InputOption::VALUE_NONE, 'Drop and recreate database schema before loading demo data');
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
                /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
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

            /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
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

            // Load fixtures in dependency order
            $fixtures = [
                new CategoryFixtures(),
                new UserGroupFixtures(),
                new CompanyGroupFixtures(),
                new CompanyFixtures(),
                new DepartmentFixtures(),
                new UserFixtures($this->passwordHasher),
                new ContactFixtures(),
                new ProjectFixtures(),
                new TransactionFixtures(),
                new CampaignFixtures(),
                new OfferFixtures(),
                new InvoiceFixtures(),
                new DocumentFixtures(),
            ];

            $hasMercureWarning = false;
            $entityManagerResetCount = 0;

            foreach ($fixtures as $fixture) {
                $fixtureClass = $fixture::class;

                if ($io->isVerbose()) {
                    $io->text(sprintf('Loading fixture: %s', basename(str_replace('\\', '/', $fixtureClass))));
                }

                try {
                    $fixture->load($this->entityManager);
                } catch (\Exception $e) {
                    $errorMessage = $e->getMessage();
                    $isMercureError = str_contains($errorMessage, 'Failed to send an update')
                        || str_contains($errorMessage, 'mercure')
                        || str_contains($errorMessage, 'Mercure');
                    $isEntityManagerClosed = str_contains($errorMessage, 'The EntityManager is closed');

                    // Check if this is a Mercure-related error or closed EntityManager
                    if ($isMercureError || $isEntityManagerClosed) {
                        // Log specific error type
                        if (!$hasMercureWarning) {
                            $warningMessages = ['Demo data loading encountered recoverable errors:'];

                            if ($isMercureError) {
                                $warningMessages[] = '• Mercure real-time updates failed (non-critical during fixture loading)';
                            }

                            if ($isEntityManagerClosed) {
                                $warningMessages[] = '• EntityManager was closed (will be reset automatically)';
                            }

                            $warningMessages[] = 'Continuing with data loading...';
                            $io->warning($warningMessages);

                            $hasMercureWarning = true;
                        }

                        // If EntityManager is closed, reset it and log the occurrence
                        if (!$this->entityManager->isOpen()) {
                            ++$entityManagerResetCount;

                            if ($io->isVerbose()) {
                                $io->text(sprintf(
                                    '[DEBUG] Resetting EntityManager (occurrence #%d) after fixture: %s',
                                    $entityManagerResetCount,
                                    basename(str_replace('\\', '/', $fixtureClass))
                                ));
                            }

                            $this->managerRegistry->resetManager();
                            $manager = $this->managerRegistry->getManager();
                            if (!$manager instanceof EntityManagerInterface) {
                                throw new \RuntimeException('Manager must be an instance of EntityManagerInterface');
                            }

                            $this->entityManager = $manager;

                            if ($io->isVerbose()) {
                                $io->text('[DEBUG] EntityManager reset successful, continuing fixture loading');
                            }
                        }

                        // Log detailed error in verbose mode
                        if ($io->isVerbose()) {
                            $io->text(sprintf('[DEBUG] Error details: %s', $errorMessage));
                            $io->text(sprintf('[DEBUG] Error occurred in: %s', $fixtureClass));
                        }

                    // Continue loading next fixture since Mercure failures during fixture loading are not critical
                    } else {
                        // Re-throw non-Mercure/non-EntityManager related exceptions
                        $io->error(sprintf('Critical error in fixture %s', $fixtureClass));
                        throw $e;
                    }
                }
            }

            // Log summary of EntityManager resets if any occurred
            if ($entityManagerResetCount > 0 && $io->isVerbose()) {
                $io->note(sprintf(
                    'EntityManager was reset %d time(s) during fixture loading',
                    $entityManagerResetCount
                ));
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
        } catch (\Exception $exception) {
            $io->error([
                'An error occurred while loading demo data:',
                $exception->getMessage(),
            ]);

            if ($output->isVerbose()) {
                $io->section('Stack trace:');
                $io->text($exception->getTraceAsString());
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
            // Document and invoice items (no foreign keys to other entities)
            'document',
            'invoice_items',
            'offer_items',
            // Invoices and offers (reference transactions)
            'invoice',
            'offer',
            // Transaction relationships
            'transaction_project',
            'transaction',
            // Campaign relationships and campaigns
            'campaign_project',
            'campaign',
            // Projects
            'project',
            // Contacts (may have self-referencing foreign key)
            'contact',
            // Categorizable entity junction table
            'categorizable_entity',
            // User relationships
            'user_user_group',
            'user',
            // Department
            'department',
            // Companies
            'company',
            'company_group',
            // User groups
            'user_group',
            // Categories (may have self-referencing foreign key for nested sets)
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
        } catch (Exception $exception) {
            throw new \RuntimeException(sprintf('Failed to drop/create schema: %s', $exception->getMessage()), 0, $exception);
        }
    }
}
