<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            EmailField::new('email'),
            ChoiceField::new('roles')
                ->setChoices([
                    'User' => 'ROLE_USER',
                    'Admin' => 'ROLE_ADMIN',
                ])
                ->allowMultipleChoices()
                ->renderExpanded(false),
            BooleanField::new('active'),
            TextareaField::new('notes'),
            AssociationField::new('company'),
            AssociationField::new('projects'),
            // Module permissions association
            AssociationField::new('modulePermissions')
                ->setLabel('Module Permissions')
                ->formatValue(function ($value, $entity) {
                    if (!$entity || !$entity->getModulePermissions()) {
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
                })
        ];
    }
}
