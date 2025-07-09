<?php

namespace App\Service;

use App\Entity\Module;
use App\Entity\User;
use App\Entity\UserModulePermission;
use App\Repository\UserModulePermissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Symfony\Contracts\Translation\TranslatorInterface;

class PermissionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserModulePermissionRepository $permissionRepository,
        private TranslatorInterface $translator
    ) {
    }

    /**
     * Get the module entity by code
     */
    public function getModuleByCode(string $moduleCode): Module
    {
        $module = $this->entityManager->getRepository(Module::class)
            ->findOneBy(['code' => $moduleCode]);
            
        if (!$module) {
            throw new \RuntimeException(
                sprintf('Module with code "%s" not found. Please run fixtures.', $moduleCode)
            );
        }
        
        return $module;
    }

    /**
     * Create module permission fields for entities that support permission management
     */
    public function createModulePermissionFields(AdminContext $context): array
    {
        $modules = $this->entityManager->getRepository(Module::class)->findAll();
        
        // Get the current entity to populate existing values
        $entity = $context->getEntity()->getInstance();
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
        $fields[] = FormField::addFieldset($tableHtml)
            ->setFormTypeOption('label_html', true);
        
        return $fields;
    }

    /**
     * Add permission tab to form fields for entities that support permission management
     */
    public function addPermissionTabToFields(array $fields, AdminContext $context): array
    {
        $fields[] = FormField::addTab($this->translator->trans('Module Permissions'));
        
        $permissionFields = $this->createModulePermissionFields($context);
        return array_merge($fields, $permissionFields);
    }

    /**
     * Add permission summary field for index pages
     */
    public function addPermissionSummaryField(array $fields): array
    {
        $fields[] = AssociationField::new('modulePermissions')
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
    public function handleModulePermissions($entity, AdminContext $context): void
    {
        if (!method_exists($entity, 'getModulePermissions')) {
            return;
        }

        $request = $context->getRequest();
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
                    $permission = new UserModulePermission();
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
     * Persist entity with permissions handling
     */
    public function persistEntityWithPermissions(callable $parentPersist, $entityInstance, AdminContext $context): void
    {
        // Start a transaction to ensure atomicity
        $this->entityManager->beginTransaction();
        
        try {
            // First persist the main entity
            $parentPersist($entityInstance);
            
            // Then handle the permissions after the main entity is persisted
            $this->handleModulePermissions($entityInstance, $context);
            
            // Commit the transaction
            $this->entityManager->commit();
        } catch (\Exception $e) {
            // Rollback on error
            $this->entityManager->rollback();
            throw $e;
        }
    }

    /**
     * Update entity with permissions handling
     */
    public function updateEntityWithPermissions(callable $parentUpdate, $entityInstance, AdminContext $context): void
    {
        // Start a transaction to ensure atomicity
        $this->entityManager->beginTransaction();
        
        try {
            // Handle permissions first for updates
            $this->handleModulePermissions($entityInstance, $context);
            
            // Then update the main entity
            $parentUpdate($entityInstance);
            
            // Commit the transaction
            $this->entityManager->commit();
        } catch (\Exception $e) {
            // Rollback on error
            $this->entityManager->rollback();
            throw $e;
        }
    }
}
