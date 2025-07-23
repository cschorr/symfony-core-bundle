<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\LocaleService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:locale:check',
    description: 'Check locale configuration synchronization'
)]
class LocaleCheckCommand
{
    public function __construct(private readonly LocaleService $localeService)
    {
    }

    public function __invoke(OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $appLocales = $this->localeService->getSupportedLocales();

        $io->title('Locale Configuration Check');

        $io->section('Services.yaml Configuration');
        $io->listing($appLocales);

        $io->section('EasyAdmin Locale Mapping');
        foreach ($appLocales as $locale) {
            $displayName = $this->localeService->getLocaleDisplayName($locale);
            $io->text(sprintf('• <info>%s</info> → %s', $locale, $displayName));
        }

        $io->section('Available Translation Files');
        $translationsDir = dirname(__DIR__, 2) . '/translations';
        $files = [];

        foreach ($appLocales as $locale) {
            $messagesFile = sprintf('%s/messages.%s.yaml', $translationsDir, $locale);
            $easyAdminFile = sprintf('%s/EasyAdminBundle.%s.yaml', $translationsDir, $locale);

            $files[] = [
                $locale,
                file_exists($messagesFile) ? '✅' : '❌',
                file_exists($easyAdminFile) ? '✅' : '❌',
            ];
        }

        $io->table(['Locale', 'messages.*.yaml', 'EasyAdminBundle.*.yaml'], $files);

        $io->success('Locale configuration check completed!');

        return Command::SUCCESS;
    }
}
