<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\Admin\Traits\FieldConfigurationTrait;
use App\Entity\Project;
use App\Entity\User;
use App\Enum\ProjectStatus;
use App\Service\DuplicateService;
use App\Service\EasyAdminFieldService;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProjectCrudController extends AbstractCrudController
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
        return Project::class;
    }

    #[\Override]
    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setHelp('index', $this->translator->trans('Manage projects, assignments, and timelines.'));
    }

    #[IsGranted('read', subject: 'Project')]
    #[\Override]
    public function index(AdminContext $context, string $Project = 'Project'): KeyValueStore|Response
    {
        return parent::index($context);
    }

    #[IsGranted('read', subject: 'Project')]
    #[\Override]
    public function detail(AdminContext $context, string $Project = 'Project'): KeyValueStore|Response
    {
        return parent::detail($context);
    }

    #[IsGranted('write', subject: 'Project')]
    #[\Override]
    public function new(AdminContext $context, string $Project = 'Project'): KeyValueStore|Response
    {
        return parent::new($context);
    }

    #[IsGranted('write', subject: 'Project')]
    #[\Override]
    public function edit(AdminContext $context, string $Project = 'Project'): KeyValueStore|Response
    {
        return parent::edit($context);
    }

    #[IsGranted('write', subject: 'Project')]
    #[\Override]
    public function delete(AdminContext $context, string $Project = 'Project'): KeyValueStore|Response
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
     * Define field configurations for Project entity.
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
                ->label('Name')
                ->help('The name of the project')
                ->linkToShow() // Add link to show action
                ->build(),

            $this->fieldService->field('description')
                ->type('textarea')
                ->label('Description')
                ->help('Detailed description of the project')
                ->pages(['detail', 'form'])
                ->build(),

            $this->fieldService->field('status')
                ->type('choice')
                ->label('Status')
                ->help('Current status of the project')
                ->pages(['index', 'detail', 'form'])  // Include index page for better visibility
                ->choices([
                    $this->translator->trans('Planning') => ProjectStatus::PLANNING,
                    $this->translator->trans('In Progress') => ProjectStatus::IN_PROGRESS,
                    $this->translator->trans('On Hold') => ProjectStatus::ON_HOLD,
                    $this->translator->trans('Completed') => ProjectStatus::COMPLETED,
                    $this->translator->trans('Cancelled') => ProjectStatus::CANCELLED,
                ])
                ->formatValue(function ($value, $entity) {
                    if ($value instanceof ProjectStatus) {
                        return $value->getLabel();
                    }

                    return $this->translator->trans('Unknown');
                })
                ->build(),

            $this->fieldService->field('assignee')
                ->type('association')
                ->label('Assignee')
                ->help('User responsible for this project')
                ->formatValue(function ($value, $entity) {
                    if (!$value) {
                        return $this->translator->trans('Keine Zuordnung');
                    }

                    return $value->getEmail();
                })
                ->build(),

            $this->fieldService->field('client')
                ->type('association')
                ->label('Client')
                ->help('Company or client for this project')
                ->formatValue(function ($value, $entity) {
                    if (!$value) {
                        return $this->translator->trans('Keine Zuordnung');
                    }

                    return $value->getName();
                })
                ->build(),

            $this->fieldService->field('startedAt')
                ->type('date')
                ->label('Start Date')
                ->help('Project start date')
                ->pages(['detail', 'form'])
                ->build(),

            $this->fieldService->field('endedAt')
                ->type('date')
                ->label('End Date')
                ->help('Project end date')
                ->pages(['detail', 'form'])
                ->build(),
        ];
    }

    #[\Override]
    public function configureActions(Actions $actions): Actions
    {
        return parent::configureActions($actions);
    }

    protected function canCreateEntity(): bool
    {
        $user = $this->getUser();

        return $user instanceof User
               && (in_array('ROLE_ADMIN', $user->getRoles(), true)
                || in_array('ROLE_USER', $user->getRoles(), true));
    }

    protected function canEditEntity($entity): bool
    {
        $user = $this->getUser();

        return $user instanceof User
               && (in_array('ROLE_ADMIN', $user->getRoles(), true)
                || ($entity->getAssignee() && $entity->getAssignee()->getId() === $user->getId()));
    }

    protected function canDeleteEntity($entity): bool
    {
        $user = $this->getUser();

        return $user instanceof User && in_array('ROLE_ADMIN', $user->getRoles(), true);
    }

    protected function canViewEntity($entity): bool
    {
        $user = $this->getUser();

        return $user instanceof User
               && (in_array('ROLE_ADMIN', $user->getRoles(), true)
                || ($entity->getAssignee() && $entity->getAssignee()->getId() === $user->getId())
                || in_array('ROLE_USER', $user->getRoles(), true));
    }

    /**
     * Override to set default status for new projects.
     */
    #[\Override]
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Project && ProjectStatus::PLANNING === $entityInstance->getStatus()) {
            $entityInstance->setStatus(ProjectStatus::PLANNING); // Ensure Planning status is set
        }

        parent::persistEntity($entityManager, $entityInstance);
    }
}
