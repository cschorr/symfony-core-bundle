<?php

namespace App\Command;

use App\Service\LocaleService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:locale:check',
    description: 'Check locale configuration synchronization'
)]
class LocaleCheckCommand extends Command
{
    public function __construct(
        private LocaleService $localeService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $appLocales = $this->localeService->getSupportedLocales();
        
        $io->title('Locale Configuration Check');
        
        $io->section('Services.yaml Configuration');
        $io->listing($appLocales);
        
        $io->section('EasyAdmin Locale Mapping');
        foreach ($appLocales as $locale) {
            $displayName = $this->localeService->getLocaleDisplayName($locale);
            $io->text("• <info>$locale</info> → $displayName");
        }
        
        $io->section('Available Translation Files');
        $translationsDir = dirname(__DIR__, 2) . '/translations';
        $files = [];
        
        foreach ($appLocales as $locale) {
            $messagesFile = "$translationsDir/messages.$locale.yaml";
            $easyAdminFile = "$translationsDir/EasyAdminBundle.$locale.yaml";
            
            $files[] = [
                $locale,
                file_exists($messagesFile) ? '✅' : '❌',
                file_exists($easyAdminFile) ? '✅' : '❌'
            ];
        }
        
        $io->table(['Locale', 'messages.*.yaml', 'EasyAdminBundle.*.yaml'], $files);
        
        $io->success('Locale configuration check completed!');
        
        return Command::SUCCESS;
    }
}
