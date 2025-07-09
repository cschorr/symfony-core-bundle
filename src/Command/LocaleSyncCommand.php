<?php

namespace App\Command;

use App\Service\LocaleService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

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
        $io->text("Route pattern: <info>$routePattern</info>");
        
        $io->section('EasyAdmin Locale Mapping');
        foreach ($supportedLocales as $locale) {
            $displayName = $this->localeService->getLocaleDisplayName($locale);
            $io->text("• <info>$locale</info> → $displayName");
        }
        
        // Update services.yaml with the generated pattern
        $servicesFile = dirname(__DIR__, 2) . '/config/services.yaml';
        if (file_exists($servicesFile)) {
            $config = Yaml::parseFile($servicesFile);
            $config['parameters']['app.locales.pattern'] = $routePattern;
            
            file_put_contents($servicesFile, Yaml::dump($config, 4, 2));
            $io->success("Updated services.yaml with route pattern: $routePattern");
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
        
        $io->note([
            'After adding/removing locales in services.yaml:',
            '1. Run: bin/console app:locale:sync',
            '2. Update display names in LocaleService::getLocaleDisplayName()',
            '3. Create missing translation files if needed',
            '4. Clear cache: bin/console cache:clear'
        ]);
        
        return Command::SUCCESS;
    }
}
