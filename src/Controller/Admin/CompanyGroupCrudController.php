<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\Traits\FieldConfigurationTrait;
use App\Entity\CompanyGroup;
use App\Service\DuplicateService;
use App\Service\EasyAdminFieldService;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
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
        private EasyAdminFieldService $fieldService,
    ) {
        parent::__construct($entityManager, $translator, $permissionService, $duplicateService, $requestStack);
    }

    public static function getEntityFqcn(): string
    {
        return CompanyGroup::class;
    }

    #[\Override]
    protected function hasPermissionManagement(): bool
    {
        return false;
    }

    #[IsGranted('read', subject: 'CompanyGroup')]
    #[\Override]
    public function index(AdminContext $context, string $CompanyGroup = 'CompanyGroup'): KeyValueStore|Response
    {
        return parent::index($context);
    }

    #[IsGranted('read', subject: 'CompanyGroup')]
    #[\Override]
    public function detail(AdminContext $context, string $CompanyGroup = 'CompanyGroup'): KeyValueStore|Response
    {
        return parent::detail($context);
    }

    #[IsGranted('write', subject: 'CompanyGroup')]
    #[\Override]
    public function edit(AdminContext $context, string $CompanyGroup = 'CompanyGroup'): KeyValueStore|Response
    {
        return parent::edit($context);
    }

    #[IsGranted('write', subject: 'CompanyGroup')]
    #[\Override]
    public function delete(AdminContext $context, string $CompanyGroup = 'CompanyGroup'): KeyValueStore|Response
    {
        return parent::delete($context);
    }

    #[\Override]
    public function configureFields(string $pageName): iterable
    {
        return $this->fieldService->generateFields(
            $this->getFieldConfigurations(),
            $pageName
        );
    }

    /**
     * Define field configurations for CompanyGroup entity.
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
