<?php

namespace App\Controller\Admin;

use App\Entity\Module;
use App\Entity\User;
use App\Service\PermissionService;
use App\Service\DuplicateService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController as EasyAdminAbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractCrudController extends EasyAdminAbstractCrudController
{
    protected EntityManagerInterface $entityManager;
    protected TranslatorInterface $translator;
    protected PermissionService $permissionService;
    protected DuplicateService $duplicateService;
    protected RequestStack $requestStack;

    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        PermissionService $permissionService,
        DuplicateService $duplicateService,
        RequestStack $requestStack
    ) {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
        $this->permissionService = $permissionService;
        $this->duplicateService = $duplicateService;
        $this->requestStack = $requestStack;
    }

    /**
     * Get the module code that corresponds to the entity name
     */
    abstract protected function getModuleCode(): string;
    
    /**
     * Get the module name associated with this CRUD controller
     * Automatically translates the module code
     */
    protected function getModuleName(): string
    {
        return $this->translator->trans($this->getModuleCode());
    }

    /**
     * Get the module entity for permission checking
     */
    protected function getModule(): Module
    {
        return $this->permissionService->getModuleByCode($this->getModuleCode());
    }

    /**
     * Configure CRUD with permission-based actions
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', sprintf('%s %s', $this->translator->trans($this->getModuleName()), $this->translator->trans('Management')))
            ->setPageTitle('detail', fn ($entity) => sprintf('%s %s', $this->translator->trans($this->getModuleName()), $this->translator->trans('Show')))
            ->setPageTitle('new', sprintf('%s %s', $this->translator->trans($this->getModuleName()), $this->translator->trans('Create')))
            ->setPageTitle('edit', fn ($entity) => sprintf('%s %s', $this->translator->trans($this->getModuleName()), $this->translator->trans('Edit')))
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->showEntityActionsInlined(false); // Show actions as dropdown menu
    }

    /**
     * Configure actions based on permissions
     */
    public function configureActions(Actions $actions): Actions
    {
        // Create the duplicate action
        $duplicateAction = Action::new('duplicate', $this->translator->trans('Duplicate'))
            ->setIcon('fa fa-copy')
            ->linkToCrudAction('duplicateAction')
            ->setHtmlAttributes([
                'title' => $this->translator->trans('Duplicate this record'),
                'data-bs-toggle' => 'tooltip'
            ]);

        $actions = $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $duplicateAction)
            ->add(Crud::PAGE_DETAIL, $duplicateAction)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn (Action $action) => $action->setLabel($this->translator->trans('Show'))->setIcon('fa fa-eye'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action) => $action->setLabel($this->translator->trans('Edit'))->setIcon('fa fa-edit'))
            ->update(Crud::PAGE_INDEX, Action::DELETE, fn (Action $action) => $action->setLabel($this->translator->trans('Delete'))->setIcon('fa fa-trash'))
            ->setPermission(Action::DETAIL, 'ROLE_USER')
            ->setPermission(Action::NEW, 'ROLE_USER')
            ->setPermission(Action::EDIT, 'ROLE_USER')
            ->setPermission(Action::DELETE, 'ROLE_USER')
            ->setPermission('duplicate', 'ROLE_USER');
        
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
                ->disable(Action::BATCH_DELETE)
                ->disable('duplicate');
        }

        return $actions;
    }

    /**
     * Add active field to pages with EasyAdmin's native boolean display
     */
    protected function addActiveField(array $fields, string $pageName = Crud::PAGE_INDEX): array
    {
        if ($pageName === Crud::PAGE_INDEX) {
            $fields[] = BooleanField::new('active', $this->translator->trans('Active'))
                ->onlyOnIndex();
        } else {
            $fields[] = BooleanField::new('active', $this->translator->trans('Active'));
        }
        
        return $fields;
    }

    /**
     * Get a human-readable label for an entity
     */
    protected function getEntityLabel($entity): string
    {
        // If entity has toString method, use it directly (already localized)
        if (method_exists($entity, '__toString')) {
            return (string) $entity;
        }
        
        // Otherwise, translate the module name
        return $this->translator->trans($this->getModuleName());
    }

    /**
     * Check if this controller should show the user permission management UI
     */
    protected function hasPermissionManagement(): bool
    {
        return false;
    }

    /**
     * Create module permission fields for entities that support permission management
     */
    protected function createModulePermissionFields(): array
    {
        if (!$this->hasPermissionManagement()) {
            return [];
        }

        return $this->permissionService->createModulePermissionFields($this->getContext());
    }

    /**
     * Add permission tab to form fields for entities that support permission management
     */
    protected function addPermissionTabToFields(array $fields): array
    {
        if (!$this->hasPermissionManagement()) {
            return $fields;
        }

        return $this->permissionService->addPermissionTabToFields($fields, $this->getContext());
    }

    /**
     * Add permission summary field for index pages
     */
    protected function addPermissionSummaryField(array $fields): array
    {
        if (!$this->hasPermissionManagement()) {
            return $fields;
        }

        return $this->permissionService->addPermissionSummaryField($fields);
    }

    /**
     * Override to handle permissions on entity creation
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$this->hasPermissionManagement()) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        $this->permissionService->persistEntityWithPermissions(
            fn($entity) => parent::persistEntity($entityManager, $entity),
            $entityInstance,
            $this->getContext()
        );
    }

    /**
     * Override to handle permissions on entity update
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$this->hasPermissionManagement()) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        $this->permissionService->updateEntityWithPermissions(
            fn($entity) => parent::updateEntity($entityManager, $entity),
            $entityInstance,
            $this->getContext()
        );
    }

    /**
     * Duplicate action for entities with permission check
     */
    public function duplicateAction(AdminContext $context): Response
    {
        // Check permissions manually
        if (!$this->isGranted('write', $this->getModule())) {
            throw $this->createAccessDeniedException();
        }
        
        $entity = $context->getEntity()->getInstance();
        
        try {
            // Create a duplicate entity (but don't persist it)
            $duplicatedEntity = $this->duplicateService->duplicate($entity);
            
            // Store only the basic scalar data in the session
            $session = $this->requestStack->getSession();
            $sessionKey = 'duplicated_entity_' . static::class;
            
            // Use a simpler approach - just store the entity ID and duplicate it fresh when needed
            $entityData = [
                'original_id' => $entity->getId(),
                'entity_class' => get_class($entity)
            ];
            
            $session->set($sessionKey, $entityData);
            
            // Redirect to the new page with a duplicate parameter
            $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
            $url = $adminUrlGenerator
                ->setController(static::class)
                ->setAction(Action::NEW)
                ->set('duplicate', '1')
                ->generateUrl();
            
            return $this->redirect($url);
            
        } catch (\Exception $e) {
            // Add error message
            $this->addFlash('danger', $this->translator->trans('Error duplicating entity: ') . $e->getMessage());
            
            // Redirect back to the index page
            $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
            $url = $adminUrlGenerator
                ->setController(static::class)
                ->setAction(Action::INDEX)
                ->generateUrl();
            
            return $this->redirect($url);
        }
    }

    /**
     * Override edit action to handle entity associations
     */
    public function edit(AdminContext $context): KeyValueStore|Response
    {
        $entity = $context->getEntity()->getInstance();
        
        // Ensure all associations are properly managed to avoid proxy issues
        $this->ensureEntityAssociationsAreManaged($entity);
        
        return parent::edit($context);
    }

    /**
     * Override createEntity to handle duplicated entities
     * This method is called by EasyAdmin when creating a new entity instance
     */
    public function createEntity(string $entityFqcn)
    {
        // Check if this is a duplicate request
        $request = $this->requestStack->getCurrentRequest();
        $isDuplicate = $request && $request->query->get('duplicate') === '1';
        
        if ($isDuplicate) {
            $sessionKey = 'duplicated_entity_' . static::class;
            $session = $this->requestStack->getSession();
            $entityData = $session->get($sessionKey);
            
            if ($entityData && isset($entityData['original_id'], $entityData['entity_class'])) {
                // Remove from session to prevent reuse
                $session->remove($sessionKey);
                
                // Find the original entity by ID
                $originalEntity = $this->entityManager->find($entityData['entity_class'], $entityData['original_id']);
                
                if ($originalEntity) {
                    try {
                        // Use DuplicateService to create a fresh duplicate
                        $duplicatedEntity = $this->duplicateService->duplicate($originalEntity);
                        
                        // Make sure all associations are properly managed
                        $this->ensureEntityAssociationsAreManaged($duplicatedEntity);
                        
                        $this->addFlash('success', $this->translator->trans('Form pre-filled with duplicated values. Click "Save" to create the new record.'));
                        
                        return $duplicatedEntity;
                    } catch (\Exception $e) {
                        $this->addFlash('danger', 'Error duplicating entity: ' . $e->getMessage());
                    }
                }
            }
        }
        
        // Fall back to the default behavior
        return parent::createEntity($entityFqcn);
    }
    
    /**
     * Ensure all associations in the entity are managed by the EntityManager
     */
    private function ensureEntityAssociationsAreManaged(object $entity): void
    {
        try {
            $entityClass = get_class($entity);
            
            // Handle Doctrine proxies - extract the real class name
            if (strpos($entityClass, 'Proxies\\__CG__\\') === 0) {
                $entityClass = substr($entityClass, strlen('Proxies\\__CG__\\'));
            }
            
            $metadata = $this->entityManager->getClassMetadata($entityClass);
            
            foreach ($metadata->getAssociationMappings() as $fieldName => $mapping) {
                if ($metadata->hasAssociation($fieldName)) {
                    $value = $metadata->getFieldValue($entity, $fieldName);
                    
                    if ($value instanceof \Doctrine\Common\Collections\Collection) {
                        // Handle collections - ensure all entities in the collection are managed
                        $toRemove = [];
                        $toAdd = [];
                        
                        foreach ($value as $relatedEntity) {
                            if (is_object($relatedEntity) && !$this->entityManager->contains($relatedEntity)) {
                                $managedEntity = $this->findManagedEntity($relatedEntity);
                                if ($managedEntity && $managedEntity !== $relatedEntity) {
                                    $toRemove[] = $relatedEntity;
                                    $toAdd[] = $managedEntity;
                                }
                            }
                        }
                        
                        // Apply changes outside the iteration to avoid modification during iteration
                        foreach ($toRemove as $entityToRemove) {
                            $value->removeElement($entityToRemove);
                        }
                        foreach ($toAdd as $entityToAdd) {
                            $value->add($entityToAdd);
                        }
                        
                    } elseif (is_object($value) && !$this->entityManager->contains($value)) {
                        // Handle single associations - this is the key fix for the proxy issue
                        $managedEntity = $this->findManagedEntity($value);
                        if ($managedEntity) {
                            $metadata->setFieldValue($entity, $fieldName, $managedEntity);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // If anything fails, don't break the application
        }
    }
    
    /**
     * Find the managed version of an entity
     */
    private function findManagedEntity(object $entity): ?object
    {
        // If entity is already managed, return it
        if ($this->entityManager->contains($entity)) {
            return $entity;
        }
        
        try {
            $entityClass = get_class($entity);
            
            // Handle Doctrine proxies - extract the real class name
            if (strpos($entityClass, 'Proxies\\__CG__\\') === 0) {
                $entityClass = substr($entityClass, strlen('Proxies\\__CG__\\'));
            }
            
            $metadata = $this->entityManager->getClassMetadata($entityClass);
            $identifier = $metadata->getIdentifierValues($entity);
            
            if (!empty($identifier)) {
                // Find the managed entity by ID
                $managedEntity = $this->entityManager->find($entityClass, $identifier);
                if ($managedEntity) {
                    return $managedEntity;
                }
            }
            
            // If we can't find by ID, try to initialize the proxy if it's a proxy
            if (method_exists($entity, '__isInitialized') && method_exists($entity, '__load')) {
                if (!$entity->__isInitialized()) {
                    $entity->__load();
                }
                // After loading, the entity might be managed now
                if ($this->entityManager->contains($entity)) {
                    return $entity;
                }
                // Try to find it again after loading
                if (!empty($identifier)) {
                    $managedEntity = $this->entityManager->find($entityClass, $identifier);
                    if ($managedEntity) {
                        return $managedEntity;
                    }
                }
            }
            
        } catch (\Exception $e) {
            // If anything fails, return null
        }
        
        return null;
    }
}
