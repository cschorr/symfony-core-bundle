<?php

namespace App\Service;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use Doctrine\Common\Collections\Collection;
use Symfony\Contracts\Translation\TranslatorInterface;

class EasyAdminFieldService
{
    public function __construct(
        private TranslatorInterface $translator,
        private AdminUrlGenerator $adminUrlGenerator,
        private ?EmbeddedTableService $embeddedTableService = null
    ) {
    }

    /**
     * Generate fields based on configuration array
     */
    public function generateFields(array $fieldConfigurations, string $pageName, ?callable $activeFieldCallback = null): array
    {
        $fields = [];
        
        foreach ($fieldConfigurations as $fieldConfig) {
            // Handle direct field objects (for backward compatibility)
            if (is_object($fieldConfig)) {
                $fields[] = $fieldConfig;
                continue;
            }
            
            // Handle configuration arrays
            if (is_array($fieldConfig)) {
                // Skip fields not meant for this page
                if (!$this->shouldShowField($fieldConfig, $pageName)) {
                    continue;
                }
                
                // Create the field based on configuration
                $field = $this->createField($fieldConfig, $pageName);
                if ($field) {
                    $fields[] = $field;
                }
            }
        }

        // Add active field for index page if callback provided
        if ($pageName === Crud::PAGE_INDEX && $activeFieldCallback) {
            $fields = $activeFieldCallback($fields, $pageName);
        }

        return $fields;
    }

    /**
     * Check if a field should be shown on a specific page
     */
    private function shouldShowField(array $fieldConfig, string $pageName): bool
    {
        // Handle special field types that should always be shown (tabs, panels)
        if (isset($fieldConfig['type']) && in_array($fieldConfig['type'], ['tab', 'panel'])) {
            // For panels, check if they should be shown on this page
            if ($fieldConfig['type'] === 'panel') {
                $pageType = $this->getPageType($pageName);
                return in_array($pageType, $fieldConfig['collapsible'] ?? []);
            }
            // Tabs are shown on form and detail pages
            $pageType = $this->getPageType($pageName);
            return in_array($pageType, ['form', 'detail']);
        }
        
        $pageType = $this->getPageType($pageName);
        return in_array($pageType, $fieldConfig['pages'] ?? []);
    }

    /**
     * Get page type from page name
     */
    private function getPageType(string $pageName): string
    {
        return match($pageName) {
            Crud::PAGE_INDEX => 'index',
            Crud::PAGE_DETAIL => 'detail',
            Crud::PAGE_NEW, Crud::PAGE_EDIT => 'form',
            default => 'unknown',
        };
    }

    /**
     * Create a field based on configuration
     */
    private function createField(array $config, string $pageName): ?object
    {
        $pageType = $this->getPageType($pageName);
        
        // Handle panels
        if ($config['type'] === 'panel') {
            $isCollapsible = in_array($pageType, $config['collapsible'] ?? []);
            return FormField::addPanel($this->translator->trans($config['label']))
                ->setIcon($config['icon'] ?? '')
                ->collapsible($isCollapsible);
        }

        // Handle tabs
        if ($config['type'] === 'tab') {
            return FormField::addTab($this->translator->trans($config['label']));
        }

        // Create field based on type
        $field = $this->createFieldByType($config);

        if (!$field) {
            return null;
        }

        // Apply common configurations
        $this->applyFieldConfiguration($field, $config, $pageType);

        return $field;
    }

    /**
     * Create field by type with support for many field types
     */
    private function createFieldByType(array $config): ?object
    {
        return match($config['type']) {
            'id' => IdField::new($config['name']),
            'text' => TextField::new($config['name']),
            'textarea' => TextareaField::new($config['name']),
            'email' => EmailField::new($config['name']),
            'telephone', 'phone' => TelephoneField::new($config['name']),
            'url' => UrlField::new($config['name']),
            'country' => CountryField::new($config['name']),
            'association' => AssociationField::new($config['name']),
            'boolean' => BooleanField::new($config['name']),
            'integer' => IntegerField::new($config['name']),
            'number' => NumberField::new($config['name']),
            'money' => MoneyField::new($config['name']),
            'date' => DateField::new($config['name']),
            'datetime' => DateTimeField::new($config['name']),
            'time' => TimeField::new($config['name']),
            'choice' => ChoiceField::new($config['name']),
            default => null,
        };
    }

