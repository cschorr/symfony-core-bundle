<?php

namespace App\Controller\Admin;

use App\Entity\Company;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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

    protected function getModuleCode(): string
    {
        return 'Company';
    }

    protected function getModuleName(): string
    {
        return 'Unternehmen';
    }

    protected function hasPermissionManagement(): bool
    {
        return false;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud);
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
            throw new AccessDeniedException('Access denied. You need read permission for the Company module.');
        }
        return parent::index($context);
    }

    public function detail(AdminContext $context)
    {
        if (!$this->isGranted('read', $this->getModule())) {
            throw new AccessDeniedException('Access denied. You need read permission for the Company module.');
        }
        return parent::detail($context);
    }

    public function new(AdminContext $context)
    {
        if (!$this->isGranted('write', $this->getModule())) {
            throw new AccessDeniedException('Access denied. You need write permission for the Company module.');
        }
        return parent::new($context);
    }

    public function edit(AdminContext $context)
    {
        if (!$this->isGranted('write', $this->getModule())) {
            throw new AccessDeniedException('Access denied. You need write permission for the Company module.');
        }
        return parent::edit($context);
    }

    public function delete(AdminContext $context)
    {
        if (!$this->isGranted('write', $this->getModule())) {
            throw new AccessDeniedException('Access denied. You need write permission for the Company module.');
        }
        return parent::delete($context);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm()->hideOnIndex(),
            TextField::new('name'),
            TextField::new('nameExtension')->setLabel('Description'),
            #AssociationField::new('companyGroup')->setLabel('Company Group'),
            #TextField::new('street'),
            #TextField::new('zip'),
            #TextField::new('city'),
            TextField::new('countryCode')->setLabel('Country Code'),
            #TextField::new('phone'),
            #TextField::new('cell')->setLabel('Mobile'),
            TextField::new('url')->setLabel('Website'),
            AssociationField::new('employees')->setLabel('Employees'),
            #AssociationField::new('projects'),
        ];
    }
}
