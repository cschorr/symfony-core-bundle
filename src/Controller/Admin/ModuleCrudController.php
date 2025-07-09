<?php

namespace App\Controller\Admin;

use App\Entity\Module;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

class ModuleCrudController extends AbstractCrudController
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
        return Module::class;
    }

    protected function getModuleCode(): string
    {
        return 'Module';
    }

    protected function getModuleName(): string
    {
        return $this->translator->trans('Module');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setPageTitle('index', $this->translator->trans('System Modules'))
            ->setPageTitle('detail', fn ($entity) => sprintf('%s: %s', $this->translator->trans('Module'), $entity->getName()))
            ->setPageTitle('new', $this->translator->trans('Create System Module'))
            ->setHelp('index', $this->translator->trans('Manage system modules and their permissions.'));
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
            throw new AccessDeniedException('Access denied. You need read permission for the Module module.');
        }
        return parent::index($context);
    }

    public function detail(AdminContext $context)
    {
        if (!$this->isGranted('read', $this->getModule())) {
            throw new AccessDeniedException('Access denied. You need read permission for the Module module.');
        }
        return parent::detail($context);
    }

    public function new(AdminContext $context)
    {
        if (!$this->isGranted('write', $this->getModule())) {
            throw new AccessDeniedException('Access denied. You need write permission for the Module module.');
        }
        return parent::new($context);
    }

    public function edit(AdminContext $context)
    {
        if (!$this->isGranted('write', $this->getModule())) {
            throw new AccessDeniedException('Access denied. You need write permission for the Module module.');
        }
        return parent::edit($context);
    }

    public function delete(AdminContext $context)
    {
        if (!$this->isGranted('write', $this->getModule())) {
            throw new AccessDeniedException('Access denied. You need write permission for the Module module.');
        }
        return parent::delete($context);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm()->hideOnIndex(),
            TextField::new('name')
                ->setHelp($this->translator->trans('Display name for the module')),
            TextField::new('code')
                ->setHelp($this->translator->trans('Unique code that matches the entity name (e.g., User, Company, Module)'))
                ->setFormTypeOption('attr', ['placeholder' => $this->translator->trans('e.g., User, Company, Module')]),
            TextareaField::new('text')
                ->setLabel($this->translator->trans('Description'))
                ->setHelp($this->translator->trans('Optional description of what this module manages')),
            AssociationField::new('userPermissions')
                ->setLabel($this->translator->trans('User Permissions'))
                ->hideOnForm()
                ->formatValue(function ($value, $entity) {
                    if (!$entity || !$entity->getUserPermissions() || $entity->getUserPermissions()->isEmpty()) {
                        return $this->translator->trans('No permissions assigned');
                    }
                    
                    $count = $entity->getUserPermissions()->count();
                    return sprintf($this->translator->trans('%d permission(s) assigned'), $count);
                }),
            AssociationField::new('userPermissions', $this->translator->trans('Permission Details'))
                ->hideOnForm()
                ->hideOnIndex()
                ->formatValue(function ($value, $entity) {
                    if (!$entity || !$entity->getUserPermissions() || $entity->getUserPermissions()->isEmpty()) {
                        return $this->translator->trans('No permissions assigned');
                    }
                    
                    $permissions = [];
                    foreach ($entity->getUserPermissions() as $permission) {
                        $user = $permission->getUser();
                        $userEmail = $user ? $user->getEmail() : $this->translator->trans('Unknown User');
                        $access = [];
                        if ($permission->canRead()) $access[] = $this->translator->trans('Read');
                        if ($permission->canWrite()) $access[] = $this->translator->trans('Write');
                        $accessString = implode(', ', $access);
                        
                        $permissions[] = sprintf('%s: %s', $userEmail, $accessString);
                    }
                    
                    return implode('<br>', $permissions);
                })
                ->renderAsHtml(),
        ];
    }

    protected function canCreateEntity(): bool
    {
        $user = $this->getUser();
        return $user instanceof \App\Entity\User && in_array('ROLE_ADMIN', $user->getRoles());
    }

    protected function canEditEntity($entity): bool
    {
        $user = $this->getUser();
        return $user instanceof \App\Entity\User && in_array('ROLE_ADMIN', $user->getRoles());
    }

    protected function canDeleteEntity($entity): bool
    {
        $user = $this->getUser();
        return $user instanceof \App\Entity\User && in_array('ROLE_ADMIN', $user->getRoles());
    }
}