    /**
     * Apply configuration to a field
     */
    private function applyFieldConfiguration(object $field, array $config, string $pageType): void
    {
        // Set label (use indexLabel for index page if available)
        $label = $pageType === 'index' && isset($config['indexLabel']) 
            ? $config['indexLabel'] 
            : ($config['label'] ?? $config['name']);
        $field->setLabel($this->translator->trans($label));

        // Hide field conditionally
        if ($config['hideOnForm'] ?? false) {
            $field->hideOnForm();
        }
        if ($config['hideOnIndex'] ?? false) {
            $field->hideOnIndex();
        }

        // Set columns for form fields (default to 12 if not specified)
        if ($pageType === 'form') {
            $columns = $config['columns'] ?? 12;
            $field->setColumns($columns);
        }

        // Set required
        if (isset($config['required'])) {
            $field->setRequired($config['required']);
        } else if ($pageType === 'form') {
            $field->setRequired(false);
        }

        // Apply additional configurations
        $this->applyAdditionalConfigurations($field, $config, $pageType);
    }

    /**
     * Apply additional configurations for specific field types and scenarios
     */
    private function applyAdditionalConfigurations(object $field, array $config, string $pageType): void
    {
        // Handle help text
        if (isset($config['help'])) {
            $field->setHelp($this->translator->trans($config['help']));
        }

        // Handle form type options
        if (isset($config['formTypeOptions'])) {
            foreach ($config['formTypeOptions'] as $key => $value) {
                $field->setFormTypeOption($key, $value);
            }
        }

        // Handle format value callback
        if (isset($config['formatValue'])) {
            $field->formatValue($config['formatValue']);
        }

        // Handle render as HTML
        if (isset($config['renderAsHtml']) && $config['renderAsHtml']) {
            $field->renderAsHtml();
        }

        // Handle linkToShow functionality
        if (isset($config['linkToShow']) && $config['linkToShow']) {
            $this->configureLinkToShow($field, $config);
        }

        // Handle country field specific configurations
        if ($config['type'] === 'country') {
            $this->configureCountryField($field, $config, $pageType);
        }

        // Handle custom formatting
        if (isset($config['format'])) {
            $this->applyCustomFormat($field, $config, $pageType);
        }

        // Handle association field configurations
        if ($config['type'] === 'association') {
            $this->configureAssociationField($field, $config, $pageType);
        }

        // Handle boolean field configurations
        if ($config['type'] === 'boolean') {
            $this->configureBooleanField($field, $config);
        }

        // Handle money field configurations
        if ($config['type'] === 'money') {
            $this->configureMoneyField($field, $config);
        }

        // Handle choice field configurations
        if ($config['type'] === 'choice') {
            $this->configureChoiceField($field, $config);
        }

        // Handle custom field options
        if (isset($config['fieldOptions'])) {
            $this->applyCustomFieldOptions($field, $config['fieldOptions']);
        }
    }

    /**
     * Apply custom formatting based on configuration
     */
    private function applyCustomFormat(object $field, array $config, string $pageType): void
    {
        $format = $config['format'];

        if ($format === 'count' && $config['type'] === 'association') {
            $field->formatValue(function ($value, $entity) use ($config) {
                if ($value instanceof Collection) {
                    $label = $config['countLabel'] ?? $config['label'] ?? 'Items';
                    return $value->count() . ' ' . $this->translator->trans($label);
                }
                return '0 ' . $this->translator->trans($config['countLabel'] ?? $config['label'] ?? 'Items');
            });
        }
    }

    /**
     * Configure country field to show flag only in index view
     */
    private function configureCountryField(object $field, array $config, string $pageType): void
    {
        // For index view, show flag only if showFlagOnly is set
        if ($pageType === 'index' && isset($config['showFlagOnly']) && $config['showFlagOnly']) {
            // Use EasyAdmin's native showFlag and showName methods
            $field->showFlag()->showName(false);
        } else {
            // For other views, show both flag and name (default)
            $field->showFlag()->showName(true);
        }
    }

