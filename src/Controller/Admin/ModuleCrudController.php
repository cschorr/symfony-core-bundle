<?php

namespace App\Controller\Admin;

use App\Entity\Module;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ModuleCrudController extends AbstractCrudController
{
    public function __construct(
        EntityManagerInterface $entityManager,
        \App\Repository\UserModulePermissionRepository $permissionRepository
    ) {
        parent::__construct($entityManager, $permissionRepository);
    }

    public static function getEntityFqcn(): string
    {
        return Module::class;
    }

    protected function getModuleCode(): string
    {
        return 'Module';
    }

    protected function getModuleName(): string
    {
        return 'Module';
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setPageTitle('index', 'System Modules')
            ->setPageTitle('detail', fn ($entity) => sprintf('Module: %s', $entity->getName()))
            ->setPageTitle('new', 'Create System Module')
            ->setHelp('index', 'Manage system modules and their permissions.');
    }

    public function configureActions(Actions $actions): Actions
    {
        // Start with parent configuration
        $actions = parent::configureActions($actions);
        
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

    public function index(AdminContext $context)
    {
        if (!$this->isGranted('read', $this->getModule())) {
            throw new AccessDeniedException('Access denied. You need read permission for the Module module.');
        }
        return parent::index($context);
    }

    public function detail(AdminContext $context)
    {
        if (!$this->isGranted('read', $this->getModule())) {
            throw new AccessDeniedException('Access denied. You need read permission for the Module module.');
        }
        return parent::detail($context);
    }

    public function new(AdminContext $context)
    {
        if (!$this->isGranted('write', $this->getModule())) {
            throw new AccessDeniedException('Access denied. You need write permission for the Module module.');
        }
        return parent::new($context);
    }

    public function edit(AdminContext $context)
    {
        if (!$this->isGranted('write', $this->getModule())) {
            throw new AccessDeniedException('Access denied. You need write permission for the Module module.');
        }
        return parent::edit($context);
    }

    public function delete(AdminContext $context)
    {
        if (!$this->isGranted('write', $this->getModule())) {
            throw new AccessDeniedException('Access denied. You need write permission for the Module module.');
        }
        return parent::delete($context);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm()->hideOnIndex(),
            TextField::new('name')
                ->setHelp('Display name for the module'),
            TextField::new('code')
                ->setHelp('Unique code that matches the entity name (e.g., User, Company, Module)')
                ->setFormTypeOption('attr', ['placeholder' => 'e.g., User, Company, Module']),
            TextareaField::new('text')
                ->setLabel('Description')
                ->setHelp('Optional description of what this module manages'),
            AssociationField::new('userPermissions')
                ->setLabel('User Permissions')
                ->hideOnForm()
                ->formatValue(function ($value, $entity) {
                    if (!$entity || !$entity->getUserPermissions() || $entity->getUserPermissions()->isEmpty()) {
                        return 'No permissions assigned';
                    }
                    
                    $count = $entity->getUserPermissions()->count();
                    return sprintf('%d permission(s) assigned', $count);
                }),
            AssociationField::new('userPermissions', 'Permission Details')
                ->hideOnForm()
                ->hideOnIndex()
                ->formatValue(function ($value, $entity) {
                    if (!$entity || !$entity->getUserPermissions() || $entity->getUserPermissions()->isEmpty()) {
                        return 'No permissions assigned';
                    }
                    
                    $permissions = [];
                    foreach ($entity->getUserPermissions() as $permission) {
                        $user = $permission->getUser();
                        $userEmail = $user ? $user->getEmail() : 'Unknown User';
                        $access = [];
                        if ($permission->canRead()) $access[] = 'Read';
                        if ($permission->canWrite()) $access[] = 'Write';
                        $accessString = implode(', ', $access);
                        
                        $permissions[] = sprintf('%s: %s', $userEmail, $accessString);
                    }
                    
                    return implode('<br>', $permissions);
                })
                ->renderAsHtml(),
        ];
    }

    protected function canCreateEntity(): bool
    {
        return $this->isAdmin();
    }

    protected function canEditEntity($entity): bool
    {
        return $this->isAdmin();
    }

    protected function canDeleteEntity($entity): bool
    {
        return $this->isAdmin();
    }
}
