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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm()->hideOnIndex(),
            TextField::new('name'),
            TextField::new('nameExtension')->setLabel($this->translator->trans('Description')),
            TextField::new('countryCode')->setLabel($this->translator->trans('Country Code')),
            TextField::new('url')->setLabel($this->translator->trans('Website')),
            AssociationField::new('employees')
                ->setLabel($this->translator->trans('Employees'))
                ->setRequired(false)
                ->setFormTypeOptions([
                    'by_reference' => false,
                    'multiple' => true,
                    'class' => User::class,
                    'choice_label' => function (User $user) {
                        return $user->getEmail();
                    },
                ])
                ->onlyOnForms(),
        ];

        // Add active field for index page
        if ($pageName === Crud::PAGE_INDEX) {
            $fields = $this->addActiveField($fields, $pageName);
        }

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