    /**
     * Configure association fields
     */
    private function configureAssociationField(object $field, array $config, string $pageType): void
    {
        // Handle autocomplete
        if ($config['autocomplete'] ?? false) {
            $field->autocomplete();
        }

        // Handle multiple selection
        if ($pageType === 'form' && ($config['multiple'] ?? false)) {
            $formOptions = [
                'by_reference' => false,
                'multiple' => true,
            ];

            // Set target class
            if (isset($config['targetEntity'])) {
                $formOptions['class'] = $config['targetEntity'];
            }

            // Set choice label
            if (isset($config['choiceLabel'])) {
                if (is_callable($config['choiceLabel'])) {
                    $formOptions['choice_label'] = $config['choiceLabel'];
                } else {
                    $formOptions['choice_label'] = $config['choiceLabel'];
                }
            }

            $field->setFormTypeOptions($formOptions);
        }

        // Handle count formatting for index
        if ($pageType === 'index' && ($config['indexFormat'] ?? '') === 'count') {
            $this->applyCustomFormat($field, array_merge($config, ['format' => 'count']), $pageType);
        }
    }

    /**
     * Configure boolean fields
     */
    private function configureBooleanField(object $field, array $config): void
    {
        if (isset($config['renderAsSwitch'])) {
            $field->renderAsSwitch($config['renderAsSwitch']);
        }
    }

    /**
     * Configure money fields
     */
    private function configureMoneyField(object $field, array $config): void
    {
        if (isset($config['currency'])) {
            $field->setCurrency($config['currency']);
        }
        if (isset($config['storedAsCents'])) {
            $field->setStoredAsCents($config['storedAsCents']);
        }
    }

    /**
     * Configure choice fields
     */
    private function configureChoiceField(object $field, array $config): void
    {
        // Set choices if provided
        if (isset($config['choices'])) {
            $field->setChoices($config['choices']);
        }
        
        // Set multiple selection if specified
        if (isset($config['multiple']) && $config['multiple']) {
            $field->allowMultipleChoices(true);
        }
        
        // Set expanded if specified (renders as radio buttons or checkboxes)
        if (isset($config['expanded']) && $config['expanded']) {
            $field->renderExpanded(true);
        }
    }

    /**
     * Apply custom field options
     */
    private function applyCustomFieldOptions(object $field, array $options): void
    {
        foreach ($options as $method => $value) {
            if (method_exists($field, $method)) {
                if (is_array($value)) {
                    $field->$method(...$value);
                } else {
                    $field->$method($value);
                }
            }
        }
    }

    /**
     * Create a standard field configuration array
     */
    public function createFieldConfig(
        string $name,
        string $type,
        array $pages = ['index', 'detail', 'form'],
        ?string $label = null,
        array $options = []
    ): array {
        return array_merge([
            'name' => $name,
            'type' => $type,
            'pages' => $pages,
            'label' => $label ?? ucfirst($name),
        ], $options);
    }

    /**
     * Create a tab configuration
     */
    public function createTabConfig(string $name, string $label): array
    {
        return [
            'type' => 'tab',
            'name' => $name,
            'label' => $label,
        ];
    }

    /**
     * Create a panel configuration for grouping fields
     */
    public function createPanelConfig(string $name, string $label, array $pages = ['form'], string $icon = 'fas fa-folder'): array
    {
        return [
            'type' => 'panel',
            'name' => $name,
            'label' => $label,
            'pages' => $pages,
            'icon' => $icon,
        ];
    }

