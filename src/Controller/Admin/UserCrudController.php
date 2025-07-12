<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Company;
use App\Service\PermissionService;
use App\Service\DuplicateService;
use App\Service\EasyAdminFieldService;
use App\Service\RelationshipSyncService;
use App\Controller\Admin\Traits\FieldConfigurationTrait;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserCrudController extends AbstractCrudController
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
        private AdminUrlGenerator $adminUrlGenerator
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
    public function new(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $User = 'User'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::new($context);
    }

    #[IsGranted('write', subject: 'User')]
    public function delete(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $User = 'User'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::delete($context);
    }

    public function configureFields(string $pageName): iterable
    {
        // Get base configuration from our new system
        $config = $this->getFieldConfiguration($pageName);
        $fields = $this->fieldService->generateFields($config, $pageName);
        
        // Add permission fields using the old system (for compatibility)
        if ($pageName === Crud::PAGE_INDEX || $pageName === Crud::PAGE_DETAIL) {
            $fields = $this->addPermissionSummaryField($fields);
        } elseif ($pageName === Crud::PAGE_EDIT || $pageName === Crud::PAGE_NEW) {
            $fields = $this->addPermissionTabToFields($fields);
        }
        
        return $fields;
    }

    /**
     * Get field configuration for User entity
     */
    private function getFieldConfiguration(string $pageName): array
    {
        // Page-specific field configurations
        if ($pageName === Crud::PAGE_INDEX) {
            $config = [
                ...$this->getActiveField(['index']), // Active field first for index
                $this->fieldService->createIdField(),
                $this->fieldService->field('email')
                    ->type('text')
                    ->label('Email')
                    ->formatValue(function ($value, $entity) {
                        // Create a link to the show action instead of mailto
                        $showUrl = $this->adminUrlGenerator
                            ->setController(self::class)
                            ->setAction(Action::DETAIL)
                            ->setEntityId($entity->getId())
                            ->generateUrl();
                        
                        return sprintf('<a href="%s" class="text-decoration-none">%s</a>', $showUrl, $value);
                    })
                    ->renderAsHtml()
                    ->build(),
                    
                $this->fieldService->field('roles')
                    ->type('choice')
                    ->label('Roles')
                    ->choices([
                        'User' => 'ROLE_USER',
                        'Admin' => 'ROLE_ADMIN',
                    ])
                    ->multiple(true)
                    ->build(),
                    
                $this->fieldService->field('company')
                    ->type('association')
                    ->label('Company')
                    ->build(),
            ];
            
        } elseif ($pageName === Crud::PAGE_DETAIL) {
            $config = [
                ...$this->getActiveField(['detail']), // Active field first for detail
                $this->fieldService->createIdField(),
                ...$this->getUserFields(['detail']),
                ...$this->getNotesField(['detail']),
                
                $this->fieldService->field('company')
                    ->type('association')
                    ->label('Company')
                    ->build(),
                    
                $this->fieldService->field('projects')
                    ->type('association')
                    ->label('Projects')
                    ->build(),
            ];
            
        } else { // FORM pages (NEW/EDIT) - Use tabs
            $config = [
                // User Information Tab - includes active field inside tab
                $this->fieldService->createTabConfig('user_info', 'User Information'),
                
                // Active field inside the tab
                ...$this->getActiveField(['form']),
                $this->fieldService->createIdField(),
                
                ...$this->getUserFields(['form']),
                ...$this->getNotesField(['form']),
                
                $this->fieldService->field('company')
                    ->type('association')
                    ->label('Company')
                    ->autocomplete(true)
                    ->build(),
                    
                $this->fieldService->field('projects')
                    ->type('association')
                    ->label('Projects')
                    ->multiple(true)
                    ->build(),
            ];
        }

        return $config;
    }

    /**
     * Override to use the new relationship sync service
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->relationshipSyncService->autoSync($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Override to use the new relationship sync service
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->relationshipSyncService->autoSync($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }
}
