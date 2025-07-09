<?php

namespace App\Controller\Admin;

use App\Entity\Company;
use App\Service\PermissionService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class CompanyCrudController extends AbstractCrudController
{
    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        PermissionService $permissionService
    ) {
        parent::__construct($entityManager, $translator, $permissionService);
    }

    public static function getEntityFqcn(): string
    {
        return Company::class;
    }

    protected function getModuleCode(): string
    {
        return 'Company';
    }

    protected function hasPermissionManagement(): bool
    {
        return false;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud);
    }

    #[IsGranted('read', subject: 'Company')]
    public function index(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Company = 'Company'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::index($context);
    }

    #[IsGranted('read', subject: 'Company')]
    public function detail(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Company = 'Company'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::detail($context);
    }

    #[IsGranted('write', subject: 'Company')]
    public function new(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Company = 'Company'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::new($context);
    }

    #[IsGranted('write', subject: 'Company')]
    public function edit(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Company = 'Company'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::edit($context);
    }

    #[IsGranted('write', subject: 'Company')]
    public function delete(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Company = 'Company'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::delete($context);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm()->hideOnIndex(),
            TextField::new('name'),
            TextField::new('nameExtension')->setLabel($this->translator->trans('Description')),
            TextField::new('countryCode')->setLabel($this->translator->trans('Country Code')),
            TextField::new('url')->setLabel($this->translator->trans('Website')),
            AssociationField::new('employees')->setLabel($this->translator->trans('Employees')),
        ];
    }
}