    /**
     * Create pre-configured address fields with panel
     */
    public function createAddressFieldGroup(array $pages = ['detail', 'form'], array $options = []): array
    {
        $panelName = $options['panelName'] ?? 'address_panel';
        $panelLabel = $options['panelLabel'] ?? $this->translator->trans('Address Information');
        $panelIcon = $options['panelIcon'] ?? 'fas fa-map-marker-alt';
        $collapsible = $options['collapsible'] ?? ['form'];
        
        return [
            $this->createPanelConfig($panelName, $panelLabel, $pages, $panelIcon, $collapsible),
            $this->createFieldConfig('street', 'text', $pages, $this->translator->trans('Street Address')),
            $this->createFieldConfig('zip', 'text', $pages, $this->translator->trans('ZIP/Postal Code')),
            $this->createFieldConfig('city', 'text', array_merge($pages, ['index']), $this->translator->trans('City')),
            $this->createCountryFieldConfig('countryCode', array_merge($pages, ['index']), $this->translator->trans('Country')),
        ];
    }

    /**
     * Create pre-configured communication fields with panel
     */
    public function createCommunicationFieldGroup(array $pages = ['detail', 'form'], array $options = []): array
    {
        $panelName = $options['panelName'] ?? 'communication_panel';
        $panelLabel = $options['panelLabel'] ?? $this->translator->trans('Communication');
        $panelIcon = $options['panelIcon'] ?? 'fas fa-phone';
        $collapsible = $options['collapsible'] ?? ['form'];
        
        return [
            $this->createPanelConfig($panelName, $panelLabel, $pages, $panelIcon, $collapsible),
            $this->createFieldConfig('email', 'email', array_merge($pages, ['index']), $this->translator->trans('Email Address'), [
                'indexLabel' => $this->translator->trans('Email')
            ]),
            $this->createFieldConfig('phone', 'telephone', $pages, $this->translator->trans('Phone Number')),
            $this->createFieldConfig('cell', 'telephone', $pages, $this->translator->trans('Mobile/Cell Phone')),
            $this->createFieldConfig('url', 'url', array_merge($pages, ['index']), $this->translator->trans('Website')),
        ];
    }

    /**
     * Create a country field with flag-only display in index view
     */
    public function createCountryFieldConfig(string $fieldName, array $pages = ['index', 'detail', 'form'], string $label = 'Country'): array
    {
        return $this->field($fieldName)
            ->type('country')
            ->label($this->translator->trans($label))
            ->pages($pages)
            ->option('showFlagOnly', in_array('index', $pages)) // Custom option to show flag only in index
            ->build();
    }

    /**
     * Create a basic ID field with smart defaults
     */
    public function createIdField(): array
    {
        return $this->createFieldConfig('id', 'id', ['detail'], 'ID', [
            'hideOnForm' => true,
            'hideOnIndex' => true,
        ]);
    }

    /**
     * Create a name field with smart defaults
     */
    public function createNameField(string $label = 'Name', bool $required = true, array $pages = ['index', 'detail', 'form']): array
    {
        return $this->createFieldConfig('name', 'text', $pages, $label, [
            'required' => $required,
        ]);
    }

    /**
     * Create a name field with link to show action
     */
    public function createNameFieldWithLink(string $label = 'Name', bool $required = true, array $pages = ['index', 'detail', 'form'], string $controllerClass = null): array
    {
        return $this->createFieldConfig('name', 'text', $pages, $label, [
            'required' => $required,
            'linkToShow' => true,
            'linkToShowController' => $controllerClass,
        ]);
    }

    /**
     * Create an association field with count display for index
     */
    public function createAssociationWithCount(
        string $fieldName,
        string $label,
        string $targetEntity,
        $choiceLabel = 'name',
        array $pages = ['index', 'detail', 'form']
    ): array {
        return $this->createFieldConfig($fieldName, 'association', $pages, $label, [
            'multiple' => true,
            'indexFormat' => 'count',
            'countLabel' => $label,
            'targetEntity' => $targetEntity,
            'choiceLabel' => $choiceLabel,
        ]);
    }

    /**
     * Builder pattern for complex field configurations
     */
    public function field(string $name, string $type = null): FieldConfigBuilder
    {
        return new FieldConfigBuilder($name, $type, $this);
    }

