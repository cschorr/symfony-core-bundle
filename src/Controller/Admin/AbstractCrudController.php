<?php

namespace App\Controller\Admin;

use App\Entity\Module;
use App\Entity\User;
use App\Repository\UserModulePermissionRepository;
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
    protected UserModulePermissionRepository $permissionRepository;
    protected TranslatorInterface $translator;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserModulePermissionRepository $permissionRepository,
        TranslatorInterface $translator
    ) {
        $this->entityManager = $entityManager;
        $this->permissionRepository = $permissionRepository;
        $this->translator = $translator;
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
        $module = $this->entityManager->getRepository(Module::class)
            ->findOneBy(['code' => $this->getModuleCode()]);
            
        if (!$module) {
            throw new \RuntimeException(
                sprintf('Module with code "%s" not found. Please run fixtures.', $this->getModuleCode())
            );
        }
        
        return $module;
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

        $modules = $this->entityManager->getRepository(Module::class)->findAll();
        
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
        
        // Build the complete table HTML in one go
        $tableHtml = '
            <style>
                .permissions-table {
                    margin: 20px 0;
                }
                .permissions-table .form-check {
                    margin: 0;
                    display: flex;
                    justify-content: center;
                }
                .permissions-table .form-check-input {
                    margin: 0;
                }
                .permissions-table td {
                    vertical-align: middle;
                    padding: 12px 8px;
                }
                .permissions-table .module-name {
                    font-weight: 500;
                    color: #495057;
                }
            </style>
            <div class="permissions-table">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60%;">Module</th>
                            <th style="width: 20%; text-align: center;">Read</th>
                            <th style="width: 20%; text-align: center;">Write</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        // Add each module row
        foreach ($modules as $module) {
            $moduleId = (string) $module->getId();
            $hasReadPermission = isset($existingPermissions[$moduleId]) && $existingPermissions[$moduleId]['read'];
            $hasWritePermission = isset($existingPermissions[$moduleId]) && $existingPermissions[$moduleId]['write'];
            
            $readChecked = $hasReadPermission ? 'checked' : '';
            $writeChecked = $hasWritePermission ? 'checked' : '';
            
            $tableHtml .= '
                        <tr>
                            <td class="module-name">' . htmlspecialchars($module->getName()) . '</td>
                            <td class="text-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           name="User[module_' . $moduleId . '_read]" 
                                           id="module_' . $moduleId . '_read" 
                                           value="1" ' . $readChecked . '>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           name="User[module_' . $moduleId . '_write]" 
                                           id="module_' . $moduleId . '_write" 
                                           value="1" ' . $writeChecked . '>
                                </div>
                            </td>
                        </tr>';
        }
        
        $tableHtml .= '
                    </tbody>
                </table>
            </div>';
        
        // Create a single fieldset with all the HTML
        $fields[] = \EasyCorp\Bundle\EasyAdminBundle\Field\FormField::addFieldset($tableHtml)
            ->setFormTypeOption('label_html', true);
        
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

        $fields[] = \EasyCorp\Bundle\EasyAdminBundle\Field\FormField::addTab($this->translator->trans('Module Permissions'));
        
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
            ->setLabel($this->translator->trans('Module Permissions'))
            ->formatValue(function ($value, $entity) {
                if (!$entity || !method_exists($entity, 'getModulePermissions') || 
                    !$entity->getModulePermissions() || $entity->getModulePermissions()->isEmpty()) {
                    return '<div style="text-align: right;">' . $this->translator->trans('No permissions') . '</div>';
                }
                
                $count = $entity->getModulePermissions()->count();
                $permissionText = $this->translator->trans('{1} 1 permission assigned|]1,Inf[ %count% permissions assigned', ['%count%' => $count]);
                
                return '<div style="text-align: right;">' . $permissionText . '</div>';
            })
            ->renderAsHtml()
            ->addCssClass('text-end'); // Bootstrap class for right alignment

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

        // Get all modules
        $modules = $this->entityManager->getRepository(Module::class)->findAll();
        
        // Get existing permissions indexed by module ID
        $existingPermissions = [];
        foreach ($entity->getModulePermissions() as $permission) {
            $moduleId = (string) $permission->getModule()->getId();
            $existingPermissions[$moduleId] = $permission;
        }

        // Process each module's permissions
        foreach ($modules as $module) {
            $moduleId = (string) $module->getId();
            $readKey = 'module_' . $moduleId . '_read';
            $writeKey = 'module_' . $moduleId . '_write';
            
            $canRead = isset($formData[$readKey]) && $formData[$readKey] === '1';
            $canWrite = isset($formData[$writeKey]) && $formData[$writeKey] === '1';

            if (isset($existingPermissions[$moduleId])) {
                // Update existing permission
                $permission = $existingPermissions[$moduleId];
                
                if ($canRead || $canWrite) {
                    // Update the permission
                    $permission->setCanRead($canRead);
                    $permission->setCanWrite($canWrite);
                } else {
                    // Remove permission if no access is granted
                    $this->entityManager->remove($permission);
                    $entity->removeModulePermission($permission);
                }
            } else {
                // Create new permission only if at least one permission is granted
                if ($canRead || $canWrite) {
                    $permission = new \App\Entity\UserModulePermission();
                    $permission->setUser($entity);
                    $permission->setModule($module);
                    $permission->setCanRead($canRead);
                    $permission->setCanWrite($canWrite);
                    
                    $this->entityManager->persist($permission);
                    $entity->addModulePermission($permission);
                }
            }
        }
        
        // Don't flush here - let the parent method handle the flush
    }

    /**
     * Override to handle permissions on entity creation
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        // Start a transaction to ensure atomicity
        $this->entityManager->beginTransaction();
        
        try {
            // First persist the main entity
            parent::persistEntity($entityManager, $entityInstance);
            
            // Then handle the permissions after the main entity is persisted
            $this->handleModulePermissions($entityInstance);
            
            // Commit the transaction
            $this->entityManager->commit();
        } catch (\Exception $e) {
            // Rollback on error
            $this->entityManager->rollback();
            throw $e;
        }
    }

    /**
     * Override to handle permissions on entity update
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        // Start a transaction to ensure atomicity
        $this->entityManager->beginTransaction();
        
        try {
            // Handle permissions first for updates
            $this->handleModulePermissions($entityInstance);
            
            // Then update the main entity
            parent::updateEntity($entityManager, $entityInstance);
            
            // Commit the transaction
            $this->entityManager->commit();
        } catch (\Exception $e) {
            // Rollback on error
            $this->entityManager->rollback();
            throw $e;
        }
    }
}
