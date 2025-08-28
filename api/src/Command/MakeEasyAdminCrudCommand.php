<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Inflector\EnglishInflector;

#[AsCommand(name: 'make:easyadmin-crud', description: 'Generate a standardized EasyAdmin CRUD controller', help: <<<'TXT'
Generate a standardized EasyAdmin CRUD controller with best practices
TXT)]
class MakeEasyAdminCrudCommand
{
    private readonly EnglishInflector $inflector;

    public function __construct()
    {
        $this->inflector = new EnglishInflector();
    }

    public function __invoke(#[\Symfony\Component\Console\Attribute\Argument(name: 'entity', description: 'The entity class name')]
        string $entity, #[\Symfony\Component\Console\Attribute\Option]
        $no_relationships, #[\Symfony\Component\Console\Attribute\Option]
        $template, #[\Symfony\Component\Console\Attribute\Option]
        $with_tabs, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entityName = $entity;
        $template = $template;
        $withTabs = $with_tabs;
        $noRelationships = $no_relationships;

        // Validate entity exists
        $entityClass = 'App\Entity\\' . $entityName;
        if (!class_exists($entityClass)) {
            $io->error(sprintf('Entity class %s does not exist.', $entityClass));

            return Command::FAILURE;
        }

        $controllerPath = $this->generateControllerPath($entityName);

        if (file_exists($controllerPath)) {
            $io->warning('Controller already exists at ' . $controllerPath);
            if (!$io->confirm('Overwrite existing controller?', false)) {
                return Command::SUCCESS;
            }
        }

        $controllerContent = $this->generateControllerContent(
            $entityName,
            $template,
            $withTabs,
            $noRelationships
        );

        file_put_contents($controllerPath, $controllerContent);

        $io->success(sprintf('Generated CRUD controller for %s at %s', $entityName, $controllerPath));

        // Offer to add to menu
        if ($io->confirm('Add to EasyAdmin menu?', true)) {
            $this->suggestMenuEntry($io, $entityName);
        }

        return Command::SUCCESS;
    }

    private function generateControllerPath(string $entityName): string
    {
        return sprintf('src/Controller/Admin/%sCrudController.php', $entityName);
    }

