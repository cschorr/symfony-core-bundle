<?php

namespace App\Controller\Admin;

use App\Entity\Company;
use App\Entity\User;
use App\Service\PermissionService;
use App\Service\DuplicateService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Form\Type\AddressType;
use App\Form\Type\CommunicationType;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

class CompanyCrudController extends AbstractCrudController
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
    public function edit(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Company = 'Company'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::edit($context);
    }

    #[IsGranted('write', subject: 'Company')]
    public function delete(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Company = 'Company'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::delete($context);
    }
    
    /**
     * Check if a company can be deleted (no related records)
     */
    protected function canDeleteEntity($entity): bool
    {
        if (!$entity instanceof Company) {
            return true;
        }
        
        // Check if company has employees
        if ($entity->getEmployees()->count() > 0) {
            return false;
        }
        
        // Check if company has projects
        if ($entity->getProjects()->count() > 0) {
            return false;
        }
        
        return true;
    }

    public function configureFields(string $pageName): iterable
    {
        // Base fields for all pages
        $fields = [
            IdField::new('id')->hideOnForm()->hideOnIndex(),
            TextField::new('name')->setColumns(12),
            TextField::new('nameExtension')
                ->setLabel($this->translator->trans('Name Extension'))
                ->setColumns(12),
        ];

        // Page-specific field configuration
        switch ($pageName) {
            case Crud::PAGE_INDEX:
                $fields = array_merge($fields, $this->getIndexFields());
                break;
            
            case Crud::PAGE_DETAIL:
                $fields = array_merge($fields, $this->getDetailFields());
                break;
            
            case Crud::PAGE_NEW:
            case Crud::PAGE_EDIT:
                $fields = array_merge($fields, $this->getFormFields());
                break;
        }

        return $fields;
    }

    /**
     * Get fields specific to the index page
     */
    private function getIndexFields(): array
    {
        $fields = [];

        // Communication summary
        $fields[] = EmailField::new('email')
            ->setLabel($this->translator->trans('Email'));
        $fields[] = UrlField::new('url')
            ->setLabel($this->translator->trans('Website'));

        // Address summary
        $fields[] = TextField::new('city')
            ->setLabel($this->translator->trans('City'));
        $fields[] = CountryField::new('countryCode')
            ->setLabel($this->translator->trans('Country'));

        // Employees count
        $fields[] = AssociationField::new('employees')
            ->setLabel($this->translator->trans('Employees'))
            ->formatValue(function ($value, $entity) {
                if ($value instanceof Collection) {
                    return $value->count() . ' ' . $this->translator->trans('Employees');
                }
                return '0 ' . $this->translator->trans('Employees');
            });

        // Active status
        $fields = $this->addActiveField($fields, Crud::PAGE_INDEX);

        return $fields;
    }

    /**
     * Get fields specific to the detail page
     */
    private function getDetailFields(): array
    {
        $fields = [];

        // Company group
        $fields[] = AssociationField::new('companyGroup')
            ->setLabel($this->translator->trans('Company Group'));

        // Communication panel (always expanded on detail)
        $fields[] = FormField::addPanel($this->translator->trans('Communication'))
            ->setIcon('fas fa-phone')
            ->collapsible(false);

        $fields[] = EmailField::new('email')
            ->setLabel($this->translator->trans('Email Address'));
        $fields[] = TelephoneField::new('phone')
            ->setLabel($this->translator->trans('Phone Number'));
        $fields[] = TelephoneField::new('cell')
            ->setLabel($this->translator->trans('Mobile/Cell Phone'));
        $fields[] = UrlField::new('url')
            ->setLabel($this->translator->trans('Website'));

        // Address panel (always expanded on detail)
        $fields[] = FormField::addPanel($this->translator->trans('Address Information'))
            ->setIcon('fas fa-map-marker-alt')
            ->collapsible(false);

        $fields[] = TextField::new('street')
            ->setLabel($this->translator->trans('Street Address'));
        $fields[] = TextField::new('zip')
            ->setLabel($this->translator->trans('ZIP/Postal Code'));
        $fields[] = TextField::new('city')
            ->setLabel($this->translator->trans('City'));
        $fields[] = CountryField::new('countryCode')
            ->setLabel($this->translator->trans('Country'));

        // Employees
        $fields[] = FormField::addPanel($this->translator->trans('Employees'))
            ->setIcon('fas fa-users')
            ->collapsible(false);

        $fields[] = AssociationField::new('employees')
            ->setLabel($this->translator->trans('Employees'));

        return $fields;
    }

    /**
     * Get fields specific to form pages (new/edit)
     */
    private function getFormFields(): array
    {
        $fields = [];

        // Company group
        $fields[] = AssociationField::new('companyGroup')
            ->setLabel($this->translator->trans('Company Group'))
            ->setRequired(false)
            ->setColumns(12);

        // Communication panel (collapsible on forms)
        $fields[] = FormField::addPanel($this->translator->trans('Communication'))
            ->setIcon('fas fa-phone')
            ->collapsible(true);

        // Communication fields using individual fields instead of fieldset
        $fields[] = EmailField::new('email')
            ->setLabel($this->translator->trans('Email Address'))
            ->setRequired(false)
            ->setColumns(12);
        
        $fields[] = TelephoneField::new('phone')
            ->setLabel($this->translator->trans('Phone Number'))
            ->setRequired(false)
            ->setColumns(12);
            
        $fields[] = TelephoneField::new('cell')
            ->setLabel($this->translator->trans('Mobile/Cell Phone'))
            ->setRequired(false)
            ->setColumns(12);
            
        $fields[] = UrlField::new('url')
            ->setLabel($this->translator->trans('Website'))
            ->setRequired(false)
            ->setColumns(12);

        // Address panel (collapsible on forms)
        $fields[] = FormField::addPanel($this->translator->trans('Address Information'))
            ->setIcon('fas fa-map-marker-alt')
            ->collapsible(true);

        // Address fields using individual fields instead of fieldset
        $fields[] = TextField::new('street')
            ->setLabel($this->translator->trans('Street Address'))
            ->setRequired(false)
            ->setColumns(12);
            
        $fields[] = TextField::new('zip')
            ->setLabel($this->translator->trans('ZIP/Postal Code'))
            ->setRequired(false)
            ->setColumns(12);
            
        $fields[] = TextField::new('city')
            ->setLabel($this->translator->trans('City'))
            ->setRequired(false)
            ->setColumns(12);
            
        $fields[] = CountryField::new('countryCode')
            ->setLabel($this->translator->trans('Country'))
            ->setRequired(false)
            ->setColumns(12);

        // Employees panel (collapsible on forms)
        $fields[] = FormField::addPanel($this->translator->trans('Employees'))
            ->setIcon('fas fa-users')
            ->collapsible(true);

        $fields[] = AssociationField::new('employees')
            ->setLabel($this->translator->trans('Employees'))
            ->setRequired(false)
            ->setColumns(12)
            ->setFormTypeOptions([
                'by_reference' => false,
                'multiple' => true,
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getEmail();
                },
            ]);

        return $fields;
    }

    /**
     * Override to handle bidirectional employee relationship
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Company $entityInstance */
        $this->syncEmployeeRelationship($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Override to handle bidirectional employee relationship
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Company $entityInstance */
        $this->syncEmployeeRelationship($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * Sync the bidirectional relationship between Company and Users (employees)
     */
    private function syncEmployeeRelationship(Company $company): void
    {
        // Only sync if company has an ID (not for new companies)
        if ($company->getId()) {
            // Get all users that were previously assigned to this company
            $previousEmployees = $this->entityManager->getRepository(User::class)
                ->findBy(['company' => $company]);

            // Remove company reference from users no longer in the collection
            foreach ($previousEmployees as $user) {
                if (!$company->getEmployees()->contains($user)) {
                    $user->setCompany(null);
                }
            }
        }

        // Set company reference for all current employees
        foreach ($company->getEmployees() as $employee) {
            if ($employee->getCompany() !== $company) {
                $employee->setCompany($company);
            }
        }
    }
}
