<?php

namespace App\Controller\Admin;

use App\Entity\SystemEntity;
use App\Entity\User;
use App\Service\PermissionService;
use App\Service\DuplicateService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController as EasyAdminAbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use ReflectionClass;
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
     * @throws \ReflectionException
     */
    protected function getSystemEntityCode(): string
    {
        $reflection = new ReflectionClass($this->getEntityFqcn());
        return $reflection->getShortName();
    }

    /**
     * Get the translated system entity name for display (singular)
     */
    protected function getSystemEntityName(): string
    {
        return $this->translator->trans($this->getSystemEntityCode());
    }

    /**
     * Get the translated system entity name for display (plural)
     */
    protected function getSystemEntityNamePlural(): string
    {
        // Map entity codes to their proper plural translation keys
        $pluralMap = [
            'User' => 'Users',
            'Company' => 'Companies',
            'SystemEntity' => 'System Entities',
            'CompanyGroup' => 'CompanyGroups',
            'Project' => 'Projects'
        ];

        $entityCode = $this->getSystemEntityCode();
        $pluralKey = $pluralMap[$entityCode] ?? $entityCode . 's';

        return $this->translator->trans($pluralKey);
    }    /**
     * Get the system entity for permission checking
     */
    protected function getSystemEntity(): SystemEntity
    {
        return $this->permissionService->getSystemEntityByCode($this->getSystemEntityCode());
    }

    /**
     * Configure CRUD with permission-based actions
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', $this->getSystemEntityNamePlural())
            ->setPageTitle('detail', fn ($entity) => sprintf('%s %s', $this->getSystemEntityName(), $this->translator->trans('Show')))
            ->setPageTitle('new', sprintf('%s %s', $this->getSystemEntityName(), $this->translator->trans('Create')))
            ->setPageTitle('edit', fn ($entity) => sprintf('%s %s', $this->getSystemEntityName(), $this->translator->trans('Edit')))
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(25)
            ->showEntityActionsInlined(false); // Show actions as dropdown menu
    }

    /**
     * Configure assets to include the EasyAdmin theme CSS and admin.js on all CRUD pages
     */
    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addCssFile('styles/easyadmin-theme.css')
            ->addJsFile('admin.js');
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
            ->reorder(Crud::PAGE_INDEX, [Action::DETAIL, Action::EDIT, 'duplicate', Action::DELETE])
            ->setPermission(Action::DETAIL, 'ROLE_USER')
            ->setPermission(Action::NEW, 'ROLE_USER')
            ->setPermission(Action::EDIT, 'ROLE_USER')
            ->setPermission(Action::DELETE, 'ROLE_USER')
            ->setPermission('duplicate', 'ROLE_USER');

        // Check permissions and disable actions accordingly
        if (!$this->isGranted('read', $this->getSystemEntity())) {
            $actions
                ->disable(Action::INDEX)
                ->disable(Action::DETAIL);
        }

        if (!$this->isGranted('write', $this->getSystemEntity())) {
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
        // Active field should be available on both index and form pages for toggle to work
        $fields[] = BooleanField::new('active', $this->translator->trans('Active'));

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

        // Otherwise, translate the system entity name
        return $this->translator->trans($this->getSystemEntityName());
    }

    /**
     * Check if this controller should show the user permission management UI
     */
    protected function hasPermissionManagement(): bool
    {
        return false;
    }

    /**
     * Create system entity permission fields for entities that support permission management
     */
    protected function createSystemEntityPermissionFields(): array
    {
        if (!$this->hasPermissionManagement()) {
            return [];
        }

        // Get the current entity from context if available
        $entity = null;
        $context = $this->getContext();
        if ($context && $context->getEntity()) {
            $entityInstance = $context->getEntity()->getInstance();
            if ($entityInstance instanceof User) {
                $entity = $entityInstance;
            }
        }

        return $this->permissionService->createSystemEntityPermissionFields($entity);
    }

    /**
     * Add permission tab to form fields for entities that support permission management
     */
    protected function addPermissionTabToFields(array $fields): array
    {
        if (!$this->hasPermissionManagement()) {
            return $fields;
        }

        // Get the current entity from context if available
        $entity = null;
        $context = $this->getContext();
        if ($context && $context->getEntity()) {
            $entityInstance = $context->getEntity()->getInstance();
            if ($entityInstance instanceof User) {
                $entity = $entityInstance;
            }
        }

        return $this->permissionService->addPermissionTabToFields($fields, $entity);
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
        parent::persistEntity($entityManager, $entityInstance);

        // Handle permissions if this is a User entity
        if ($entityInstance instanceof User && $this->hasPermissionManagement()) {
            $request = $this->getContext()->getRequest();
            $formData = $request->request->all();
            $this->permissionService->handleSystemEntityPermissions($entityInstance, $formData);
        }
    }

    /**
     * Override to handle permissions on entity update
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::updateEntity($entityManager, $entityInstance);

        // Handle permissions if this is a User entity
        if ($entityInstance instanceof User && $this->hasPermissionManagement()) {
            $request = $this->getContext()->getRequest();
            $formData = $request->request->all();
            $this->permissionService->handleSystemEntityPermissions($entityInstance, $formData);
        }
    }

    /**
     * Duplicate action for entities with permission check
     */
    public function duplicateAction(AdminContext $context): Response
    {
        // Check permissions manually
        if (!$this->isGranted('write', $this->getSystemEntity())) {
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

    /**
     * Override to handle foreign key constraint violations
     */
    public function delete(AdminContext $context): KeyValueStore|Response
    {
        $entity = $context->getEntity()->getInstance();
        $request = $this->requestStack->getCurrentRequest();

        // Check if entity can be deleted (implemented by child classes)
        if (method_exists($this, 'canDeleteEntity') && !call_user_func([$this, 'canDeleteEntity'], $entity)) {
            $this->addFlash('danger', $this->translator->trans('Cannot delete record. It has related records that prevent deletion.'));

            // Redirect back to the referer page or fallback to detail page
            $referer = $request->headers->get('referer');
            if ($referer) {
                return $this->redirect($referer);
            }

            // Fallback to detail page if no referer
            $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
            $url = $adminUrlGenerator
                ->setController(static::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($entity->getId())
                ->generateUrl();

            return $this->redirect($url);
        }

        try {
            return parent::delete($context);
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            // Handle foreign key constraint violations
            $this->addFlash('danger', $this->translator->trans('Cannot delete record. It has related records that prevent deletion. Please remove all related records first.'));

            // Redirect back to the referer page or fallback to detail page
            $referer = $request->headers->get('referer');
            if ($referer) {
                return $this->redirect($referer);
            }

            // Fallback to detail page if no referer
            $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
            $url = $adminUrlGenerator
                ->setController(static::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($entity->getId())
                ->generateUrl();

            return $this->redirect($url);
        }
    }

    /**
     * Auto-sync bidirectional relationships using RelationshipSyncService
     */
    protected function autoSyncRelationships(object $entity): void
    {
        // This can be overridden by child controllers if they inject RelationshipSyncService
    }

    /**
     * Get common field validation rules
     */
    protected function getFieldValidationRules(): array
    {
        return [
            'name' => ['required' => true, 'maxLength' => 255],
            'email' => ['required' => false, 'type' => 'email'],
            'phone' => ['required' => false, 'type' => 'telephone'],
            'url' => ['required' => false, 'type' => 'url'],
        ];
    }

    /**
     * Apply common business logic before persist
     */
    protected function beforePersist(object $entity): void
    {
        $this->autoSyncRelationships($entity);
    }

    /**
     * Apply common business logic before update
     */
    protected function beforeUpdate(object $entity): void
    {
        $this->autoSyncRelationships($entity);
    }
}