    /**
     * Create multiple field configurations at once
     */
    public function createFields(array $fieldDefinitions): array
    {
        $configs = [];
        foreach ($fieldDefinitions as $definition) {
            if (is_string($definition)) {
                // Simple field name only - auto-detect type
                $configs[] = $this->autoDetectField($definition);
            } elseif (is_array($definition)) {
                // Full configuration array
                $configs[] = $definition;
            }
        }
        return $configs;
    }

    /**
     * Auto-detect field type and create basic configuration
     */
    private function autoDetectField(string $fieldName): array
    {
        $type = match(true) {
            str_contains($fieldName, 'email') => 'email',
            str_contains($fieldName, 'phone') || str_contains($fieldName, 'cell') => 'telephone',
            str_contains($fieldName, 'url') || str_contains($fieldName, 'website') => 'url',
            str_contains($fieldName, 'country') => 'country',
            str_contains($fieldName, 'date') => 'date',
            str_contains($fieldName, 'time') => 'datetime',
            str_contains($fieldName, 'active') || str_contains($fieldName, 'enabled') => 'boolean',
            $fieldName === 'id' => 'id',
            default => 'text',
        };

        $pages = $fieldName === 'id' 
            ? ['detail'] 
            : ['index', 'detail', 'form'];

        $options = $fieldName === 'id' 
            ? ['hideOnForm' => true, 'hideOnIndex' => true]
            : [];

        return $this->createFieldConfig($fieldName, $type, $pages, ucfirst($fieldName), $options);
    }

    /**
     * Validate field configuration and provide helpful error messages
     */
    public function validateFieldConfiguration(array $config): array
    {
        $errors = [];
        
        // Check required fields
        if (!isset($config['name']) || empty($config['name'])) {
            $errors[] = "Field configuration must include a 'name' property";
        }
        
        if (!isset($config['type']) || empty($config['type'])) {
            $errors[] = "Field configuration must include a 'type' property";
        }
        
        // Validate field type
        if (isset($config['type'])) {
            $validTypes = ['id', 'text', 'textarea', 'email', 'telephone', 'url', 'country', 
                          'association', 'boolean', 'integer', 'number', 'money', 'date', 
                          'datetime', 'time', 'choice', 'image', 'panel'];
            
            if (!in_array($config['type'], $validTypes)) {
                $errors[] = "Invalid field type '{$config['type']}'. Valid types: " . implode(', ', $validTypes);
            }
        }
        
        // Validate pages
        if (isset($config['pages'])) {
            $validPages = ['index', 'detail', 'form'];
            $invalidPages = array_diff($config['pages'], $validPages);
            if (!empty($invalidPages)) {
                $errors[] = "Invalid page(s): " . implode(', ', $invalidPages) . ". Valid pages: " . implode(', ', $validPages);
            }
        }
        
        // Validate columns
        if (isset($config['columns']) && ($config['columns'] < 1 || $config['columns'] > 12)) {
            $errors[] = "Column width must be between 1 and 12, got: {$config['columns']}";
        }
        
        // Association-specific validation
        if ($config['type'] === 'association' && isset($config['multiple']) && $config['multiple']) {
            if (!isset($config['targetEntity'])) {
                $errors[] = "Association field with multiple=true requires 'targetEntity' option";
            }
        }
        
        return $errors;
    }

    /**
     * Generate fields with validation
     */
    public function generateFieldsWithValidation(array $fieldConfigurations, string $pageName, ?callable $activeFieldCallback = null): array
    {
        $allErrors = [];
        
        // Validate all configurations first
        foreach ($fieldConfigurations as $index => $config) {
            if (is_array($config)) {
                $errors = $this->validateFieldConfiguration($config);
                if (!empty($errors)) {
                    $fieldName = $config['name'] ?? "field at index {$index}";
                    $allErrors[$fieldName] = $errors;
                }
            }
        }
        
        // If there are validation errors, throw exception with details
        if (!empty($allErrors)) {
            $errorMessage = "Field configuration errors:\n";
            foreach ($allErrors as $fieldName => $errors) {
                $errorMessage .= "- {$fieldName}: " . implode(', ', $errors) . "\n";
            }
            throw new \InvalidArgumentException($errorMessage);
        }
        
        // If validation passes, generate fields normally
        return $this->generateFields($fieldConfigurations, $pageName, $activeFieldCallback);
    }

