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

class ModuleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Module::class;
    }

    protected function getModuleName(): string
    {
        return 'Module'; // Use Module for managing modules themselves
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setPageTitle('index', 'System Modules')
            ->setPageTitle('new', 'Create System Module')
            ->setHelp('index', 'Manage system modules and their permissions.');
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);
        
        // Only admins should be able to create/edit/delete modules
        if (!$this->isAdmin()) {
            $actions
                ->disable(Action::NEW)
                ->disable(Action::EDIT)
                ->disable(Action::DELETE)
                ->disable(Action::BATCH_DELETE);
        }

        return $actions;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name')
                ->setHelp('Unique name for the module (used for permission checking)'),
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
