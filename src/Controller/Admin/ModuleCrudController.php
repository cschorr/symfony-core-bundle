<?php

namespace App\Controller\Admin;

use App\Entity\Module;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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

    #[IsGranted('read', subject: 'Module')]
    public function index(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Module = 'Module'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::index($context);
    }

    #[IsGranted('read', subject: 'Module')]
    public function detail(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Module = 'Module'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::detail($context);
    }

    #[IsGranted('write', subject: 'Module')]
    public function new(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Module = 'Module'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::new($context);
    }

    #[IsGranted('write', subject: 'Module')]
    public function edit(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Module = 'Module'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::edit($context);
    }

    #[IsGranted('write', subject: 'Module')]
    public function delete(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Module = 'Module'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
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
