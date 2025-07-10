<?php

namespace App\Controller\Admin;

use App\Entity\Company;
use App\Entity\User;
use App\Service\EasyAdminFieldService;
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
        RequestStack $requestStack,
        private EasyAdminFieldService $fieldService
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
        return $this->fieldService->generateFields(
            $this->getFieldConfigurations(),
            $pageName,
            fn($fields, $pageName) => $this->addActiveField($fields, $pageName)
        );
    }

    /**
     * Define all field configurations for the Company entity
     */
    private function getFieldConfigurations(): array
    {
        return [
            // Basic fields
            $this->fieldService->createFieldConfig('id', 'id', [], null, [
                'hideOnForm' => true,
                'hideOnIndex' => true,
            ]),
            $this->fieldService->createFieldConfig('name', 'text', ['index', 'detail', 'form'], 'Name', [
                'required' => true,
            ]),
            $this->fieldService->createFieldConfig('nameExtension', 'text', ['detail', 'form'], 'Name Extension'),
            $this->fieldService->createFieldConfig('companyGroup', 'association', ['detail', 'form'], 'Company Group'),

            // Communication panel
            $this->fieldService->createPanelConfig('communication_panel', 'Communication', ['detail', 'form'], 'fas fa-phone'),
            $this->fieldService->createFieldConfig('email', 'email', ['index', 'detail', 'form'], 'Email Address', [
                'indexLabel' => 'Email',
                'panel' => 'communication',
            ]),
            $this->fieldService->createFieldConfig('phone', 'telephone', ['detail', 'form'], 'Phone Number', [
                'panel' => 'communication',
            ]),
            $this->fieldService->createFieldConfig('cell', 'telephone', ['detail', 'form'], 'Mobile/Cell Phone', [
                'panel' => 'communication',
            ]),
            $this->fieldService->createFieldConfig('url', 'url', ['index', 'detail', 'form'], 'Website', [
                'panel' => 'communication',
            ]),

            // Address panel
            $this->fieldService->createPanelConfig('address_panel', 'Address Information', ['detail', 'form'], 'fas fa-map-marker-alt'),
            $this->fieldService->createFieldConfig('street', 'text', ['detail', 'form'], 'Street Address', [
                'panel' => 'address',
            ]),
            $this->fieldService->createFieldConfig('zip', 'text', ['detail', 'form'], 'ZIP/Postal Code', [
                'panel' => 'address',
            ]),
            $this->fieldService->createFieldConfig('city', 'text', ['index', 'detail', 'form'], 'City', [
                'panel' => 'address',
            ]),
            $this->fieldService->createFieldConfig('countryCode', 'country', ['index', 'detail', 'form'], 'Country', [
                'panel' => 'address',
            ]),

            // Employees panel
            $this->fieldService->createPanelConfig('employees_panel', 'Employees', ['detail', 'form'], 'fas fa-users'),
            $this->fieldService->createFieldConfig('employees', 'association', ['index', 'detail', 'form'], 'Employees', [
                'panel' => 'employees',
                'multiple' => true,
                'indexFormat' => 'count',
                'countLabel' => 'Employees',
                'targetEntity' => User::class,
                'choiceLabel' => fn(User $user) => $user->getEmail(),
            ]),
        ];
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
