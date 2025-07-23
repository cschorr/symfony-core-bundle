<?php

namespace App\Command;

use App\Service\LocaleService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:locale:sync',
    description: 'Synchronize locale configuration and generate route patterns'
)]
class LocaleSyncCommand extends Command
{
    public function __construct(
        private LocaleService $localeService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Locale Configuration Synchronization');

        $supportedLocales = $this->localeService->getSupportedLocales();
        $routePattern = $this->localeService->getLocaleRoutePattern();

        $io->section('Current Configuration');
        $io->text("Supported locales: <info>" . implode(', ', $supportedLocales) . "</info>");
        $io->text("Route pattern (auto-generated): <info>$routePattern</info>");

        $io->section('EasyAdmin Locale Mapping');
        foreach ($supportedLocales as $locale) {
            $displayName = $this->localeService->getLocaleDisplayName($locale);
            $io->text("• <info>$locale</info> → $displayName");
        }

        $io->section('Translation Files Status');
        $translationsDir = dirname(__DIR__, 2) . '/translations';
        $files = [];

        foreach ($supportedLocales as $locale) {
            $messagesFile = "$translationsDir/messages.$locale.yaml";
            $easyAdminFile = "$translationsDir/EasyAdminBundle.$locale.yaml";

            $files[] = [
                $locale,
                file_exists($messagesFile) ? '✅' : '❌',
                file_exists($easyAdminFile) ? '✅' : '❌'
            ];
        }

        $io->table(['Locale', 'messages.*.yaml', 'EasyAdminBundle.*.yaml'], $files);

        $io->success('✅ Route pattern automatically generated from app.locales!');

        $io->note([
            'Adding/removing locales is now super simple:',
            '1. Edit app.locales in config/services.yaml',
            '2. Update display names in LocaleService::getLocaleDisplayName()',
            '3. Create missing translation files if needed',
            '4. Clear cache: bin/console cache:clear',
            '',
            '✨ No manual pattern updates needed - everything is automatic!'
        ]);

        return Command::SUCCESS;
    }
}
