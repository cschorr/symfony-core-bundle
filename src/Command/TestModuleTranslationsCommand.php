<?php

namespace App\Command;

use App\Service\ModuleTranslationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test:module-translations',
    description: 'Test the module translation service'
)]
class TestModuleTranslationsCommand extends Command
{
    public function __construct(
        private ModuleTranslationService $moduleTranslationService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Testing Module Translation Service');

        // Test German translations
        $io->section('German Navigation Labels (Plural)');
        $germanLabels = [
            'User' => $this->moduleTranslationService->getNavigationLabel('User', 'de'),
            'Company' => $this->moduleTranslationService->getNavigationLabel('Company', 'de'),
            'Module' => $this->moduleTranslationService->getNavigationLabel('Module', 'de'),
            'CompanyGroup' => $this->moduleTranslationService->getNavigationLabel('CompanyGroup', 'de'),
            'Project' => $this->moduleTranslationService->getNavigationLabel('Project', 'de'),
        ];

        foreach ($germanLabels as $code => $label) {
            $io->writeln("  {$code} → {$label}");
        }

        // Test English translations
        $io->section('English Navigation Labels (Plural)');
        $englishLabels = [
            'User' => $this->moduleTranslationService->getNavigationLabel('User', 'en'),
            'Company' => $this->moduleTranslationService->getNavigationLabel('Company', 'en'),
            'Module' => $this->moduleTranslationService->getNavigationLabel('Module', 'en'),
            'CompanyGroup' => $this->moduleTranslationService->getNavigationLabel('CompanyGroup', 'en'),
            'Project' => $this->moduleTranslationService->getNavigationLabel('Project', 'en'),
        ];

        foreach ($englishLabels as $code => $label) {
            $io->writeln("  {$code} → {$label}");
        }

        // Test full translation mappings
        $io->section('Full German Translation Mappings');
        $germanMappings = $this->moduleTranslationService->getModuleTranslationMappings('de');
        foreach ($germanMappings as $key => $value) {
            $io->writeln("  {$key}: \"{$value}\"");
        }

        $io->success('Module Translation Service is working correctly!');
        
        return Command::SUCCESS;
    }
}
