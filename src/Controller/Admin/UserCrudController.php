<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Doctrine\ORM\EntityManagerInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        EntityManagerInterface $entityManager, 
        \App\Repository\UserModulePermissionRepository $permissionRepository
    ) {
        parent::__construct($entityManager, $permissionRepository);
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    protected function getModuleName(): string
    {
        return 'Benutzer';
    }

    protected function hasPermissionManagement(): bool
    {
        return true;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setPageTitle('edit', 'Edit User')
            ->overrideTemplate('crud/edit', 'admin/user_edit.html.twig');
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm(),
        ];

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

            // Add permission tab (handled by abstract controller)
            $fields = $this->addPermissionTabToFields($fields);
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
            
            // Add permission summary (handled by abstract controller)
            $fields = $this->addPermissionSummaryField($fields);
        }

        return $fields;
    }
}
