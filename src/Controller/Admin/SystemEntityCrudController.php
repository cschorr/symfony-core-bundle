<?php

namespace App\Controller\Admin;

use App\Entity\SystemEntity;
use App\Entity\User;
use App\Entity\UserSystemEntityPermission;
use App\Repository\UserSystemEntityPermissionRepository;
use App\Service\PermissionService;
use App\Service\DuplicateService;
use App\Service\EasyAdminFieldService;
use App\Service\RelationshipSyncService;
use App\Controller\Admin\Traits\FieldConfigurationTrait;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class SystemEntityCrudController extends AbstractCrudController
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
        private UserSystemEntityPermissionRepository $userSystemEntityPermissionRepository
    ) {
        parent::__construct($entityManager, $translator, $permissionService, $duplicateService, $requestStack);
    }

    public static function getEntityFqcn(): string
    {
        return SystemEntity::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setPageTitle('index', $this->translator->trans('System Entities'))
            ->setPageTitle('detail', fn ($entity) => sprintf('%s: %s', $this->translator->trans('SystemEntity'), $entity->getName()))
            ->setPageTitle('new', $this->translator->trans('Create System Entity'))
            ->setHelp('index', $this->translator->trans('Manage system entities and their permissions.'));
    }

    #[IsGranted('read', subject: 'SystemEntity')]
    public function index(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $SystemEntity = 'SystemEntity'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::index($context);
    }

    #[IsGranted('read', subject: 'SystemEntity')]
    public function detail(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $SystemEntity = 'SystemEntity'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::detail($context);
    }

    #[IsGranted('write', subject: 'SystemEntity')]
    public function edit(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $SystemEntity = 'SystemEntity'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::edit($context);
    }

    #[IsGranted('write', subject: 'SystemEntity')]
    public function delete(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $SystemEntity = 'SystemEntity'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::delete($context);
    }

    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions);
    }

    /**
     * Get field configuration for SystemEntity entity
     */
    public function configureFields(string $pageName): iterable
    {
        // Get base configuration from our new system
        $config = $this->getFieldConfiguration($pageName);
        $fields = $this->fieldService->generateFields($config, $pageName);

        // Add permission tab for form pages
        if ($pageName === Crud::PAGE_EDIT || $pageName === Crud::PAGE_NEW) {
            // Get the current entity from context if available
            $entity = null;
            $context = $this->getContext();
            if ($context && $context->getEntity()) {
                $entityInstance = $context->getEntity()->getInstance();
                if ($entityInstance instanceof SystemEntity) {
                    $entity = $entityInstance;
                }
            }

            // First, we need to wrap the basic fields in a tab
            $fieldsWithTabs = [];

            // Add basic information tab
            $fieldsWithTabs[] = FormField::addTab($this->translator->trans('System Entity Information'))
                ->setHelp($this->translator->trans('Basic information about the system entity'))
                ->collapsible();

            // Add all the basic fields
            foreach ($fields as $field) {
                $fieldsWithTabs[] = $field;
            }

            // Then add permission tabs with entity data
            $fieldsWithTabs = $this->permissionService->addSystemEntityPermissionTabToFieldsWithEntity($fieldsWithTabs, $entity);

            return $fieldsWithTabs;
        }

        return $fields;
    }

    /**
     * Get field configuration for SystemEntity entity
     */
    private function getFieldConfiguration(string $pageName): array
    {
        // Base configuration for all pages - includes active field first
        $config = [
            ...$this->getActiveField(), // Active field first for all pages
            $this->fieldService->createIdField(),
        ];

        // Page-specific field configurations
        if ($pageName === Crud::PAGE_INDEX) {
            $config = array_merge($config, [
                $this->fieldService->field('name')
                    ->type('text')
                    ->label('Name')
                    ->linkToShow() // This will auto-detect the SystemEntityCrudController
                    ->build(),

                $this->fieldService->field('code')
                    ->type('text')
                    ->label('Code')
                    ->build(),

                ...$this->getSystemEntityPermissionsSummaryField(),
            ]);

        } elseif ($pageName === Crud::PAGE_DETAIL) {
            $config = array_merge($config, [
                $this->fieldService->field('name')
                    ->type('text')
                    ->label('Name')
                    ->build(),

                $this->fieldService->field('code')
                    ->type('text')
                    ->label('Code')
                    ->build(),

                $this->fieldService->field('icon')
                    ->type('text')
                    ->label('Icon')
                    ->build(),

                $this->fieldService->field('text')
                    ->type('textarea')
                    ->label('Description')
                    ->build(),

                ...$this->getSystemEntityPermissionsSummaryField(),
                ...$this->getSystemEntityPermissionsDetailField(),
            ]);

        } else { // FORM pages (NEW/EDIT)
            $config = array_merge($config, [
                $this->fieldService->field('name')
                    ->type('text')
                    ->label('Name')
                    ->help('Display name for the system entity')
                    ->build(),

                $this->fieldService->field('code')
                    ->type('text')
                    ->label('Code')
                    ->help('Unique code that matches the entity name (e.g., User, Company, SystemEntity)')
                    ->formTypeOption('attr', ['placeholder' => 'e.g., User, Company, SystemEntity'])
                    ->build(),

                $this->fieldService->field('icon')
                    ->type('text')
                    ->label('Icon')
                    ->help('FontAwesome icon class (e.g., fas fa-users, fas fa-building)')
                    ->formTypeOption('attr', ['placeholder' => 'e.g., fas fa-users, fas fa-building'])
                    ->build(),

                $this->fieldService->field('text')
                    ->type('textarea')
                    ->label('Description')
                    ->help('Optional description of what this system entity manages')
                    ->build(),
            ]);
        }

        return $config;
    }

    /**
     * Get system entity permissions summary field configuration
     */
    private function getSystemEntityPermissionsSummaryField(): array
    {
        return [
            $this->fieldService->field('userPermissions')
                ->type('association')
                ->label('User Permissions')
                ->pages(['index', 'detail'])
                ->formatValue(function ($value, $entity) {
                    if (!$entity || !$entity->getUserPermissions() || $entity->getUserPermissions()->isEmpty()) {
                        return $this->translator->trans('No permissions assigned');
                    }

                    $count = $entity->getUserPermissions()->count();
                    return sprintf($this->translator->trans('%d permission(s) assigned'), $count);
                })
                ->build(),
        ];
    }

    /**
     * Get system entity permissions detail field configuration
     */
    private function getSystemEntityPermissionsDetailField(): array
    {
        return [
            $this->fieldService->field('userPermissions')
                ->type('association')
                ->label('Permission Details')
                ->pages(['detail'])
                ->formatValue(function ($value, $entity) {
                    if (!$entity || !$entity->getUserPermissions() || $entity->getUserPermissions()->isEmpty()) {
                        return $this->translator->trans('No permissions assigned');
                    }

                    $permissions = [];
                    foreach ($entity->getUserPermissions() as $permission) {
                        $user = $permission->getUser();
                        $userEmail = $user ? $user->getEmail() : $this->translator->trans('Unknown User');
                        $access = [];
                        if ($permission->canRead()) $access[] = $this->translator->trans('Read');
                        if ($permission->canWrite()) $access[] = $this->translator->trans('Write');
                        $accessString = implode(', ', $access);

                        $permissions[] = sprintf('%s: %s', $userEmail, $accessString);
                    }

                    return implode('<br>', $permissions);
                })
                ->renderAsHtml(true)
                ->build(),
        ];
    }

    /**
     * Override to use the new relationship sync service and handle permissions
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->processSystemEntityPermissions($entityInstance);
        $this->relationshipSyncService->autoSync($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Override to use the new relationship sync service and handle permissions
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->processSystemEntityPermissions($entityInstance);
        $this->relationshipSyncService->autoSync($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * Process system entity permission checkboxes from form
     */
    private function processSystemEntityPermissions($systemEntity): void
    {
        if (!$systemEntity instanceof SystemEntity) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $formData = $request->request->all();

        // Get all users to process their permissions
        $users = $this->entityManager->getRepository(User::class)->findAll();

        foreach ($users as $user) {
            $readFieldName = 'userPermission_read_' . $user->getId();
            $writeFieldName = 'userPermission_write_' . $user->getId();

            $canRead = isset($formData[$readFieldName]) && $formData[$readFieldName] === '1';
            $canWrite = isset($formData[$writeFieldName]) && $formData[$writeFieldName] === '1';

            // Find or create permission entity
            $permission = $this->userSystemEntityPermissionRepository
                ->findOneBy([
                    'user' => $user,
                    'systemEntity' => $systemEntity
                ]);

            if ($canRead || $canWrite) {
                if (!$permission) {
                    $permission = new UserSystemEntityPermission();
                    $permission->setUser($user);
                    $permission->setSystemEntity($systemEntity);
                }

                $permission->setCanRead($canRead);
                $permission->setCanWrite($canWrite);

                $this->entityManager->persist($permission);
            } elseif ($permission) {
                // Remove permission if both read and write are false
                $this->entityManager->remove($permission);
            }
        }

        $this->entityManager->flush();
    }

    protected function canCreateEntity(): bool
    {
        $user = $this->getUser();
        return $user instanceof \App\Entity\User && in_array('ROLE_ADMIN', $user->getRoles());
    }

    protected function canEditEntity($entity): bool
    {
        $user = $this->getUser();
        return $user instanceof \App\Entity\User && in_array('ROLE_ADMIN', $user->getRoles());
    }

    protected function canDeleteEntity($entity): bool
    {
        $user = $this->getUser();
        return $user instanceof \App\Entity\User && in_array('ROLE_ADMIN', $user->getRoles());
    }
}
