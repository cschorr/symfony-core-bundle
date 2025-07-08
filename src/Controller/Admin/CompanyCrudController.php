<?php

namespace App\Controller\Admin;

use App\Entity\Company;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Doctrine\ORM\EntityManagerInterface;

class CompanyCrudController extends AbstractCrudController
{
    public function __construct(
        EntityManagerInterface $entityManager,
        \App\Repository\UserModulePermissionRepository $permissionRepository
    ) {
        parent::__construct($entityManager, $permissionRepository);
    }

    public static function getEntityFqcn(): string
    {
        return Company::class;
    }

    protected function getModuleName(): string
    {
        return 'Unternehmen';
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            TextEditorField::new('description'),
            AssociationField::new('group'),
            AssociationField::new('addresses'),
            AssociationField::new('users'),
            AssociationField::new('projects'),
        ];
    }
}
