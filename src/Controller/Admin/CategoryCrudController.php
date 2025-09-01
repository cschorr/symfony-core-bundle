<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\Traits\FieldConfigurationTrait;
use App\Entity\Category;
use App\Service\DuplicateService;
use App\Service\EasyAdminFieldService;
use App\Service\EmbeddedTableService;
use App\Service\PermissionService;
use App\Service\RelationshipSyncService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class CategoryCrudController extends AbstractCrudController
{
    use FieldConfigurationTrait;

    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        PermissionService $permissionService,
        DuplicateService $duplicateService,
        RequestStack $requestStack,
        private EasyAdminFieldService $fieldService,
        private RelationshipSyncService $relationshipSyncService,
        private AdminUrlGenerator $adminUrlGenerator,
        private EmbeddedTableService $embeddedTableService,
    ) {
        parent::__construct($entityManager, $translator, $permissionService, $duplicateService, $requestStack);
    }

    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    #[\Override]
    protected function hasPermissionManagement(): bool
    {
        return false;
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud);
    }

    #[IsGranted('read', subject: 'Category')]
    #[\Override]
    public function index(AdminContext $context, string $Category = 'Category'): KeyValueStore|Response
    {
        return parent::index($context);
    }

    #[IsGranted('read', subject: 'Category')]
    #[\Override]
    public function detail(AdminContext $context, string $Category = 'Category'): KeyValueStore|Response
    {
        return parent::detail($context);
    }

    #[IsGranted('write', subject: 'Category')]
    #[\Override]
    public function edit(AdminContext $context, string $Category = 'Category'): KeyValueStore|Response
    {
        return parent::edit($context);
    }

    #[IsGranted('write', subject: 'Category')]
    #[\Override]
    public function delete(AdminContext $context, string $Category = 'Category'): KeyValueStore|Response
    {
        return parent::delete($context);
    }

    /**
     * Check if a category can be deleted (no related records).
     */
    protected function canDeleteEntity($entity): bool
    {
        if (!$entity instanceof Category) {
            return true;
        }
        return true;
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        return $this->fieldService->generateFields(
            $this->getFieldConfigurations(),
            $pageName
        );
    }

    /**
     * Define all field configurations for the Category entity using enhanced approach.
     */
    private function getFieldConfigurations(): array
    {
        $fields = [];

        // Fields for index page only (no tabs on index)
        $fields = array_merge($fields, $this->getIndexPageFields());

        // For detail and form pages, organize everything into tabs
        $fields = array_merge($fields, $this->getTabOrganizedFields());

        return $fields;
    }

    /**
     * Get fields specifically for index page (outside of any tabs).
     */
    private function getIndexPageFields(): array
    {
        return [
            // Active field for index page only
            $this->fieldService->field('active')
                ->type('boolean')
                ->label('Active')
                ->pages(['index'])
                ->build(),

            // Category name with link to detail for index page
            $this->fieldService->field('name')
                ->type('text')
                ->label('Name')
                ->required(true)
                ->linkToShow() // This will auto-detect the CategoryCrudController
                ->pages(['index'])
                ->build(),

            // Additional fields for index view
            #$this->fieldService->createFieldConfig('companyGroup', 'association', ['index'], 'Company Group'),

            // Contact information for index view
            $this->fieldService->createFieldConfig('url', 'url', ['index'], 'Website'),
            $this->fieldService->createCountryFieldConfig('countryCode', ['index'], 'Country'),
        ];
    }

    /**
     * Get all fields organized into tabs for detail and form pages.
     */
    private function getTabOrganizedFields(): array
    {
        $fields = [];

        // Category Information Tab
        $fields[] = $this->fieldService->createTabConfig('category_info_tab', 'Category Information');

        // Active field for detail and form pages
        $fields[] = $this->fieldService->field('active')
            ->type('boolean')
            ->label('Active')
            ->pages(['detail', 'form'])
            ->build();

        $fields[] = $this->fieldService->field('id')
            ->type('id')
            ->label('ID')
            ->pages(['detail']) // ID only on detail page, not form
            ->build();

        $fields[] = $this->fieldService->field('name')
            ->type('text')
            ->label('Name')
            ->required(true)
            ->pages(['detail', 'form']) // Show on detail and form pages inside tab
            ->build();

        return $fields;
    }

    /**
     * Get contact fields for use inside tabs (without panels).
     */
    private function getContactFieldsForTabs(): array
    {
        return [
            // Communication fields
            $this->fieldService->createFieldConfig('email', 'email', ['detail', 'form'], 'Email Address'),
            $this->fieldService->createFieldConfig('phone', 'telephone', ['detail', 'form'], 'Phone Number'),
            $this->fieldService->createFieldConfig('cell', 'telephone', ['detail', 'form'], 'Mobile/Cell Phone'),
            $this->fieldService->createFieldConfig('url', 'url', ['detail', 'form'], 'Website'),

            // Address fields
            $this->fieldService->createFieldConfig('street', 'text', ['detail', 'form'], 'Street Address'),
            $this->fieldService->createFieldConfig('zip', 'text', ['detail', 'form'], 'ZIP/Postal Code'),
            $this->fieldService->createFieldConfig('city', 'text', ['detail', 'form'], 'City'),
            $this->fieldService->createCountryFieldConfig('countryCode', ['detail', 'form'], 'Country'),
        ];
    }

    /**
     * Auto-sync relationships using the service.
     */
    #[\Override]
    protected function autoSyncRelationships(object $entity): void
    {
        if ($entity instanceof Category) {
            $this->relationshipSyncService->autoSync($entity);
        }
    }

    /**
     * Override to use the new relationship sync service.
     */
    #[\Override]
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->beforePersist($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Override to use the new relationship sync service.
     */
    #[\Override]
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->beforeUpdate($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }
}
