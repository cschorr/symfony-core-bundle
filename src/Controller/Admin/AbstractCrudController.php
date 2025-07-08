<?php

namespace App\Controller\Admin;

use App\Entity\Module;
use App\Entity\User;
use App\Repository\UserModulePermissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController as EasyAdminAbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Exception\ForbiddenActionException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

abstract class AbstractCrudController extends EasyAdminAbstractCrudController
{
    protected EntityManagerInterface $entityManager;
    protected UserModulePermissionRepository $permissionRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserModulePermissionRepository $permissionRepository
    ) {
        $this->entityManager = $entityManager;
        $this->permissionRepository = $permissionRepository;
    }

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
        $module = $this->entityManager->getRepository(Module::class)
            ->findOneBy(['name' => $this->getModuleName()]);
            
        if (!$module) {
            throw new \RuntimeException(
                sprintf('Module "%s" not found. Please run fixtures.', $this->getModuleName())
            );
        }
        
        return $module;
    }

    /**
     * Check if current user has permission for the given action
     */
    protected function hasPermission(string $permission): bool
    {
        // Admin users have all permissions
        if ($this->isAdmin()) {
            return true;
        }

        try {
            return $this->isGranted($permission, $this->getModule());
        } catch (\Exception $e) {
            // If module doesn't exist, only admins can access
            return false;
        }
    }

    /**
     * Check permission and throw exception if not granted
     */
    protected function checkPermission(string $permission): void
    {
        // Admin users bypass all permission checks
        if ($this->isAdmin()) {
            return;
        }

        if (!$this->hasPermission($permission)) {
            throw new AccessDeniedException(
                sprintf('Access denied. You need "%s" permission for module "%s".', 
                    $permission, 
                    $this->getModuleName()
                )
            );
        }
    }

    /**
     * Check if current user is admin
     */
    protected function isAdmin(): bool
    {
        $user = $this->getUser();
        return $user instanceof User && in_array('ROLE_ADMIN', $user->getRoles());
    }

    /**
     * Configure CRUD with permission-based actions
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', sprintf('%s Management', $this->getModuleName()))
            ->setPageTitle('detail', fn ($entity) => sprintf('View %s', $this->getEntityLabel($entity)))
            ->setPageTitle('new', sprintf('Create %s', $this->getModuleName()))
            ->setPageTitle('edit', fn ($entity) => sprintf('Edit %s', $this->getEntityLabel($entity)))
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->showEntityActionsInlined();
    }

    /**
     * Configure actions based on permissions
     */
    public function configureActions(Actions $actions): Actions
    {
        // Check read permission for index and detail actions
        if (!$this->hasPermission('read')) {
            $actions
                ->disable(Action::INDEX)
                ->disable(Action::DETAIL);
        }

        // Check write permission for create, edit, and delete actions
        if (!$this->hasPermission('write')) {
            $actions
                ->disable(Action::NEW)
                ->disable(Action::EDIT)
                ->disable(Action::DELETE)
                ->disable(Action::BATCH_DELETE);
        }

        return $actions;
    }

    /**
     * Override index to check read permission
     */
    public function index(AdminContext $context)
    {
        $this->checkPermission('read');
        return parent::index($context);
    }

    /**
     * Override detail to check read permission
     */
    public function detail(AdminContext $context)
    {
        $this->checkPermission('read');
        return parent::detail($context);
    }

    /**
     * Override new to check write permission
     */
    public function new(AdminContext $context)
    {
        $this->checkPermission('write');
        return parent::new($context);
    }

    /**
     * Override edit to check write permission
     */
    public function edit(AdminContext $context)
    {
        $this->checkPermission('write');
        return parent::edit($context);
    }

    /**
     * Override delete to check write permission
     */
    public function delete(AdminContext $context)
    {
        $this->checkPermission('write');
        return parent::delete($context);
    }

    /**
     * Override batch delete to check write permission
     */
    public function batchDelete(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        $this->checkPermission('write');
        return parent::batchDelete($context, $batchActionDto);
    }

    /**
     * Get a human-readable label for an entity
     * Can be overridden by concrete controllers
     */
    protected function getEntityLabel($entity): string
    {
        if (method_exists($entity, '__toString')) {
            return (string) $entity;
        }
        
        if (method_exists($entity, 'getName')) {
            return $entity->getName();
        }
        
        if (method_exists($entity, 'getTitle')) {
            return $entity->getTitle();
        }
        
        if (method_exists($entity, 'getId')) {
            return sprintf('%s #%s', $this->getModuleName(), $entity->getId());
        }
        
        return $this->getModuleName();
    }

    /**
     * Get current user with proper type checking
     */
    protected function getCurrentUser(): ?User
    {
        $user = $this->getUser();
        return $user instanceof User ? $user : null;
    }

    /**
     * Helper method to create permission-aware association fields
     * This can be used by concrete controllers to show only accessible related entities
     */
    protected function getAccessibleModules(): array
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return [];
        }

        // Admin users see all modules
        if ($this->isAdmin()) {
            return $this->entityManager->getRepository(Module::class)->findAll();
        }

        // Regular users see only modules they have read access to
        return $this->permissionRepository->findModulesWithReadAccess($user);
    }

    /**
     * Helper method to check if entity creation is allowed
     * Can be overridden for custom business logic
     */
    protected function canCreateEntity(): bool
    {
        return $this->hasPermission('write');
    }

    /**
     * Helper method to check if entity editing is allowed
     * Can be overridden for custom business logic
     */
    protected function canEditEntity($entity): bool
    {
        return $this->hasPermission('write');
    }

    /**
     * Helper method to check if entity deletion is allowed
     * Can be overridden for custom business logic
     */
    protected function canDeleteEntity($entity): bool
    {
        return $this->hasPermission('write');
    }

    /**
     * Helper method for common field configurations
     * Returns basic fields that most entities will have
     */
    protected function getCommonFields(): array
    {
        return [
            'id' => ['type' => 'id', 'hideOnForm' => true],
            'createdAt' => ['type' => 'datetime', 'hideOnForm' => true, 'hideOnIndex' => true],
            'updatedAt' => ['type' => 'datetime', 'hideOnForm' => true, 'hideOnIndex' => true],
        ];
    }

    /**
     * Helper method to handle common pre-persist operations
     * Can be overridden by concrete controllers
     */
    protected function beforePersist($entity): void
    {
        // Common logic before persisting entities
        // Override in concrete controllers for specific behavior
    }

    /**
     * Helper method to handle common pre-update operations
     * Can be overridden by concrete controllers
     */
    protected function beforeUpdate($entity): void
    {
        // Common logic before updating entities
        // Override in concrete controllers for specific behavior
    }

    /**
     * Helper method to handle common pre-delete operations
     * Can be overridden by concrete controllers
     */
    protected function beforeDelete($entity): void
    {
        // Common logic before deleting entities
        // Override in concrete controllers for specific behavior
    }
}
