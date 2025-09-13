<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Service\EasyAdminFieldService;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

/**
 * Advanced example showing multiple approaches for field configuration
 * using the enhanced EasyAdminFieldService.
 */
class UserCrudControllerAdvanced extends AbstractCrudController
{
    public function __construct(
        private EasyAdminFieldService $fieldService,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return $this->fieldService->generateFields(
            $this->getFieldConfigurations(),
            $pageName
        );
    }

    private function getFieldConfigurations(): array
    {
        $fields = [];

        // APPROACH 1: Using helper methods (simplest)
        $fields[] = $this->fieldService->createIdField();
        $fields[] = $this->fieldService->createNameField('Full Name');

        // APPROACH 2: Builder pattern (most flexible)
        $fields[] = $this->fieldService->field('email')
            ->type('email')
            ->required()
            ->indexLabel('Email')
            ->build();

        $fields[] = $this->fieldService->field('firstName')
            ->label('First Name')
            ->columns(6)
            ->required()
            ->build();

        $fields[] = $this->fieldService->field('lastName')
            ->label('Last Name')
            ->columns(6)
            ->required()
            ->build();

        // APPROACH 3: Pre-built field groups (fastest for common patterns)
        $fields = array_merge($fields, $this->fieldService->createCommunicationFieldGroup());
        $fields = array_merge($fields, $this->fieldService->createAddressFieldGroup());

        // APPROACH 4: Complex builder configurations
        $fields[] = $this->fieldService->createPanelConfig('company_panel', 'Company Information', ['detail', 'form'], 'fas fa-building');

        $fields[] = $this->fieldService->field('company')
            ->association(\App\Entity\Company::class, 'name')
            ->label('Company')
            ->build();

        // APPROACH 5: Advanced configurations
        $fields[] = $this->fieldService->createPanelConfig('account_panel', 'Account Settings', ['detail', 'form'], 'fas fa-cog');

        $fields[] = $this->fieldService->field('isActive')
            ->type('boolean')
            ->label('Active Account')
            ->renderAsSwitch()
            ->build();

        $fields[] = $this->fieldService->field('roles')
            ->choices([
                'Administrator' => 'ROLE_ADMIN',
                'Manager' => 'ROLE_MANAGER',
                'User' => 'ROLE_USER',
            ])
            ->multiple()
            ->label('User Roles')
            ->notOnIndex()
            ->build();

        // APPROACH 6: Auto-detection (experimental)
        $autoFields = $this->fieldService->createFields([
            'createdAt',    // Auto-detects as datetime
            'isEnabled',    // Auto-detects as boolean
            'phoneNumber',  // Auto-detects as telephone
            'websiteUrl',   // Auto-detects as url
        ]);
        $fields = array_merge($fields, $autoFields);

        return $fields;
    }
}

// COMPARISON: Before vs After

/*
 * OLD APPROACH (verbose and repetitive):
 *
 * $this->fieldService->createFieldConfig('email', 'email', ['index', 'detail', 'form'], 'Email Address', [
 *     'required' => true,
 *     'indexLabel' => 'Email',
 *     'columns' => 12,
 * ])
 *
 * NEW APPROACHES:
 *
 * // Builder pattern (readable and flexible):
 * $this->fieldService->field('email')
 *     ->required()
 *     ->indexLabel('Email')
 *     ->build()
 *
 * // Field groups (fastest for common patterns):
 * $this->fieldService->createCommunicationFieldGroup()
 *
 * // Auto-detection (minimal configuration):
 * $this->fieldService->createFields(['email'])
 */
