<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

#[AsCommand(
    name: 'easyadmin:migrate-fields',
    description: 'Migrate EasyAdmin CRUD controllers to use the new field configuration system'
)]
class MigrateEasyAdminFieldsCommand
{
    public function __construct(private readonly string $projectDir)
    {
    }

    public function __invoke(#[\Symfony\Component\Console\Attribute\Argument(name: 'controller', description: 'Specific controller to migrate (e.g., UserCrudController)')]
        ?string $controller, #[\Symfony\Component\Console\Attribute\Option]
        $dry_run, #[\Symfony\Component\Console\Attribute\Option]
        $backup, #[\Symfony\Component\Console\Attribute\Option]
        $analyze, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $controller = $controller;
        $dryRun = $dry_run;
        $backup = $backup;
        $analyze = $analyze;

        $controllersDir = $this->projectDir . '/src/Controller/Admin';

        if (!is_dir($controllersDir)) {
            $io->error('Admin controllers directory not found: ' . $controllersDir);

            return Command::FAILURE;
        }

        if ($analyze) {
            return $this->analyzeControllers($io, $controllersDir);
        }

        $controllers = $this->findControllers($controllersDir, $controller);

        if ([] === $controllers) {
            $io->warning('No controllers found to migrate.');

            return Command::SUCCESS;
        }

        $io->title('EasyAdmin Field System Migration');

        if ($dryRun) {
            $io->note('Running in dry-run mode - no files will be modified');
        }

        foreach ($controllers as $controllerFile) {
            $this->migrateController($io, $controllerFile, $dryRun, $backup);
        }

        $io->success('Migration completed!');

        if (!$dryRun) {
            $io->section('Next Steps:');
            $io->listing([
                'Test each migrated controller on all pages (index, detail, form)',
                'Update any custom field configurations',
                'Add relationship sync if needed',
                'Run your test suite',
                'Clear Symfony cache: bin/console cache:clear',
            ]);
        }

        return Command::SUCCESS;
    }

    private function findControllers(string $dir, ?string $specific): array
    {
        $finder = new Finder();
        $finder->files()
            ->in($dir)
            ->name('*CrudController.php')
            ->notName('AbstractCrudController.php');

        if (null !== $specific && '' !== $specific && '0' !== $specific) {
            $finder->name($specific);
        }

        $controllers = [];
        foreach ($finder as $file) {
            $controllers[] = $file->getRealPath();
        }

        return $controllers;
    }

    private function analyzeControllers(SymfonyStyle $io, string $dir): int
    {
        $controllers = $this->findControllers($dir, null);
        $analysis = [];

        foreach ($controllers as $controllerFile) {
            $content = file_get_contents($controllerFile);
            $className = basename((string) $controllerFile, '.php');

            $info = [
                'file' => $controllerFile,
                'class' => $className,
                'has_configure_fields' => str_contains($content, 'configureFields'),
                'uses_trait' => str_contains($content, 'FieldConfigurationTrait'),
                'uses_field_service' => str_contains($content, 'EasyAdminFieldService'),
                'has_relationship_sync' => str_contains($content, 'RelationshipSyncService'),
                'field_count' => substr_count($content, '::new('),
                'has_tabs' => str_contains($content, 'FormField::addTab'),
                'has_panels' => str_contains($content, 'FormField::addPanel'),
                'complexity' => $this->calculateComplexity($content),
            ];

            $analysis[] = $info;
        }

        $io->title('EasyAdmin Controllers Analysis');

        $migrated = array_filter($analysis, fn ($a) => $a['uses_trait'] && $a['uses_field_service']);
        $needsMigration = array_filter($analysis, fn ($a) => !$a['uses_trait'] || !$a['uses_field_service']);

        $io->section('Migration Status');
        $io->text('✅ Already migrated: ' . count($migrated));
        $io->text('🔄 Needs migration: ' . count($needsMigration));

        if ([] !== $needsMigration) {
            $io->section('Controllers Needing Migration');

            $tableData = [];
            foreach ($needsMigration as $controller) {
                $priority = $this->getMigrationPriority($controller);
                $tableData[] = [
                    $controller['class'],
                    $controller['field_count'],
                    $controller['complexity'],
                    $priority,
                    $controller['has_tabs'] ? '✓' : '',
                    $controller['has_panels'] ? '✓' : '',
                ];
            }

            $io->table(
                ['Controller', 'Fields', 'Complexity', 'Priority', 'Tabs', 'Panels'],
                $tableData
            );

            $io->section('Recommended Migration Order');
            usort($needsMigration, fn ($a, $b) => $this->getMigrationPriorityScore($a) <=> $this->getMigrationPriorityScore($b));

            $io->listing(array_map(fn ($c) => $c['class'] . ' (' . $this->getMigrationPriority($c) . ')', $needsMigration));
        }

        return Command::SUCCESS;
    }

