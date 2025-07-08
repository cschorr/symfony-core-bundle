<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Module;
use App\Entity\UserModulePermission;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;

class UserCrudController extends AbstractCrudController
{
    private EntityManagerInterface $entityManager;
    private Environment $twig;

    public function __construct(EntityManagerInterface $entityManager, Environment $twig)
    {
        $this->entityManager = $entityManager;
        $this->twig = $twig;
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('edit', 'Edit User')
            ->overrideTemplate('crud/edit', 'admin/user_edit.html.twig');
    }

    private function getUserModule(): Module
    {
        $module = $this->entityManager->getRepository(Module::class)->findOneBy(['name' => 'Benutzer']);
        if (!$module) {
            throw new \RuntimeException('Module "Benutzer" not found. Please run fixtures.');
        }
        return $module;
    }

    public function configureFields(string $pageName): iterable
    {
        // Check if user has read access to Benutzer module
        $this->denyAccessUnlessGranted('read', $this->getUserModule());
        
        $fields = [
            IdField::new('id')->hideOnForm(),
        ];

        // Add module permissions section for edit/new pages
        if ($pageName === Crud::PAGE_EDIT || $pageName === Crud::PAGE_NEW) {
            // User Information Tab
            $fields[] = FormField::addTab('User Information');
            $fields[] = EmailField::new('email');
            $fields[] = ChoiceField::new('roles')
                ->setChoices([
                    'User' => 'ROLE_USER',
                    'Admin' => 'ROLE_ADMIN',
                ])
                ->allowMultipleChoices()
                ->renderExpanded(false);
            $fields[] = BooleanField::new('active');
            $fields[] = TextareaField::new('notes');
            $fields[] = AssociationField::new('company');
            $fields[] = AssociationField::new('projects');

            // Module Permissions Tab
            $fields[] = FormField::addTab('Module Permissions');
            $fields[] = FormField::addFieldset('Select permissions for each module:');
            $permissionFields = $this->createModulePermissionsField($pageName);
            $fields = array_merge($fields, $permissionFields);
        } else {
            // For index page, show all fields without tabs
            $fields[] = EmailField::new('email');
            $fields[] = ChoiceField::new('roles')
                ->setChoices([
                    'User' => 'ROLE_USER',
                    'Admin' => 'ROLE_ADMIN',
                ])
                ->allowMultipleChoices()
                ->renderExpanded(false);
            $fields[] = BooleanField::new('active');
            $fields[] = TextareaField::new('notes');
            $fields[] = AssociationField::new('company');
            $fields[] = AssociationField::new('projects')->hideOnForm();
            
            // For index page, show a summary
            $fields[] = AssociationField::new('modulePermissions')
                ->setLabel('Module Permissions')
                ->formatValue(function ($value, $entity) {
                    if (!$entity || !$entity->getModulePermissions() || $entity->getModulePermissions()->isEmpty()) {
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
        }

        return $fields;
    }

    private function createModulePermissionsField(string $pageName)
    {
        $modules = $this->entityManager->getRepository(Module::class)->findAll();
        $fields = [];
        
        foreach ($modules as $module) {
            // Read permission field
            $readField = BooleanField::new('module_' . $module->getId() . '_read')
                ->setLabel($module->getName() . ' - Read')
                ->setFormTypeOption('mapped', false);
                
            // Write permission field
            $writeField = BooleanField::new('module_' . $module->getId() . '_write')
                ->setLabel($module->getName() . ' - Write')
                ->setFormTypeOption('mapped', false);
                
            $fields[] = $readField;
            $fields[] = $writeField;
        }
        
        return $fields;
    }

    public function edit(AdminContext $context)
    {
        $response = parent::edit($context);
        
        // If it's a GET request, modify the form to set the initial values
        if ($context->getRequest()->isMethod('GET')) {
            $entity = $context->getEntity()->getInstance();
            if ($entity instanceof User) {
                // Get the entity manager to refresh the entity and ensure permissions are loaded
                $this->entityManager->refresh($entity);
                
                // Set the initial form values using JavaScript
                $this->addPermissionInitializationScript($entity);
            }
        }
        
        return $response;
    }

    private function addPermissionInitializationScript(User $user): void
    {
        $modules = $this->entityManager->getRepository(Module::class)->findAll();
        $permissions = [];
        
        foreach ($modules as $module) {
            $canRead = false;
            $canWrite = false;
            
            foreach ($user->getModulePermissions() as $permission) {
                if ($permission->getModule() && $permission->getModule()->getId() === $module->getId()) {
                    $canRead = $permission->canRead();
                    $canWrite = $permission->canWrite();
                    break;
                }
            }
            
            $permissions['module_' . $module->getId() . '_read'] = $canRead;
            $permissions['module_' . $module->getId() . '_write'] = $canWrite;
        }
        
        // Store the permissions in Twig globals so they can be accessed in the template
        $this->twig->addGlobal('user_permissions_data', $permissions);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->denyAccessUnlessGranted('write', $this->getUserModule());
        $this->handleModulePermissions($entityManager, $entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->denyAccessUnlessGranted('write', $this->getUserModule());
        $this->handleModulePermissions($entityManager, $entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->denyAccessUnlessGranted('delete', $this->getUserModule());
        parent::deleteEntity($entityManager, $entityInstance);
    }

    private function handleModulePermissions(EntityManagerInterface $entityManager, User $user): void
    {
        $request = $this->getContext()->getRequest();
        $formData = $request->request->all()['User'] ?? [];

        // Clear existing permissions
        foreach ($user->getModulePermissions() as $permission) {
            $entityManager->remove($permission);
        }
        $user->getModulePermissions()->clear();

        // Get all modules
        $modules = $entityManager->getRepository(Module::class)->findAll();

        // Process each module's permissions
        foreach ($modules as $module) {
            $readKey = 'module_' . $module->getId() . '_read';
            $writeKey = 'module_' . $module->getId() . '_write';
            
            $canRead = isset($formData[$readKey]) && $formData[$readKey] === '1';
            $canWrite = isset($formData[$writeKey]) && $formData[$writeKey] === '1';

            // Only create permission if at least one permission is granted
            if ($canRead || $canWrite) {
                $permission = new UserModulePermission();
                $permission->setUser($user);
                $permission->setModule($module);
                $permission->setCanRead($canRead);
                $permission->setCanWrite($canWrite);
                $permission->setCreatedAt(new \DateTime());
                $permission->setUpdatedAt(new \DateTime());
                
                $entityManager->persist($permission);
                $user->addModulePermission($permission);
            }
        }
    }
}
