<?php

namespace App\Controller\Admin;

use App\Entity\Module;
use App\Service\PermissionService;
use App\Service\DuplicateService;
use App\Service\EasyAdminFieldService;
use App\Service\RelationshipSyncService;
use App\Controller\Admin\Traits\FieldConfigurationTrait;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class ModuleCrudController extends AbstractCrudController
{
    use FieldConfigurationTrait;

    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        PermissionService $permissionService,
        DuplicateService $duplicateService,
        RequestStack $requestStack,
        private EasyAdminFieldService $fieldService,
        private RelationshipSyncService $relationshipSyncService
    ) {
        parent::__construct($entityManager, $translator, $permissionService, $duplicateService, $requestStack);
    }

    public static function getEntityFqcn(): string
    {
        return Module::class;
    }

    protected function getModuleCode(): string
    {
        return 'Module';
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setPageTitle('index', $this->translator->trans('System Modules'))
            ->setPageTitle('detail', fn ($entity) => sprintf('%s: %s', $this->translator->trans('Module'), $entity->getName()))
            ->setPageTitle('new', $this->translator->trans('Create System Module'))
            ->setHelp('index', $this->translator->trans('Manage system modules and their permissions.'));
    }

    #[IsGranted('read', subject: 'Module')]
    public function index(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Module = 'Module'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::index($context);
    }

    #[IsGranted('read', subject: 'Module')]
    public function detail(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Module = 'Module'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::detail($context);
    }

    #[IsGranted('write', subject: 'Module')]
    public function edit(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Module = 'Module'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::edit($context);
    }

    #[IsGranted('write', subject: 'Module')]
    public function delete(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Module = 'Module'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::delete($context);
    }

    public function configureFields(string $pageName): iterable
    {
        // Get base configuration from our new system
        $config = $this->getFieldConfiguration($pageName);
        return $this->fieldService->generateFields($config, $pageName);
    }

    /**
     * Get field configuration for Module entity
     */
    private function getFieldConfiguration(string $pageName): array
    {
        // Base configuration for all pages
        $config = [
            $this->fieldService->createIdField(),
        ];

        // Page-specific field configurations
        if ($pageName === Crud::PAGE_INDEX) {
            $config = array_merge($config, [
                $this->fieldService->field('name')
                    ->type('text')
                    ->label('Name')
                    ->build(),
                    
                $this->getModulePermissionsSummaryField(),
                
                ...$this->getActiveField(['index']),
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
                    
                $this->getModulePermissionsSummaryField(),
                $this->getModulePermissionsDetailField(),
                
                ...$this->getActiveField(['detail']),
            ]);
            
        } else { // FORM pages (NEW/EDIT)
            $config = array_merge($config, [
                $this->fieldService->field('name')
                    ->type('text')
                    ->label('Name')
                    ->help('Display name for the module')
                    ->build(),
                    
                $this->fieldService->field('code')
                    ->type('text')
                    ->label('Code')
                    ->help('Unique code that matches the entity name (e.g., User, Company, Module)')
                    ->formTypeOption('attr', ['placeholder' => 'e.g., User, Company, Module'])
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
                    ->help('Optional description of what this module manages')
                    ->build(),
                    
                ...$this->getActiveField(['form']),
            ]);
        }

        return $config;
    }

    /**
     * Get module permissions summary field configuration
     */
    private function getModulePermissionsSummaryField(): array
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
     * Get module permissions detail field configuration
     */
    private function getModulePermissionsDetailField(): array
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