    /**
     * Configure field to link to show action
     */
    private function configureLinkToShow(object $field, array $config): void
    {
        $controllerClass = $config['linkToShowController'] ?? null;
        
        $field->formatValue(function ($value, $entity) use ($controllerClass) {
            if (empty($value)) {
                return $value;
            }
            
            // If no specific controller is provided, try to auto-detect from entity
            if (!$controllerClass) {
                $controllerClass = $this->getControllerClassFromEntity($entity);
            }
            
            if ($controllerClass) {
                try {
                    $showUrl = $this->adminUrlGenerator
                        ->setController($controllerClass)
                        ->setAction(Action::DETAIL)
                        ->setEntityId($entity->getId())
                        ->generateUrl();
                    
                    return sprintf('<a href="%s" class="text-decoration-none">%s</a>', $showUrl, $value);
                } catch (\Exception $e) {
                    // If URL generation fails, return the original value
                    return $value;
                }
            }
            
            return $value;
        })->renderAsHtml();
    }

    /**
     * Auto-detect controller class from entity
     */
    private function getControllerClassFromEntity(object $entity): ?string
    {
        $entityClass = get_class($entity);
        $entityName = substr($entityClass, strrpos($entityClass, '\\') + 1);
        
        // Convention: App\Controller\Admin\{EntityName}CrudController
        $controllerClass = "App\\Controller\\Admin\\{$entityName}CrudController";
        
        if (class_exists($controllerClass)) {
            return $controllerClass;
        }
        
        return null;
    }

    /**
     * Create field based on schema configuration
     */
    public function createFieldFromSchema(array $config): mixed
    {
        $field = match ($config['type']) {
            'id' => IdField::new($config['property'], $config['label']),
            'text' => TextField::new($config['property'], $config['label']),
            'textarea' => TextareaField::new($config['property'], $config['label']),
            'email' => EmailField::new($config['property'], $config['label']),
            'telephone' => TelephoneField::new($config['property'], $config['label']),
            'url' => UrlField::new($config['property'], $config['label']),
            'boolean' => BooleanField::new($config['property'], $config['label']),
            'datetime' => DateTimeField::new($config['property'], $config['label']),
            'date' => DateField::new($config['property'], $config['label']),
            'time' => TimeField::new($config['property'], $config['label']),
            'country' => CountryField::new($config['property'], $config['label']),
            'association' => AssociationField::new($config['property'], $config['label']),
            'number' => NumberField::new($config['property'], $config['label']),
            'integer' => IntegerField::new($config['property'], $config['label']),
            'money' => MoneyField::new($config['property'], $config['label']),
            'percent' => PercentField::new($config['property'], $config['label']),
            'choice' => ChoiceField::new($config['property'], $config['label']),
            'image' => ImageField::new($config['property'], $config['label']),
            default => TextField::new($config['property'], $config['label'])
        };

        return $this->applySchemaOptions($field, $config);
    }

