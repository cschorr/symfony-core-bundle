<?php

namespace App\Controller\Admin;

use App\Entity\Project;
use App\Service\PermissionService;
use App\Service\DuplicateService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProjetCrudController extends AbstractCrudController
{
    public function __construct(
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator,
        PermissionService $permissionService,
        DuplicateService $duplicateService,
        RequestStack $requestStack
    ) {
        parent::__construct($entityManager, $translator, $permissionService, $duplicateService, $requestStack);
    }

    public static function getEntityFqcn(): string
    {
        return Project::class;
    }

    protected function getModuleCode(): string
    {
        return 'Project';
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setPageTitle('index', $this->translator->trans('Projects'))
            ->setPageTitle('detail', fn ($entity) => sprintf('%s: %s', $this->translator->trans('Project'), $entity->getName()))
            ->setPageTitle('new', $this->translator->trans('Create Project'))
            ->setPageTitle('edit', fn ($entity) => sprintf('%s: %s', $this->translator->trans('Edit Project'), $entity->getName()))
            ->setHelp('index', $this->translator->trans('Manage projects, assignments, and timelines.'));
    }

    #[IsGranted('read', subject: 'Project')]
    public function index(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Project = 'Project'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::index($context);
    }

    #[IsGranted('read', subject: 'Project')]
    public function detail(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Project = 'Project'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::detail($context);
    }

    #[IsGranted('write', subject: 'Project')]
    public function new(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Project = 'Project'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::new($context);
    }

    #[IsGranted('write', subject: 'Project')]
    public function edit(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Project = 'Project'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::edit($context);
    }

    #[IsGranted('write', subject: 'Project')]
    public function delete(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, string $Project = 'Project'): \EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore|Response
    {
        return parent::delete($context);
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            IdField::new('id')->hideOnForm()->hideOnIndex(),
            TextField::new('name')
                ->setLabel($this->translator->trans('Project Name'))
                ->setHelp($this->translator->trans('The name of the project')),
            TextareaField::new('description')
                ->setLabel($this->translator->trans('Description'))
                ->setHelp($this->translator->trans('Detailed description of the project'))
                ->hideOnIndex(),
            ChoiceField::new('status')
                ->setLabel($this->translator->trans('Status'))
                ->setChoices([
                    $this->translator->trans('Planning') => 0,
                    $this->translator->trans('In Progress') => 1,
                    $this->translator->trans('On Hold') => 2,
                    $this->translator->trans('Completed') => 3,
                    $this->translator->trans('Cancelled') => 4,
                ])
                ->setHelp($this->translator->trans('Current status of the project'))
                ->renderAsBadges([
                    0 => 'secondary', // Planning
                    1 => 'primary',   // In Progress
                    2 => 'warning',   // On Hold
                    3 => 'success',   // Completed
                    4 => 'danger',    // Cancelled
                ]),
            AssociationField::new('assignee')
                ->setLabel($this->translator->trans('Assignee'))
                ->setHelp($this->translator->trans('User responsible for this project'))
                ->formatValue(function ($value, $entity) {
                    if (!$value) {
                        return $this->translator->trans('Not assigned');
                    }
                    return $value->getEmail();
                }),
            AssociationField::new('client')
                ->setLabel($this->translator->trans('Client'))
                ->setHelp($this->translator->trans('Company or client for this project'))
                ->formatValue(function ($value, $entity) {
                    if (!$value) {
                        return $this->translator->trans('No client assigned');
                    }
                    return $value->getName();
                }),
            DateField::new('startDate')
                ->setLabel($this->translator->trans('Start Date'))
                ->setHelp($this->translator->trans('Project start date'))
                ->hideOnIndex(),
            DateField::new('endDate')
                ->setLabel($this->translator->trans('End Date'))
                ->setHelp($this->translator->trans('Project end date'))
                ->hideOnIndex(),
        ];

        // Add active field to all pages
        $fields = $this->addActiveField($fields, $pageName);

        return $fields;
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        // Add custom action for project timeline view
        $viewTimeline = Action::new('viewTimeline', $this->translator->trans('Timeline'), 'fas fa-calendar-alt')
            ->linkToCrudAction('viewTimeline')
            ->displayIf(function ($entity) {
                return $entity->getStartDate() && $entity->getEndDate();
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $viewTimeline)
            ->add(Crud::PAGE_DETAIL, $viewTimeline);
    }

    public function viewTimeline(AdminContext $context): Response
    {
        $project = $context->getEntity()->getInstance();
        
        // You can implement timeline view logic here
        // For now, redirect to detail view
        return $this->redirectToRoute('admin', [
            'crudAction' => 'detail',
            'crudControllerFqcn' => static::class,
            'entityId' => $project->getId(),
        ]);
    }

    protected function canCreateEntity(): bool
    {
        $user = $this->getUser();
        return $user instanceof \App\Entity\User && 
               (in_array('ROLE_ADMIN', $user->getRoles()) || 
                in_array('ROLE_USER', $user->getRoles()));
    }

    protected function canEditEntity($entity): bool
    {
        $user = $this->getUser();
        return $user instanceof \App\Entity\User && 
               (in_array('ROLE_ADMIN', $user->getRoles()) ||
                ($entity->getAssignee() && $entity->getAssignee()->getId() === $user->getId()));
    }

    protected function canDeleteEntity($entity): bool
    {
        $user = $this->getUser();
        return $user instanceof \App\Entity\User && in_array('ROLE_ADMIN', $user->getRoles());
    }

    protected function canViewEntity($entity): bool
    {
        $user = $this->getUser();
        return $user instanceof \App\Entity\User && 
               (in_array('ROLE_ADMIN', $user->getRoles()) ||
                ($entity->getAssignee() && $entity->getAssignee()->getId() === $user->getId()) ||
                in_array('ROLE_USER', $user->getRoles()));
    }
}
