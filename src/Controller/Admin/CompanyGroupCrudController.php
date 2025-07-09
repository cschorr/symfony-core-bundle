<?php

namespace App\Controller\Admin;

use App\Entity\CompanyGroup;
use App\Service\PermissionService;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class CompanyGroupCrudController extends AbstractCrudController
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
        return CompanyGroup::class;
    }

    protected function getModuleCode(): string
    {
        return 'CompanyGroup';
    }

    protected function getModuleName(): string
    {
        return $this->translator->trans('CompanyGroup');
    }

    protected function hasPermissionManagement(): bool
    {
        return false;
    }

    #[IsGranted('read', subject: 'CompanyGroup')]
    public function index(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $CompanyGroup = 'CompanyGroup'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::index($context);
    }

    #[IsGranted('read', subject: 'CompanyGroup')]
    public function detail(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $CompanyGroup = 'CompanyGroup'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::detail($context);
    }

    #[IsGranted('write', subject: 'CompanyGroup')]
    public function new(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $CompanyGroup = 'CompanyGroup'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::new($context);
    }

    #[IsGranted('write', subject: 'CompanyGroup')]
    public function edit(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $CompanyGroup = 'CompanyGroup'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::edit($context);
    }

    #[IsGranted('write', subject: 'CompanyGroup')]
    public function delete(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $CompanyGroup = 'CompanyGroup'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::delete($context);
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
