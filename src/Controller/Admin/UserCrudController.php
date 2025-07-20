<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Company;
use App\Controller\Admin\ProjectCrudController;
use App\Service\PermissionService;
use App\Service\DuplicateService;
use App\Service\EasyAdminFieldService;
use App\Service\RelationshipSyncService;
use App\Service\EmbeddedTableService;
use App\Controller\Admin\Traits\FieldConfigurationTrait;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserCrudController extends AbstractCrudController
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
        private EmbeddedTableService $embeddedTableService
    ) {
        parent::__construct($entityManager, $translator, $permissionService, $duplicateService, $requestStack);
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    protected function hasPermissionManagement(): bool
    {
        return true;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud);
    }

    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions);
    }

    #[IsGranted('read', subject: 'User')]
    public function index(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $User = 'User'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::index($context);
    }

    #[IsGranted('read', subject: 'User')]
    public function detail(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $User = 'User'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::detail($context);
    }

    #[IsGranted('write', subject: 'User')]
    public function edit(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $User = 'User'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        // Get the current user entity
        $user = $context->getEntity()->getInstance();

        // Refresh the entity from the database with all associations properly loaded
        if ($user->getId()) {
            $freshUser = $this->entityManager->getRepository(User::class)
                ->createQueryBuilder('u')
                ->leftJoin('u.company', 'c')
                ->addSelect('c')
                ->leftJoin('u.projects', 'p')
                ->addSelect('p')
                ->where('u.id = :id')
                ->setParameter('id', $user->getId())
                ->getQuery()
                ->getOneOrNullResult();

            if ($freshUser) {
                $context->getEntity()->setInstance($freshUser);
            }
        }

        return parent::edit($context);
    }

    #[IsGranted('write', subject: 'User')]
    public function new(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $User = 'User'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::new($context);
    }

    #[IsGranted('write', subject: 'User')]
    public function delete(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $User = 'User'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::delete($context);
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = $this->fieldService->generateFields(
            $this->getFieldConfigurations(),
            $pageName
        );

        // Add permission fields for edit/new pages with proper tabbed structure
        if ($pageName === Crud::PAGE_EDIT || $pageName === Crud::PAGE_NEW) {
            // Get the current entity from context if available
            $entity = null;
            $context = $this->getContext();
            if ($context && $context->getEntity()) {
                $entityInstance = $context->getEntity()->getInstance();
                if ($entityInstance instanceof User) {
                    $entity = $entityInstance;
                }
            }

            // Add permission tabs with entity data
            $fields = $this->permissionService->addPermissionTabToFields($fields, $entity);
        } elseif ($pageName === Crud::PAGE_INDEX || $pageName === Crud::PAGE_DETAIL) {
            // Add permission summary field for index and detail pages
            $fields = $this->addPermissionSummaryField($fields);
        }

        return $fields;
    }

    /**
     * Define all field configurations for the User entity using enhanced approach
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
     * Get fields specifically for index page (outside of any tabs)
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

            // Email field with link to detail for index page
            $this->fieldService->field('email')
                ->type('text')
                ->label('Email')
                ->formatValue(function ($value, $entity) {
                    $showUrl = $this->adminUrlGenerator
                        ->setController(self::class)
                        ->setAction(Action::DETAIL)
                        ->setEntityId($entity->getId())
                        ->generateUrl();

                    return sprintf('<a href="%s" class="text-decoration-none">%s</a>', $showUrl, $value);
                })
                ->renderAsHtml()
                ->pages(['index'])
                ->build(),

            $this->fieldService->field('roles')
                ->type('choice')
                ->label('Roles')
                ->choices([
                    $this->translator->trans('User') => 'ROLE_USER',
                    $this->translator->trans('Admin') => 'ROLE_ADMIN',
                ])
                ->multiple(true)
                ->pages(['index'])
                ->build(),

            $this->fieldService->field('company')
                ->type('association')
                ->label('Company')
                ->pages(['index'])
                ->build(),
        ];
    }

    /**
     * Get all fields organized into tabs for detail and form pages
     */
    private function getTabOrganizedFields(): array
    {
        $fields = [];

        // User Information Tab
        $fields[] = $this->fieldService->createTabConfig('user_info_tab', 'User Information');

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

        // User basic fields
        $fields = array_merge($fields, $this->getUserFields(['detail', 'form']));
        $fields = array_merge($fields, $this->getNotesField(['detail', 'form']));

        $fields[] = $this->fieldService->field('company')
            ->type('association')
            ->label('Company')
            ->pages(['detail', 'form'])
            ->autocomplete(true)
            ->build();

        // Projects Tab
        $fields[] = $this->fieldService->createTabConfig('projects_tab', 'Projects');
        $fields[] = $this->fieldService->field('projects')
            ->type('association')
            ->label('Projects')
            ->pages(['detail'])
            ->formatValue($this->embeddedTableService->createEmbeddedTableFormatter([
                'name' => 'Project Name',
                'status' => 'Status',
                'createdAt' => 'Created'
            ], 'Projects', 'No projects assigned'))
            ->renderAsHtml(true)
            ->build();

        // For form page, show regular association field for projects
        $fields[] = $this->fieldService->field('projects')
            ->type('association')
            ->label('Projects')
            ->multiple(true)
            ->pages(['form'])
            ->build();

        return $fields;
    }

    /**
     * Override to use the new relationship sync service
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->relationshipSyncService->autoSync($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Override to use the new relationship sync service
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->relationshipSyncService->autoSync($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }
}
