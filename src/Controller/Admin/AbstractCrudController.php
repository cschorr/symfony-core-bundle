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
     * Concrete controllers should override this to add permission checks using isGranted()
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions;
    }

    /**
     * Base CRUD methods - concrete controllers should override and add permission checks
     */
    public function index(AdminContext $context)
    {
        return parent::index($context);
    }

    public function detail(AdminContext $context)
    {
        return parent::detail($context);
    }

    public function new(AdminContext $context)
    {
        return parent::new($context);
    }

    public function edit(AdminContext $context)
    {
        return parent::edit($context);
    }

    public function delete(AdminContext $context)
    {
        return parent::delete($context);
    }

    public function batchDelete(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
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
     * Check if this controller should show the user permission management UI
     * This controls whether permission checkboxes and tabs are shown in forms.
     * Override this method to return true for entities that should have permission management UI (like User).
     * Note: Permission checking for CRUD operations is handled by individual controllers using isGranted().
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

        $modules = $this->entityManager->getRepository(Module::class)->findAll();
        $fields = [];
        
        // Get the current entity to populate existing values
        $entity = $this->getContext()->getEntity()->getInstance();
        $existingPermissions = [];
        
        if ($entity && method_exists($entity, 'getModulePermissions')) {
            $permissions = $entity->getModulePermissions();
            
            foreach ($permissions as $permission) {
                $moduleId = (string) $permission->getModule()->getId();
                $existingPermissions[$moduleId] = [
                    'read' => $permission->canRead(),
                    'write' => $permission->canWrite()
                ];
            }
        }
        
        foreach ($modules as $module) {
            $moduleId = (string) $module->getId();
            $hasReadPermission = isset($existingPermissions[$moduleId]) && $existingPermissions[$moduleId]['read'];
            $hasWritePermission = isset($existingPermissions[$moduleId]) && $existingPermissions[$moduleId]['write'];
            
            // Read permission field
            $readField = \EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField::new('module_' . $moduleId . '_read')
                ->setLabel($module->getName() . ' - Read')
                ->setFormTypeOption('mapped', false)
                ->setFormTypeOption('data', $hasReadPermission);
                
            // Write permission field
            $writeField = \EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField::new('module_' . $moduleId . '_write')
                ->setLabel($module->getName() . ' - Write')
                ->setFormTypeOption('mapped', false)
                ->setFormTypeOption('data', $hasWritePermission);
                
            $fields[] = $readField;
            $fields[] = $writeField;
        }
        
        return $fields;
    }

    /**
     * Add permission tab to form fields for entities that support permission management
     */
    protected function addPermissionTabToFields(array $fields): array
    {
        if (!$this->hasPermissionManagement()) {
            return $fields;
        }

        $fields[] = \EasyCorp\Bundle\EasyAdminBundle\Field\FormField::addTab('Module Permissions');
        $fields[] = \EasyCorp\Bundle\EasyAdminBundle\Field\FormField::addFieldset('Select permissions for each module:');
        
        $permissionFields = $this->createModulePermissionFields();
        return array_merge($fields, $permissionFields);
    }

    /**
     * Add permission summary field for index pages
     */
    protected function addPermissionSummaryField(array $fields): array
    {
        if (!$this->hasPermissionManagement()) {
            return $fields;
        }

        $fields[] = \EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField::new('modulePermissions')
            ->setLabel('Module Permissions')
            ->formatValue(function ($value, $entity) {
                if (!$entity || !method_exists($entity, 'getModulePermissions') || 
                    !$entity->getModulePermissions() || $entity->getModulePermissions()->isEmpty()) {
                    return 'No permissions';
                }
                
                $permissions = [];
                foreach ($entity->getModulePermissions() as $permission) {
                    $module = $permission->getModule() ? $permission->getModule()->getName() : 'Unknown';
                    $access = [];
                    if ($permission->canRead()) $access[] = 'R';
                    if ($permission->canWrite()) $access[] = 'W';
                    $permissions[] = $module . ' (' . implode(',', $access) . ')';
                }
                
                return implode(', ', $permissions);
            });

        return $fields;
    }

    /**
     * Handle module permissions for entities that support it
     */
    protected function handleModulePermissions($entity): void
    {
        if (!$this->hasPermissionManagement() || !method_exists($entity, 'getModulePermissions')) {
            return;
        }

        $request = $this->getContext()->getRequest();
        $entityName = (new \ReflectionClass($entity))->getShortName();
        $formData = $request->request->all()[$entityName] ?? [];

        // Clear existing permissions
        foreach ($entity->getModulePermissions() as $permission) {
            $this->entityManager->remove($permission);
        }
        $entity->getModulePermissions()->clear();

        // Get all modules
        $modules = $this->entityManager->getRepository(Module::class)->findAll();

        // Process each module's permissions
        foreach ($modules as $module) {
            $readKey = 'module_' . (string) $module->getId() . '_read';
            $writeKey = 'module_' . (string) $module->getId() . '_write';
            
            $canRead = isset($formData[$readKey]) && $formData[$readKey] === '1';
            $canWrite = isset($formData[$writeKey]) && $formData[$writeKey] === '1';

            // Only create permission if at least one permission is granted
            if ($canRead || $canWrite) {
                $permission = new \App\Entity\UserModulePermission();
                $permission->setUser($entity);
                $permission->setModule($module);
                $permission->setCanRead($canRead);
                $permission->setCanWrite($canWrite);
                $permission->setCreatedAt(new \DateTime());
                $permission->setUpdatedAt(new \DateTime());
                
                $this->entityManager->persist($permission);
                $entity->addModulePermission($permission);
            }
        }
    }

    /**
     * Override to handle permissions on entity creation
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->handleModulePermissions($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Override to handle permissions on entity update
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->handleModulePermissions($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }
}
