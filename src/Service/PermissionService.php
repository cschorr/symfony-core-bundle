<?php

namespace App\Service;

use App\Entity\SystemEntity;
use App\Entity\User;
use App\Entity\UserSystemEntityPermission;
use App\Repository\SystemEntityRepository;
use App\Repository\UserSystemEntityPermissionRepository;
use App\Service\EasyAdminFieldService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use Symfony\Contracts\Translation\TranslatorInterface;

class PermissionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SystemEntityRepository $systemEntityRepository,
        private UserSystemEntityPermissionRepository $userSystemEntityPermissionRepository,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * Get system entity by code
     */
    public function getSystemEntityByCode(string $code): ?SystemEntity
    {
        return $this->systemEntityRepository->findOneBy(['code' => $code]);
    }

    /**
     * Create system entity permission form fields organized in a tab
     * @return array
     */
    public function createSystemEntityPermissionFields(?User $entity = null): array
    {
        $permissionFields = [];
        $systemEntities = $this->systemEntityRepository->findBy(['active' => true], ['name' => 'ASC']);

        foreach ($systemEntities as $systemEntity) {
            // Get existing permission for this system entity if entity is provided
            $permission = null;
            if ($entity && $entity->getId()) {
                $permission = $this->userSystemEntityPermissionRepository->findOneBy([
                    'user' => $entity,
                    'systemEntity' => $systemEntity
                ]);
            }

            // Create read permission field
            $readFieldName = sprintf('systemEntity_%s_read', $systemEntity->getId()->toString());
            $readField = BooleanField::new($readFieldName)
                ->setLabel(sprintf('%s (%s)', $this->translator->trans($systemEntity->getName()), $this->translator->trans('Read')))
                ->setFormTypeOption('attr', ['data-system-entity-id' => $systemEntity->getId()->toString()])
                ->setFormTypeOption('attr', ['data-permission-type' => 'read'])
                ->setFormTypeOption('required', false)
                ->setFormTypeOption('mapped', false); // Don't map to entity property

            if ($permission) {
                $readField->setFormTypeOption('data', $permission->canRead());
            }

            // Create write permission field
            $writeFieldName = sprintf('systemEntity_%s_write', $systemEntity->getId()->toString());
            $writeField = BooleanField::new($writeFieldName)
                ->setLabel(sprintf('%s (%s)', $this->translator->trans($systemEntity->getName()), $this->translator->trans('Write')))
                ->setFormTypeOption('attr', ['data-system-entity-id' => $systemEntity->getId()->toString()])
                ->setFormTypeOption('attr', ['data-permission-type' => 'write'])
                ->setFormTypeOption('required', false)
                ->setFormTypeOption('mapped', false); // Don't map to entity property

            if ($permission) {
                $writeField->setFormTypeOption('data', $permission->canWrite());
            }

            $permissionFields[] = $readField;
            $permissionFields[] = $writeField;
        }

        return $permissionFields;
    }

    /**
     * Add permission tab to form fields for entities that support permission management
     */
    public function addPermissionTabToFields(array $fields, ?User $entity = null): array
    {
        $permissionFields = $this->createSystemEntityPermissionFields($entity);
        
        if (empty($permissionFields)) {
            return $fields;
        }

        // Create a tab for permissions using FormField::addTab
        $permissionTab = FormField::addTab($this->translator->trans('SystemEntity Permissions'), 'fas fa-shield-alt');

        // Add the tab first, then the permission fields
        $fields[] = $permissionTab;
        $fields = array_merge($fields, $permissionFields);

        return $fields;
    }

    /**
     * Handle system entity permissions when saving user
     */
    public function handleSystemEntityPermissions(User $user, array $formData): void
    {
        // Get all active system entities
        $systemEntities = $this->systemEntityRepository->findBy(['active' => true]);

        foreach ($systemEntities as $systemEntity) {
            $systemEntityId = $systemEntity->getId()->toString();
            
            // Check form data for this system entity's permissions
            $hasReadPermission = false;
            $hasWritePermission = false;
            
            foreach ($formData as $key => $value) {
                if (is_array($value) && 
                    isset($value['data-system-entity-id']) && 
                    $value['data-system-entity-id'] === $systemEntityId) {
                    
                    if (isset($value['data-permission-type'])) {
                        if ($value['data-permission-type'] === 'read') {
                            $hasReadPermission = (bool) $value;
                        } elseif ($value['data-permission-type'] === 'write') {
                            $hasWritePermission = (bool) $value;
                        }
                    }
                }
            }

            // Find existing permission
            $permission = $this->userSystemEntityPermissionRepository->findOneBy([
                'user' => $user,
                'systemEntity' => $systemEntity
            ]);

            // Create or update permission
            if ($hasReadPermission || $hasWritePermission) {
                if (!$permission) {
                    $permission = new UserSystemEntityPermission();
                    $permission->setUser($user);
                    $permission->setSystemEntity($systemEntity);
                }
                
                $permission->setCanRead($hasReadPermission);
                $permission->setCanWrite($hasWritePermission);
                
                $this->entityManager->persist($permission);
            } elseif ($permission) {
                // Remove permission if both read and write are false
                $this->entityManager->remove($permission);
            }
        }
    }

    /**
     * Check if user can read system entity
     */
    public function canUserReadSystemEntity(User $user, SystemEntity $systemEntity): bool
    {
        $permission = $this->userSystemEntityPermissionRepository->findOneBy([
            'user' => $user,
            'systemEntity' => $systemEntity
        ]);

        return $permission && $permission->canRead();
    }

    /**
     * Check if user can write system entity  
     */
    public function canUserWriteSystemEntity(User $user, SystemEntity $systemEntity): bool
    {
        $permission = $this->userSystemEntityPermissionRepository->findOneBy([
            'user' => $user,
            'systemEntity' => $systemEntity
        ]);

        return $permission && $permission->canWrite();
    }

    /**
     * Add permission tab to fields for SystemEntity (shows user permissions for this system entity)
     */
    public function addSystemEntityPermissionTabToFields(array $fields): array
    {
        // Get all users to create permission fields
        $users = $this->entityManager->getRepository(User::class)->findAll();
        
        // Create tab for permissions
        $permissionFields = [
            FormField::addTab($this->translator->trans('User Permissions'))
                ->setHelp($this->translator->trans('Manage user permissions for this system entity'))
                ->collapsible()
        ];
        
        foreach ($users as $user) {
            // Create read permission field
            $readFieldName = 'userPermission_read_' . $user->getId();
            $userReadField = BooleanField::new($readFieldName)
                ->setLabel($user->getEmail() . ' - ' . $this->translator->trans('Can Read'))
                ->setFormTypeOption('attr', ['data-user-id' => $user->getId()->toString()])
                ->setFormTypeOption('attr', ['data-permission-type' => 'read'])
                ->setFormTypeOption('required', false)
                ->setFormTypeOption('mapped', false); // Don't map to entity property
                
            // Create write permission field  
            $writeFieldName = 'userPermission_write_' . $user->getId();
            $userWriteField = BooleanField::new($writeFieldName)
                ->setLabel($user->getEmail() . ' - ' . $this->translator->trans('Can Write'))
                ->setFormTypeOption('attr', ['data-user-id' => $user->getId()->toString()])
                ->setFormTypeOption('attr', ['data-permission-type' => 'write'])
                ->setFormTypeOption('required', false)
                ->setFormTypeOption('mapped', false); // Don't map to entity property
                
            $permissionFields[] = $userReadField;
            $permissionFields[] = $userWriteField;
        }
        
        // Add permission fields to main fields array
        return array_merge($fields, $permissionFields);
    }

    /**
     * Create SystemEntity permission fields with proper data binding
     */
    public function addSystemEntityPermissionTabToFieldsWithEntity(array $fields, ?SystemEntity $entity = null): array
    {
        // Get all users to create permission fields
        $users = $this->entityManager->getRepository(User::class)->findAll();
        
        // Create tab for permissions
        $permissionFields = [
            FormField::addTab($this->translator->trans('User Permissions'))
                ->setHelp($this->translator->trans('Manage user permissions for this system entity'))
                ->collapsible()
        ];
        
        foreach ($users as $user) {
            // Find existing permission
            $permission = null;
            if ($entity) {
                $permission = $this->userSystemEntityPermissionRepository
                    ->findOneBy([
                        'user' => $user,
                        'systemEntity' => $entity
                    ]);
            }
            
            // Create read permission field
            $readFieldName = 'userPermission_read_' . $user->getId();
            $userReadField = BooleanField::new($readFieldName)
                ->setLabel($user->getEmail() . ' (' . $this->translator->trans('Read') . ')')
                ->setFormTypeOption('attr', ['data-user-id' => $user->getId()->toString()])
                ->setFormTypeOption('attr', ['data-permission-type' => 'read'])
                ->setFormTypeOption('required', false)
                ->setFormTypeOption('mapped', false); // Don't map to entity property
                
            if ($permission) {
                $userReadField->setFormTypeOption('data', $permission->canRead());
            }
                
            // Create write permission field  
            $writeFieldName = 'userPermission_write_' . $user->getId();
            $userWriteField = BooleanField::new($writeFieldName)
                ->setLabel($user->getEmail() . ' (' . $this->translator->trans('Write') . ')')
                ->setFormTypeOption('attr', ['data-user-id' => $user->getId()->toString()])
                ->setFormTypeOption('attr', ['data-permission-type' => 'write'])
                ->setFormTypeOption('required', false)
                ->setFormTypeOption('mapped', false); // Don't map to entity property
                
            if ($permission) {
                $userWriteField->setFormTypeOption('data', $permission->canWrite());
            }
                
            $permissionFields[] = $userReadField;
            $permissionFields[] = $userWriteField;
        }
        
        // Add permission fields to main fields array
        return array_merge($fields, $permissionFields);
    }

    /**
     * Add permission summary field for index pages
     * @param array $fields
     * @return array
     */
    public function addPermissionSummaryField(array $fields): array
    {
        // Add an association field that shows permission summary with count
        $permissionSummaryField = AssociationField::new('systemEntityPermissions')
            ->setLabel($this->translator->trans('System Permissions'))
            ->setHelp($this->translator->trans('Count of system entity permissions for this user'))
            ->onlyOnIndex()
            ->formatValue(function ($value, $entity) {
                if (!$entity instanceof User) {
                    return $this->translator->trans('No User');
                }
                
                $permissions = $entity->getSystemEntityPermissions();
                
                if ($permissions->isEmpty()) {
                    return $this->translator->trans('No permissions assigned');
                }
                
                $count = $permissions->count();
                return sprintf($this->translator->trans('%d permission(s) assigned'), $count);
            });
            
        $fields[] = $permissionSummaryField;
        
        return $fields;
    }
}
