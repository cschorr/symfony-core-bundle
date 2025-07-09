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
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserCrudController extends AbstractCrudController
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
        return User::class;
    }

    protected function getModuleCode(): string
    {
        return 'User';
    }

    protected function getModuleName(): string
    {
        return $this->translator->trans('User');
    }

    protected function hasPermissionManagement(): bool
    {
        return true;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setPageTitle('edit', $this->translator->trans('Edit User'));
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
            throw new AccessDeniedException($this->translator->trans('Access denied. You need read permission for the %module% module.', ['%module%' => $this->translator->trans('User')]));
        }
        return parent::index($context);
    }

    public function detail(AdminContext $context)
    {
        if (!$this->isGranted('read', $this->getModule())) {
            throw new AccessDeniedException($this->translator->trans('Access denied. You need read permission for the %module% module.', ['%module%' => $this->translator->trans('User')]));
        }
        return parent::detail($context);
    }

    public function new(AdminContext $context)
    {
        if (!$this->isGranted('write', $this->getModule())) {
            throw new AccessDeniedException($this->translator->trans('Access denied. You need write permission for the %module% module.', ['%module%' => $this->translator->trans('User')]));
        }
        return parent::new($context);
    }

    public function edit(AdminContext $context)
    {
        if (!$this->isGranted('write', $this->getModule())) {
            throw new AccessDeniedException($this->translator->trans('Access denied. You need write permission for the %module% module.', ['%module%' => $this->translator->trans('User')]));
        }
        return parent::edit($context);
    }

    public function delete(AdminContext $context)
    {
        if (!$this->isGranted('write', $this->getModule())) {
            throw new AccessDeniedException($this->translator->trans('Access denied. You need write permission for the %module% module.', ['%module%' => $this->translator->trans('User')]));
        }
        return parent::delete($context);
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm()->hideOnIndex(),
        ];

        if ($pageName === Crud::PAGE_EDIT || $pageName === Crud::PAGE_NEW) {
            // User Information Tab
            $fields[] = FormField::addTab($this->translator->trans('User Information'));
            $fields[] = EmailField::new('email');
            $fields[] = ChoiceField::new('roles')
                ->setLabel($this->translator->trans('Roles'))
                ->setChoices([
                    $this->translator->trans('User') => 'ROLE_USER',
                    $this->translator->trans('Admin') => 'ROLE_ADMIN',
                ])
                ->allowMultipleChoices()
                ->renderExpanded(false);
            $fields[] = BooleanField::new('active');
            $fields[] = TextareaField::new('notes');
            $fields[] = AssociationField::new('company');
            $fields[] = AssociationField::new('projects');

            // Add permission tab (handled by abstract controller)
            $fields = $this->addPermissionTabToFields($fields);
        } elseif ($pageName === Crud::PAGE_DETAIL) {
            // For detail page, show all fields including notes
            $fields[] = EmailField::new('email');
            $fields[] = ChoiceField::new('roles')
                ->setLabel($this->translator->trans('Roles'))
                ->setChoices([
                    $this->translator->trans('User') => 'ROLE_USER',
                    $this->translator->trans('Admin') => 'ROLE_ADMIN',
                ])
                ->allowMultipleChoices()
                ->renderExpanded(false);
            $fields[] = BooleanField::new('active');
            $fields[] = TextareaField::new('notes');
            $fields[] = AssociationField::new('company');
            $fields[] = AssociationField::new('projects');
            
            // Add permission summary (handled by abstract controller)
            $fields = $this->addPermissionSummaryField($fields);
        } else {
            // For index page, show all fields without tabs and hide notes
            $fields[] = EmailField::new('email');
            $fields[] = ChoiceField::new('roles')
                ->setLabel($this->translator->trans('Roles'))
                ->setChoices([
                    $this->translator->trans('User') => 'ROLE_USER',
                    $this->translator->trans('Admin') => 'ROLE_ADMIN',
                ])
                ->allowMultipleChoices()
                ->renderExpanded(false);
            $fields[] = BooleanField::new('active');
            #$fields[] = TextareaField::new('notes');
            $fields[] = AssociationField::new('company');
            
            // Add permission summary (handled by abstract controller)
            $fields = $this->addPermissionSummaryField($fields);
        }

        return $fields;
    }
}
