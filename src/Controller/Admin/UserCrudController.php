<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Company;
use App\Service\PermissionService;
use App\Service\DuplicateService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        PermissionService $permissionService,
        DuplicateService $duplicateService,
        RequestStack $requestStack
    ) {
        parent::__construct($entityManager, $translator, $permissionService, $duplicateService, $requestStack);
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    protected function getModuleCode(): string
    {
        return 'User';
    }

    protected function hasPermissionManagement(): bool
    {
        return true;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud);
    }

    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions);
    }

    #[IsGranted('read', subject: 'User')]
    public function index(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $User = 'User'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::index($context);
    }

    #[IsGranted('read', subject: 'User')]
    public function detail(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $User = 'User'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::detail($context);
    }

    #[IsGranted('write', subject: 'User')]
    public function edit(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $User = 'User'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        // Get the current user entity
        $user = $context->getEntity()->getInstance();
        
        // Refresh the entity from the database with all associations properly loaded
        if ($user->getId()) {
            $freshUser = $this->entityManager->getRepository(User::class)
                ->createQueryBuilder('u')
                ->leftJoin('u.company', 'c')
                ->addSelect('c')
                ->leftJoin('u.projects', 'p')
                ->addSelect('p')
                ->where('u.id = :id')
                ->setParameter('id', $user->getId())
                ->getQuery()
                ->getOneOrNullResult();
            
            if ($freshUser) {
                $context->getEntity()->setInstance($freshUser);
            }
        }
        
        return parent::edit($context);
    }

    #[IsGranted('write', subject: 'User')]
    public function delete(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $User = 'User'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
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
            $fields[] = AssociationField::new('company')
                ->autocomplete();
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
            $fields[] = AssociationField::new('company');
            
            // Add permission summary (handled by abstract controller)
            $fields = $this->addPermissionSummaryField($fields);
        }

        return $fields;
    }

    /**
     * Override to handle bidirectional relationship with Company
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var User $entityInstance */
        $this->syncCompanyRelationship($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Override to handle bidirectional relationship with Company
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var User $entityInstance */
        $this->syncCompanyRelationship($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * Sync the bidirectional relationship between User and Company
     */
    private function syncCompanyRelationship(User $user): void
    {
        // Get the current company
        $currentCompany = $user->getCompany();
        
        // If user has an ID (not a new user), handle previous company relationship
        if ($user->getId()) {
            // Find the original user to see what company they were previously assigned to
            $originalUser = $this->entityManager->getRepository(User::class)->find($user->getId());
            $previousCompany = $originalUser?->getCompany();
            
            // If the company has changed, remove user from previous company
            if ($previousCompany && $previousCompany !== $currentCompany) {
                $previousCompany->removeEmployee($user);
            }
        }
        
        // Add user to current company if set
        if ($currentCompany && !$currentCompany->getEmployees()->contains($user)) {
            $currentCompany->addEmployee($user);
        }
    }
}