    /**
     * Apply field options based on schema configuration
     */
    private function applySchemaOptions(mixed $field, array $config): mixed
    {
        // Standard options
        if (isset($config['required']) && $config['required']) {
            $field->setRequired(true);
        }

        if (isset($config['help'])) {
            $field->setHelp($config['help']);
        }

        if (isset($config['linkToShow']) && $config['linkToShow']) {
            $field->setTemplateName('admin/field/text_link.html.twig');
        }

        // Page visibility
        if (isset($config['pages'])) {
            $this->applyPageVisibility($field, $config['pages']);
        }

        // Association specific options
        if ($config['type'] === 'association') {
            if (isset($config['multiple'])) {
                $field->setFormTypeOption('multiple', $config['multiple']);
            }

            if (isset($config['autocomplete']) && $config['autocomplete']) {
                $field->autocomplete();
            }

            // Handle embedded table
            if (isset($config['embedded_table']) && $this->embeddedTableService) {
                $field->formatValue(
                    $this->embeddedTableService->createEmbeddedTableFormatter(
                        $config['embedded_table']['columns'],
                        $config['embedded_table']['title'],
                        $config['embedded_table']['empty_message'] ?? null
                    )
                );
            }
        }

        // Boolean specific options
        if ($config['type'] === 'boolean') {
            $field->renderAsSwitch(false);
        }

        // DateTime specific options
        if (in_array($config['type'], ['datetime', 'date', 'time'])) {
            if (isset($config['format'])) {
                $field->setFormat($config['format']);
            } elseif ($config['type'] === 'datetime') {
                $field->setFormat('dd/MM/yyyy HH:mm');
            }
        }

        // Text specific options
        if (in_array($config['type'], ['text', 'textarea'])) {
            if (isset($config['truncate'])) {
                $field->setMaxLength($config['truncate']);
            }
        }

        // Choice specific options
        if ($config['type'] === 'choice' && isset($config['choices'])) {
            $field->setChoices($config['choices']);
        }

        return $field;
    }

    /**
     * Apply page visibility to field
     */
    private function applyPageVisibility(mixed $field, array $pages): void
    {
        $allPages = ['index', 'detail', 'form', 'edit', 'new'];
        $hiddenPages = array_diff($allPages, $pages);

        foreach ($hiddenPages as $page) {
            switch ($page) {
                case 'index':
                    $field->hideOnIndex();
                    break;
                case 'detail':
                    $field->hideOnDetail();
                    break;
                case 'form':
                    $field->hideOnForm();
                    break;
                case 'edit':
                    $field->onlyWhenCreating();
                    break;
                case 'new':
                    $field->onlyWhenUpdating();
                    break;
            }
        }
    }

    /**
     * Create a standard set of fields from schema configuration
     */
    public function createFieldsFromSchema(array $fieldSchema, string $page = null): array
    {
        $fields = [];

        foreach ($fieldSchema as $fieldConfig) {
            // Skip if page filter is specified and field is not for this page
            if ($page && !in_array($page, $fieldConfig['pages'])) {
                continue;
            }

            $fields[] = $this->createFieldFromSchema($fieldConfig);
        }

        return $fields;
    }

    /**
     * Create tab structure with fields
     */
    public function createTabsFromSchema(array $tabSchema): array
    {
        $tabs = [];

        foreach ($tabSchema as $tabConfig) {
            $tabFields = $this->createFieldsFromSchema($tabConfig['fields'], 'detail');
            
            if (!empty($tabFields)) {
                $tabs[] = [
                    'id' => $tabConfig['id'],
                    'label' => $tabConfig['label'],
                    'fields' => $tabFields
                ];
            }
        }

        return $tabs;
    }

    /**
     * Quick method for standard entity fields
     */
    public function createStandardEntityFields(string $entityName): array
    {
        return [
            IdField::new('id', 'ID')->onlyOnDetail(),
            BooleanField::new('active', 'Active')->renderAsSwitch(false),
            TextField::new('name', ucfirst($entityName) . ' Name')
                ->setRequired(true),
            DateTimeField::new('createdAt', 'Created At')
                ->setFormat('dd/MM/yyyy HH:mm')
                ->onlyOnDetail(),
            DateTimeField::new('updatedAt', 'Updated At')
                ->setFormat('dd/MM/yyyy HH:mm')
                ->onlyOnDetail(),
        ];
    }

    /**
     * Create address field group
     */
    public function createAddressFieldsGroup(): array
    {
        return [
            TextField::new('street', 'Street Address'),
            TextField::new('zip', 'ZIP/Postal Code'),
            TextField::new('city', 'City'),
            CountryField::new('countryCode', 'Country'),
        ];
    }

    /**
     * Create contact field group
     */
    public function createContactFieldsGroup(): array
    {
        return [
            EmailField::new('email', 'Email Address'),
            TelephoneField::new('phone', 'Phone Number'),
            TelephoneField::new('cell', 'Mobile/Cell Phone'),
            UrlField::new('url', 'Website'),
        ];
    }
}
