<?php

namespace App\Controller\Admin;

use App\Entity\CompanyGroup;
use App\Service\PermissionService;
use App\Service\DuplicateService;
use App\Service\EasyAdminFieldService;
use App\Controller\Admin\Traits\FieldConfigurationTrait;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class CompanyGroupCrudController extends AbstractCrudController
{
    use FieldConfigurationTrait;

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
        return CompanyGroup::class;
    }

    protected function hasPermissionManagement(): bool
    {
        return false;
    }

    #[IsGranted('read', subject: 'CompanyGroup')]
    public function index(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $CompanyGroup = 'CompanyGroup'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::index($context);
    }

    #[IsGranted('read', subject: 'CompanyGroup')]
    public function detail(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $CompanyGroup = 'CompanyGroup'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::detail($context);
    }

    #[IsGranted('write', subject: 'CompanyGroup')]
    public function edit(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $CompanyGroup = 'CompanyGroup'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::edit($context);
    }

    #[IsGranted('write', subject: 'CompanyGroup')]
    public function delete(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $CompanyGroup = 'CompanyGroup'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::delete($context);
    }

    public function configureFields(string $pageName): iterable
    {
        return $this->fieldService->generateFields(
            $this->getFieldConfigurations(),
            $pageName
        );
    }

    /**
     * Define field configurations for CompanyGroup entity
     */
    private function getFieldConfigurations(): array
    {
        return [
            // Active field first, enabled for all views
            ...$this->getActiveField(),

            // Standard fields
            $this->fieldService->createIdField(),
            $this->fieldService->field('name')
                ->type('text')
                ->label($this->translator->trans('Name'))
                ->build(),

            $this->fieldService->field('code')
                ->type('text')
                ->label($this->translator->trans('Code'))
                ->required(false)
                ->build(),
        ];
    }
}