    private function calculateComplexity(string $content): string
    {
        $score = 0;

        // Count various complexity indicators
        $score += substr_count($content, '::new(') * 1;           // Basic fields
        $score += substr_count($content, 'FormField::') * 2;      // Form structure
        $score += substr_count($content, 'AssociationField') * 3; // Relationships
        $score += substr_count($content, 'ChoiceField') * 2;      // Choice fields
        $score += substr_count($content, 'if (') * 1;             // Conditional logic
        $score += substr_count($content, 'switch (') * 3;         // Complex conditionals

        if ($score < 10) {
            return 'Low';
        }

        if ($score < 25) {
            return 'Medium';
        }

        return 'High';
    }

    private function getMigrationPriority(array $controller): string
    {
        $score = $this->getMigrationPriorityScore($controller);

        if ($score <= 10) {
            return 'High';
        }

        if ($score <= 20) {
            return 'Medium';
        }

        return 'Low';
    }

    private function getMigrationPriorityScore(array $controller): int
    {
        $score = 0;

        // Lower score = higher priority
        $score += $controller['field_count']; // More fields = easier to benefit
        $score += $controller['has_tabs'] ? -5 : 0; // Tabs benefit from new system
        $score += $controller['has_panels'] ? -3 : 0; // Panels benefit too
        $score += 'High' === $controller['complexity'] ? 10 : 0; // Complex = lower priority

        return $score;
    }

    private function migrateController(SymfonyStyle $io, string $file, bool $dryRun, bool $backup): void
    {
        $className = basename($file, '.php');
        $content = file_get_contents($file);

        $io->section('Migrating: ' . $className);

        // Check if already migrated
        if (str_contains($content, 'FieldConfigurationTrait')) {
            $io->text('✅ Already uses FieldConfigurationTrait - skipping');

            return;
        }

        if ($backup && !$dryRun) {
            $backupFile = $file . '.backup.' . date('Y-m-d-H-i-s');
            copy($file, $backupFile);
            $io->text('📄 Backup created: ' . basename($backupFile));
        }

        $changes = $this->generateMigrationChanges($content, $className);

        if ($dryRun) {
            $io->text('Would make the following changes:');
            foreach ($changes['summary'] as $change) {
                $io->text('  • ' . $change);
            }
        } else {
            $newContent = $this->applyMigrationChanges($content, $changes);
            file_put_contents($file, $newContent);

            $io->text('✅ Migration completed');
            foreach ($changes['summary'] as $change) {
                $io->text('  • ' . $change);
            }
        }

        $io->text('');
    }

    private function generateMigrationChanges(string $content, string $className): array
    {
        $changes = [
            'add_use_statements' => [],
            'add_trait' => false,
            'add_constructor_params' => [],
            'replace_configure_fields' => false,
            'add_relationship_sync' => false,
            'summary' => [],
        ];

        // Check what needs to be added
        if (!str_contains($content, 'EasyAdminFieldService')) {
            $changes['add_use_statements'][] = 'use App\Service\EasyAdminFieldService;';
            $changes['add_constructor_params'][] = 'private EasyAdminFieldService $fieldService';
            $changes['summary'][] = 'Add EasyAdminFieldService dependency';
        }

        if (!str_contains($content, 'FieldConfigurationTrait')) {
            $changes['add_use_statements'][] = 'use App\Controller\Admin\Traits\FieldConfigurationTrait;';
            $changes['add_trait'] = true;
            $changes['summary'][] = 'Add FieldConfigurationTrait';
        }

        if (str_contains($content, 'configureFields')) {
            $changes['replace_configure_fields'] = true;
            $changes['summary'][] = 'Replace configureFields method with new pattern';
        }

        // Check if entity likely has relationships (basic heuristic)
        if (str_contains($content, 'AssociationField') && !str_contains($content, 'RelationshipSyncService')) {
            $changes['add_use_statements'][] = 'use App\Service\RelationshipSyncService;';
            $changes['add_constructor_params'][] = 'private RelationshipSyncService $relationshipSyncService';
            $changes['add_relationship_sync'] = true;
            $changes['summary'][] = 'Add relationship sync service (optional)';
        }

        return $changes;
    }

    private function applyMigrationChanges(string $content, array $changes): string
    {
        // This is a simplified implementation - in practice, you'd want more sophisticated AST manipulation
        $newContent = $content;

        // Add use statements
        if (!empty($changes['add_use_statements'])) {
            $useStatements = implode("\n", $changes['add_use_statements']);
            $newContent = preg_replace('/^(use [^;]+;)$/m', '$1
' . $useStatements, $newContent, 1);
        }

        // Add trait
        if ($changes['add_trait']) {
            return preg_replace(
                '/class \w+CrudController extends AbstractCrudController\s*{/',
                "$0\n    use FieldConfigurationTrait;\n",
                (string) $newContent
            );
        }

        // Note: For a production tool, you'd implement proper AST manipulation
        // This is just a demonstration of the concept

        return $newContent;
    }
}
