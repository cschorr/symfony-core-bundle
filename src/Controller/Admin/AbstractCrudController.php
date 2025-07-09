<?php

namespace App\Controller\Admin;

use App\Entity\Module;
use App\Entity\User;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController as EasyAdminAbstractCrudController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractCrudController extends EasyAdminAbstractCrudController
{
    protected EntityManagerInterface $entityManager;
    protected TranslatorInterface $translator;
    protected PermissionService $permissionService;

    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        PermissionService $permissionService
    ) {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->permissionService = $permissionService;
    }

    /**
     * Get the module code that corresponds to the entity name
     */
    abstract protected function getModuleCode(): string;
    
    /**
     * Get the module name associated with this CRUD controller
     * Must be implemented by each concrete controller
     */
    abstract protected function getModuleName(): string;

    /**
     * Get the module entity for permission checking
     */
    protected function getModule(): Module
    {
        return $this->permissionService->getModuleByCode($this->getModuleCode());
    }

    /**
     * Configure CRUD with permission-based actions
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', sprintf('%s %s', $this->translator->trans($this->getModuleName()), $this->translator->trans('Management')))
            ->setPageTitle('detail', fn ($entity) => sprintf('%s %s', $this->translator->trans($this->getModuleName()), $this->translator->trans('Show')))
            ->setPageTitle('new', sprintf('%s %s', $this->translator->trans($this->getModuleName()), $this->translator->trans('Create')))
            ->setPageTitle('edit', fn ($entity) => sprintf('%s %s', $this->translator->trans($this->getModuleName()), $this->translator->trans('Edit')))
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->showEntityActionsInlined(false); // Show actions as dropdown menu
    }

    /**
     * Configure actions based on permissions
     */
    public function configureActions(Actions $actions): Actions
    {
        $actions = $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn (Action $action) => $action->setLabel($this->translator->trans('Show'))->setIcon('fa fa-eye'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action->setLabel($this->translator->trans('Edit'))->setIcon('fa fa-edit'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action->setLabel($this->translator->trans('Delete'))->setIcon('fa fa-trash'))
            ->setPermission(Action::DETAIL, 'ROLE_USER')
            ->setPermission(Action::NEW, 'ROLE_USER')
            ->setPermission(Action::EDIT, 'ROLE_USER')
            ->setPermission(Action::DELETE, 'ROLE_USER');
        
        // Check permissions and disable actions accordingly
        if (!$this->isGranted('read', $this->getModule())) {
            $actions
                ->disable(Action::INDEX)
                ->disable(Action::DETAIL);
        }

        if (!$this->isGranted('write', $this->getModule())) {
            $actions
                ->disable(Action::NEW)
                ->disable(Action::EDIT)
                ->disable(Action::DELETE)
                ->disable(Action::BATCH_DELETE);
        }

        return $actions;
    }

    /**
     * Get a human-readable label for an entity
     */
    protected function getEntityLabel($entity): string
    {
        // If entity has toString method, use it directly (already localized)
        if (method_exists($entity, '__toString')) {
            return (string) $entity;
        }
        
        // Otherwise, translate the module name
        return $this->translator->trans($this->getModuleName());
    }

    /**
     * Check if this controller should show the user permission management UI
     */
    protected function hasPermissionManagement(): bool
    {
        return false;
    }

    /**
     * Create module permission fields for entities that support permission management
     */
    protected function createModulePermissionFields(): array
    {
        if (!$this->hasPermissionManagement()) {
            return [];
        }

        return $this->permissionService->createModulePermissionFields($this->getContext());
    }

    /**
     * Add permission tab to form fields for entities that support permission management
     */
    protected function addPermissionTabToFields(array $fields): array
    {
        if (!$this->hasPermissionManagement()) {
            return $fields;
        }

        return $this->permissionService->addPermissionTabToFields($fields, $this->getContext());
    }

    /**
     * Add permission summary field for index pages
     */
    protected function addPermissionSummaryField(array $fields): array
    {
        if (!$this->hasPermissionManagement()) {
            return $fields;
        }

        return $this->permissionService->addPermissionSummaryField($fields);
    }

    /**
     * Override to handle permissions on entity creation
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$this->hasPermissionManagement()) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        $this->permissionService->persistEntityWithPermissions(
            fn($entity) => parent::persistEntity($entityManager, $entity),
            $entityInstance,
            $this->getContext()
        );
    }

    /**
     * Override to handle permissions on entity update
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$this->hasPermissionManagement()) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        $this->permissionService->updateEntityWithPermissions(
            fn($entity) => parent::updateEntity($entityManager, $entity),
            $entityInstance,
            $this->getContext()
        );
    }
}
