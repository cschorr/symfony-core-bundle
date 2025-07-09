<?php

namespace App\Controller\Admin;

use App\Entity\Company;
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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class CompanyCrudController extends AbstractCrudController
{
    public function __construct(
        EntityManagerInterface $entityManager,
        \App\Repository\UserModulePermissionRepository $permissionRepository,
        TranslatorInterface $translator
    ) {
        parent::__construct($entityManager, $permissionRepository, $translator);
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
        return $this->translator->trans('Company');
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

    public function index(AdminContext $context): KeyValueStore|Response
    {
        if (!$this->isGranted('read', $this->getModule())) {
            throw new AccessDeniedException($this->translator->trans('Access denied. You need read permission for the %module% module.', ['%module%' => $this->translator->trans('Company')]));
        }
        return parent::index($context);
    }

    public function detail(AdminContext $context): KeyValueStore|Response
    {
        if (!$this->isGranted('read', $this->getModule())) {
            throw new AccessDeniedException($this->translator->trans('Access denied. You need read permission for the %module% module.', ['%module%' => $this->translator->trans('Company')]));
        }
        return parent::detail($context);
    }

    public function new(AdminContext $context): KeyValueStore|Response
    {
        if (!$this->isGranted('write', $this->getModule())) {
            throw new AccessDeniedException($this->translator->trans('Access denied. You need write permission for the %module% module.', ['%module%' => $this->translator->trans('Company')]));
        }
        return parent::new($context);
    }

    public function edit(AdminContext $context): KeyValueStore|Response
    {
        if (!$this->isGranted('write', $this->getModule())) {
            throw new AccessDeniedException($this->translator->trans('Access denied. You need write permission for the %module% module.', ['%module%' => $this->translator->trans('Company')]));
        }
        return parent::edit($context);
    }

    public function delete(AdminContext $context): KeyValueStore|Response
    {
        if (!$this->isGranted('write', $this->getModule())) {
            throw new AccessDeniedException($this->translator->trans('Access denied. You need write permission for the %module% module.', ['%module%' => $this->translator->trans('Company')]));
        }
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
