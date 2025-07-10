<?php

namespace App\Command;

use App\Service\ModuleTranslationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'app:module:sync-translations',
    description: 'Synchronize module translations based on Module entity data'
)]
class ModuleSyncTranslationsCommand extends Command
{
    public function __construct(
        private ModuleTranslationService $moduleTranslationService,
        private ParameterBagInterface $parameterBag
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'Specific locale to sync (e.g., de, en)', null)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be changed without making actual changes')
            ->setHelp(
                'This command synchronizes module translations based on the Module entity data.' . PHP_EOL .
                'It uses the "code" field for singular forms and "name" field for plural forms.' . PHP_EOL . PHP_EOL .
                'Examples:' . PHP_EOL .
                '  php bin/console app:module:sync-translations' . PHP_EOL .
                '  php bin/console app:module:sync-translations --locale=de' . PHP_EOL .
                '  php bin/console app:module:sync-translations --dry-run'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $locale = $input->getOption('locale');
        $dryRun = $input->getOption('dry-run');

        $supportedLocales = ['de', 'en'];
        $localesToProcess = $locale ? [$locale] : $supportedLocales;

        foreach ($localesToProcess as $currentLocale) {
            if (!in_array($currentLocale, $supportedLocales)) {
                $io->warning("Locale '{$currentLocale}' is not supported. Skipping...");
                continue;
            }

            $this->syncTranslationsForLocale($currentLocale, $io, $dryRun);
        }

        $io->success('Module translation synchronization completed!');
        
        if (!$dryRun) {
            $io->note('Remember to clear the cache: php bin/console cache:clear');
        }

        return Command::SUCCESS;
    }

    private function syncTranslationsForLocale(string $locale, SymfonyStyle $io, bool $dryRun): void
    {
        $io->section("Processing locale: {$locale}");

        $projectDir = $this->parameterBag->get('kernel.project_dir');
        $translationFile = $projectDir . "/translations/messages.{$locale}.yaml";
        
        if (!file_exists($translationFile)) {
            $io->error("Translation file not found: {$translationFile}");
            return;
        }

        // Load existing translations
        $existingTranslations = Yaml::parseFile($translationFile);
        
        // Get module-based translations
        $moduleTranslations = $this->moduleTranslationService->generateTranslationsForLocale($locale);

        // Update the module translation section
        $updated = false;
        
        foreach ($moduleTranslations as $key => $translation) {
            if (!isset($existingTranslations[$key]) || $existingTranslations[$key] !== $translation) {
                $io->text("  {$key}: \"{$translation}\"");
                $existingTranslations[$key] = $translation;
                $updated = true;
            }
        }

        if ($updated && !$dryRun) {
            // Write back to file with proper formatting
            $yamlContent = $this->generateFormattedYaml($existingTranslations, $locale);
            file_put_contents($translationFile, $yamlContent);
            $io->success("Updated {$translationFile}");
        } elseif ($updated && $dryRun) {
            $io->note("Would update {$translationFile} (dry-run mode)");
        } else {
            $io->text("No changes needed for {$locale}");
        }
    }

    private function generateFormattedYaml(array $translations, string $locale): string
    {
        $languageName = $locale === 'de' ? 'German' : 'English';
        $header = "# {$languageName} translations for the application\n\n";
        
        // Generate the module translations section
        $moduleSection = "# Module/Entity Names (automatically generated from Module entity)\n";
        $moduleSection .= "# These keys match the \"code\" and \"name\" fields in the Module entity\n";
        
        $moduleTranslations = $this->moduleTranslationService->generateTranslationsForLocale($locale);
        
        foreach ($moduleTranslations as $key => $translation) {
            $moduleSection .= "{$key}: \"{$translation}\"\n";
        }
        
        // Remove module translations from the main array to avoid duplication
        foreach (array_keys($moduleTranslations) as $key) {
            unset($translations[$key]);
        }
        
        // Generate the rest of the YAML
        $remainingYaml = Yaml::dump($translations, 2, 2);
        
        // Remove the first line (---) from YAML output
        $remainingYaml = preg_replace('/^---\n/', '', $remainingYaml);
        
        return $header . $moduleSection . "\n" . $remainingYaml;
    }
}
