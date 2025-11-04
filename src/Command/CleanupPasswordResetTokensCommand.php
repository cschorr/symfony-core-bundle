<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Command;

use C3net\CoreBundle\Service\PasswordResetService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-password-reset-tokens',
    description: 'Remove expired and used password reset tokens from the database',
)]
class CleanupPasswordResetTokensCommand
{
    public function __construct(private readonly PasswordResetService $passwordResetService)
    {
    }

    public function __invoke(#[\Symfony\Component\Console\Attribute\Option(name: 'dry-run', mode: InputOption::VALUE_NONE, description: 'Show which tokens would be deleted without actually deleting them')]
    bool $dryRun = false, ?OutputInterface $output = null, ?\Symfony\Component\Console\Style\SymfonyStyle $io = null): int
    {
        $isDryRun = $dry_run;

        $io->title('Password Reset Tokens Cleanup');

        if ($isDryRun) {
            $io->note('DRY RUN MODE - No tokens will be deleted');
        }

        $io->info('Searching for expired and used password reset tokens...');

        try {
            if ($isDryRun) {
                // In dry run mode, we would need to query and count
                // For now, we'll just indicate what would happen
                $io->warning('Dry run mode: Would delete expired and used tokens');
                $io->info('To actually delete tokens, run without --dry-run option');

                return Command::SUCCESS;
            }

            $deletedCount = $this->passwordResetService->cleanupExpiredTokens();

            if ($deletedCount > 0) {
                $io->success(sprintf(
                    'Successfully deleted %d expired/used password reset token%s',
                    $deletedCount,
                    1 === $deletedCount ? '' : 's'
                ));

                $io->table(
                    ['Statistic', 'Value'],
                    [
                        ['Tokens Deleted', $deletedCount],
                        ['Timestamp', (new \DateTimeImmutable())->format('Y-m-d H:i:s')],
                    ]
                );
            } else {
                $io->info('No expired or used tokens found. Database is clean!');
            }

            return Command::SUCCESS;
        } catch (\Throwable $throwable) {
            $io->error('Failed to cleanup password reset tokens');
            $io->error($throwable->getMessage());

            if ($output->isVerbose()) {
                $io->block($throwable->getTraceAsString(), 'TRACE', 'fg=white;bg=red', ' ', true);
            }

            return Command::FAILURE;
        }
    }
}
