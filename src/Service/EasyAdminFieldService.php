<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Symfony\Contracts\Translation\TranslatorInterface;

class EasyAdminFieldService
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    /**
     * Generate fields based on configuration array
     */
    public function generateFields(array $fieldConfigurations, string $pageName, ?callable $activeFieldCallback = null): array
    {
        $fields = [];
        
        foreach ($fieldConfigurations as $fieldConfig) {
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
     * Configure association fields
     */
    private function configureAssociationField(object $field, array $config, string $pageType): void
    {
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
     * Create a panel configuration
     */
    public function createPanelConfig(
        string $name,
        string $label,
        array $pages = ['detail', 'form'],
        ?string $icon = null,
        array $collapsibleOn = ['form']
    ): array {
        return [
            'name' => $name,
            'type' => 'panel',
            'pages' => $pages,
            'label' => $label,
            'icon' => $icon,
            'collapsible' => $collapsibleOn,
        ];
    }
}