    private function generateControllerContent(string $entityName, string $template, bool $withTabs, bool $noRelationships): string
    {
        $entityClass = 'App\Entity\\' . $entityName;
        $pluralName = $this->inflector->pluralize($entityName)[0] ?? $entityName . 's';

        $content = "<?php\n\n";
        $content .= "namespace App\\Controller\\Admin;\n\n";
        $content .= "use {$entityClass};\n";
        $content .= "use App\\Service\\CrudSchemaBuilder;\n";
        $content .= "use App\\Service\\EasyAdminFieldService;\n";
        $content .= "use App\\Controller\\Admin\\Traits\\StandardCrudControllerTrait;\n";
        $content .= "use EasyCorp\\Bundle\\EasyAdminBundle\\Config\\Crud;\n";
        $content .= "use EasyCorp\\Bundle\\EasyAdminBundle\\Controller\\AbstractCrudController;\n";

        if ($withTabs) {
            $content .= "use EasyCorp\\Bundle\\EasyAdminBundle\\Config\\Action;\n";
            $content .= "use EasyCorp\\Bundle\\EasyAdminBundle\\Config\\Actions;\n";
        }

        $content .= "\n";
        $content .= "class {$entityName}CrudController extends AbstractCrudController\n";
        $content .= "{\n";
        $content .= "    use StandardCrudControllerTrait;\n\n";

        $content .= "    public function __construct(\n";
        $content .= "        private CrudSchemaBuilder \$schemaBuilder,\n";
        $content .= "        private EasyAdminFieldService \$fieldService\n";
        $content .= "    ) {}\n\n";

        $content .= "    public static function getEntityFqcn(): string\n";
        $content .= "    {\n";
        $content .= "        return {$entityClass}::class;\n";
        $content .= "    }\n\n";

        $content .= "    public function configureCrud(Crud \$crud): Crud\n";
        $content .= "    {\n";
        $content .= "        return \$crud\n";
        $content .= "            ->setEntityLabelInSingular('{$entityName}')\n";
        $content .= "            ->setEntityLabelInPlural('{$pluralName}')\n";
        $content .= "            ->setSearchFields(['name'])\n";
        $content .= "            ->setDefaultSort(['createdAt' => 'DESC'])\n";
        $content .= "            ->setPaginatorPageSize(25);\n";
        $content .= "    }\n\n";

        if ($withTabs) {
            $content .= "    public function configureActions(Actions \$actions): Actions\n";
            $content .= "    {\n";
            $content .= "        return \$actions\n";
            $content .= "            ->add(Crud::PAGE_INDEX, Action::DETAIL)\n";
            $content .= "            ->remove(Crud::PAGE_INDEX, Action::DELETE);\n";
            $content .= "    }\n\n";
        }

        $content .= "    protected function getFieldSchema(): array\n";
        $content .= "    {\n";
        $content .= "        return [\n";
        $content .= "            // Standard fields\n";
        $content .= "            \$this->schemaBuilder->createField('id', 'id', 'ID', ['detail']),\n";
        $content .= "            \$this->schemaBuilder->createField('active', 'boolean', 'Active'),\n";
        $content .= "            \$this->schemaBuilder->createField('name', 'text', '{$entityName} Name', ['index', 'detail', 'form'], [\n";
        $content .= "                'required' => true,\n";
        $content .= "                'linkToShow' => true\n";
        $content .= "            ]),\n";
        $content .= "            \$this->schemaBuilder->createField('createdAt', 'datetime', 'Created At', ['index', 'detail']),\n";
        $content .= "            \$this->schemaBuilder->createField('updatedAt', 'datetime', 'Updated At', ['detail']),\n";
        $content .= "\n";
        $content .= "            // TODO: Add your custom fields here\n";
        $content .= "            // \$this->schemaBuilder->createField('description', 'textarea', 'Description', ['detail', 'form']),\n";

        if (!$noRelationships) {
            $content .= "\n";
            $content .= "            // TODO: Add relationship fields\n";
            $content .= "            // \$this->schemaBuilder->createAssociationField('relatedEntity', 'Related Entity'),\n";
        }

        $content .= "        ];\n";
        $content .= "    }\n\n";

        if ($withTabs) {
            $content .= "    protected function getTabSchema(): array\n";
            $content .= "    {\n";
            $content .= "        return [\n";
            $content .= "            \$this->schemaBuilder->createInfoTab('{$entityName}', [\n";
            $content .= "                // Add custom info fields here\n";
            $content .= "            ]),\n";
            $content .= "\n";
            $content .= "            // TODO: Add relationship tabs\n";
            $content .= "            // \$this->schemaBuilder->createRelationshipTab(\n";
            $content .= "            //     'relatedEntities',\n";
            $content .= "            //     'Related Entities',\n";
            $content .= "            //     [\n";
            $content .= "            //         ['property' => 'name', 'label' => 'Name'],\n";
            $content .= "            //         ['property' => 'active', 'label' => 'Status']\n";
            $content .= "            //     ]\n";
            $content .= "            // ),\n";
            $content .= "        ];\n";
            $content .= "    }\n\n";
        }

        $content .= "    // TODO: Customize field behavior\n";
        $content .= "    // public function configureFields(string \$pageName): iterable\n";
        $content .= "    // {\n";
        $content .= "    //     \$fields = parent::configureFields(\$pageName);\n";
        $content .= "    //\n";
        $content .= "    //     // Add custom field modifications here\n";
        $content .= "    //\n";
        $content .= "    //     return \$fields;\n";
        $content .= "    // }\n";
        $content .= "}\n";

        return $content;
    }

    private function suggestMenuEntry(SymfonyStyle $io, string $entityName): void
    {
        $pluralName = $this->inflector->pluralize($entityName)[0] ?? $entityName . 's';

        $menuEntry = sprintf("MenuItem::linkToCrud('%s', 'fas fa-list', %s::class),", $pluralName, $entityName);

        $io->note([
            'Add this menu item to your DashboardController:',
            '',
            $menuEntry,
        ]);
    }
}
