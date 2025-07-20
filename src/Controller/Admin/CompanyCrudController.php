<?php

namespace App\Controller\Admin;

use App\Entity\Company;
use App\Entity\User;
use App\Controller\Admin\UserCrudController;
use App\Controller\Admin\ProjectCrudController;
use App\Service\EasyAdminFieldService;
use App\Service\PermissionService;
use App\Service\DuplicateService;
use App\Service\RelationshipSyncService;
use App\Service\EmbeddedTableService;
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
        private RelationshipSyncService $relationshipSyncService,
        private AdminUrlGenerator $adminUrlGenerator,
        private EmbeddedTableService $embeddedTableService
    ) {
        parent::__construct($entityManager, $translator, $permissionService, $duplicateService, $requestStack);
    }

    public static function getEntityFqcn(): string
    {
        return Company::class;
    }

    protected function getSystemEntityCode(): string
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
        
        // Fields for index page only (no tabs on index)
        $fields = array_merge($fields, $this->getIndexPageFields());

        // For detail and form pages, organize everything into tabs
        $fields = array_merge($fields, $this->getTabOrganizedFields());

        return $fields;
    }

    /**
     * Get fields specifically for index page (outside of any tabs)
     */
    private function getIndexPageFields(): array
    {
        return [
            // Active field for index page only
            $this->fieldService->field('active')
                ->type('boolean')
                ->label('Active')
                ->pages(['index'])
                ->build(),
                
            // Company name with link to detail for index page
            $this->fieldService->field('name')
                ->type('text')
                ->label('Name')
                ->required(true)
                ->linkToShow() // This will auto-detect the CompanyCrudController
                ->pages(['index'])
                ->build(),
                
            // Additional fields for index view
            $this->fieldService->createFieldConfig('companyGroup', 'association', ['index'], 'Company Group'),
            
            // Contact information for index view
            $this->fieldService->createFieldConfig('url', 'url', ['index'], 'Website'),
            $this->fieldService->createCountryFieldConfig('countryCode', ['index'], 'Country'),
        ];
    }

    /**
     * Get all fields organized into tabs for detail and form pages
     */
    private function getTabOrganizedFields(): array
    {
        $fields = [];
        
        // Company Information Tab
        $fields[] = $this->fieldService->createTabConfig('company_info_tab', 'Company Information');
        
        // Active field for detail and form pages
        $fields[] = $this->fieldService->field('active')
            ->type('boolean')
            ->label('Active')
            ->pages(['detail', 'form'])
            ->build();
            
        $fields[] = $this->fieldService->field('id')
            ->type('id')
            ->label('ID')
            ->pages(['detail']) // ID only on detail page, not form
            ->build();
            
        $fields[] = $this->fieldService->field('name')
            ->type('text')
            ->label('Name')
            ->required(true)
            ->pages(['detail', 'form']) // Show on detail and form pages inside tab
            ->build();
            
        $fields[] = $this->fieldService->createFieldConfig('nameExtension', 'text', ['detail', 'form'], 'Name Extension');
        $fields[] = $this->fieldService->createFieldConfig('companyGroup', 'association', ['detail', 'form'], 'Company Group');
        
        // Contact Information Tab
        $fields[] = $this->fieldService->createTabConfig('contact_tab', 'Contact Information');
        $fields = array_merge($fields, $this->getContactFieldsForTabs());
        
        // Users Tab
        $fields[] = $this->fieldService->createTabConfig('users_tab', 'Users');
        $fields[] = $this->fieldService->field('employees')
            ->type('association')
            ->label('Users/Employees')
            ->pages(['detail'])
            ->formatValue($this->embeddedTableService->createEmbeddedTableFormatter([
                'email' => 'Email', 
                'active' => 'Active',
                'createdAt' => 'Created'
            ], 'Users', 'No users assigned'))
            ->renderAsHtml(true)
            ->build();
            
        // For form page, show regular association field for users
        $fields[] = $this->fieldService->field('employees')
            ->type('association')
            ->label('Users/Employees')
            ->multiple(true)
            ->pages(['form'])
            ->autocomplete(true)
            ->build();

        // Projects Tab
        $fields[] = $this->fieldService->createTabConfig('projects_tab', 'Projects');
        $fields[] = $this->fieldService->field('projects')
            ->type('association')
            ->label('Projects')
            ->pages(['detail'])
            ->formatValue($this->embeddedTableService->createEmbeddedTableFormatter([
                'name' => 'Project Name',
                'status' => 'Status',
                'createdAt' => 'Created'
            ], 'Projects', 'No projects assigned'))
            ->renderAsHtml(true)
            ->build();
            
        // For form page, show regular association field for projects
        $fields[] = $this->fieldService->field('projects')
            ->type('association')
            ->label('Projects')
            ->multiple(true)
            ->pages(['form'])
            ->autocomplete(true)
            ->build();

        return $fields;
    }

    /**
     * Get contact fields for use inside tabs (without panels)
     */
    private function getContactFieldsForTabs(): array
    {
        return [
            // Communication fields
            $this->fieldService->createFieldConfig('email', 'email', ['detail', 'form'], 'Email Address'),
            $this->fieldService->createFieldConfig('phone', 'telephone', ['detail', 'form'], 'Phone Number'),
            $this->fieldService->createFieldConfig('cell', 'telephone', ['detail', 'form'], 'Mobile/Cell Phone'),
            $this->fieldService->createFieldConfig('url', 'url', ['detail', 'form'], 'Website'),
            
            // Address fields
            $this->fieldService->createFieldConfig('street', 'text', ['detail', 'form'], 'Street Address'),
            $this->fieldService->createFieldConfig('zip', 'text', ['detail', 'form'], 'ZIP/Postal Code'),
            $this->fieldService->createFieldConfig('city', 'text', ['detail', 'form'], 'City'),
            $this->fieldService->createCountryFieldConfig('countryCode', ['detail', 'form'], 'Country'),
        ];
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
