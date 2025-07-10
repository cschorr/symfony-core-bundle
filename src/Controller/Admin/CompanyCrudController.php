<?php

namespace App\Controller\Admin;

use App\Entity\Company;
use App\Entity\User;
use App\Service\EasyAdminFieldService;
use App\Service\PermissionService;
use App\Service\DuplicateService;
use App\Service\RelationshipSyncService;
use App\Controller\Admin\Traits\FieldConfigurationTrait;
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
    use FieldConfigurationTrait;

    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        PermissionService $permissionService,
        DuplicateService $duplicateService,
        RequestStack $requestStack,
        private EasyAdminFieldService $fieldService,
        private RelationshipSyncService $relationshipSyncService
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
            $pageName
        );
    }

    /**
     * Define all field configurations for the Company entity using enhanced approach
     */
    private function getFieldConfigurations(): array
    {
        $fields = [];
        
        // Standard entity fields (ID, name, active)
        $fields = array_merge($fields, $this->getStandardEntityFields('Company'));
        
        // Additional company-specific fields
        $fields[] = $this->fieldService->createFieldConfig('nameExtension', 'text', ['detail', 'form'], 'Name Extension');
        $fields[] = $this->fieldService->createFieldConfig('companyGroup', 'association', ['detail', 'form'], 'Company Group');

        // Contact information (address + communication) - customized for index view
        $fields = array_merge($fields, $this->getCustomContactFieldGroups());

        // Employees using enhanced builder pattern
        $fields[] = $this->fieldService->createPanelConfig('employees_panel', 'Employees', ['detail', 'form'], 'fas fa-users');
        $fields[] = $this->getUserAssociationField('employees', 'Employees', ['index', 'detail', 'form'], true);

        return $fields;
    }

    /**
     * Get contact field groups customized for Company - excludes city from index view
     */
    private function getCustomContactFieldGroups(): array
    {
        $fields = [];
        
        // Communication fields (includes email and website in index)
        $fields = array_merge($fields, $this->fieldService->createCommunicationFieldGroup(['detail', 'form']));
        
        // Address fields - customized to exclude city from index
        $fields = array_merge($fields, [
            $this->fieldService->createPanelConfig('address_panel', 'Address Information', ['detail', 'form'], 'fas fa-map-marker-alt'),
            $this->fieldService->createFieldConfig('street', 'text', ['detail', 'form'], 'Street Address'),
            $this->fieldService->createFieldConfig('zip', 'text', ['detail', 'form'], 'ZIP/Postal Code'),
            $this->fieldService->createFieldConfig('city', 'text', ['detail', 'form'], 'City'), // Excluded from index
            $this->fieldService->createCountryFieldConfig('countryCode', ['index', 'detail', 'form'], 'Country'), // Flag-only in index
        ]);
        
        return $fields;
    }

    /**
     * Auto-sync relationships using the service
     */
    protected function autoSyncRelationships(object $entity): void
    {
        if ($entity instanceof Company) {
            $this->relationshipSyncService->autoSync($entity);
        }
    }

    /**
     * Override to use the new relationship sync service
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->beforePersist($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Override to use the new relationship sync service
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->beforeUpdate($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }
}
