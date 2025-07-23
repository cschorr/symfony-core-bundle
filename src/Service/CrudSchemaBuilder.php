<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Service to help build standardized CRUD controller configurations.
 */
class CrudSchemaBuilder
{
    public function __construct(
        private readonly EasyAdminFieldService $fieldService,
    ) {
    }

    /**
     * Create a standard field configuration array.
     */
    public function createField(
        string $property,
        string $type,
        string $label,
        array $pages = ['index', 'detail', 'form'],
        array $options = [],
    ): array {
        return array_merge([
            'property' => $property,
            'type' => $type,
            'label' => $label,
            'pages' => $pages,
        ], $options);
    }

    /**
     * Create standard index fields for most entities.
     */
    public function createStandardIndexFields(string $entityName, array $customFields = []): array
    {
        $standardFields = [
            $this->createField('active', 'boolean', 'Active', ['index']),
            $this->createField('name', 'text', ucfirst($entityName) . ' Name', ['index'], [
                'required' => true,
                'linkToShow' => true,
            ]),
            $this->createField('createdAt', 'datetime', 'Created', ['index']),
        ];

        return array_merge($standardFields, $customFields);
    }

    /**
     * Create a tab configuration.
     */
    public function createTab(string $id, string $label, array $fields): array
    {
        return [
            'id' => $id,
            'label' => $label,
            'fields' => $fields,
        ];
    }

    /**
     * Create standard information tab.
     */
    public function createInfoTab(string $entityName, array $customFields = []): array
    {
        $standardFields = [
            $this->createField('active', 'boolean', 'Active', ['detail', 'form']),
            $this->createField('id', 'id', 'ID', ['detail']),
            $this->createField('name', 'text', ucfirst($entityName) . ' Name', ['detail', 'form'], [
                'required' => true,
            ]),
            $this->createField('createdAt', 'datetime', 'Created At', ['detail']),
            $this->createField('updatedAt', 'datetime', 'Updated At', ['detail']),
        ];

        return $this->createTab(
            strtolower($entityName) . '_info_tab',
            ucfirst($entityName) . ' Information',
            array_merge($standardFields, $customFields)
        );
    }

    /**
     * Create embedded table field configuration.
     */
    public function createEmbeddedTableField(
        string $property,
        string $label,
        array $columns,
        string $tableTitle,
        ?string $emptyMessage = null,
    ): array {
        return $this->createField($property, 'association', $label, ['detail'], [
            'embedded_table' => [
                'columns' => $columns,
                'title' => $tableTitle,
                'empty_message' => $emptyMessage ?? 'No ' . strtolower($tableTitle) . ' assigned',
            ],
        ]);
    }

    /**
     * Create association field for forms.
     */
    public function createAssociationField(
        string $property,
        string $label,
        bool $multiple = false,
        bool $autocomplete = true,
    ): array {
        return $this->createField($property, 'association', $label, ['form'], [
            'multiple' => $multiple,
            'autocomplete' => $autocomplete,
        ]);
    }

    /**
     * Create standard address fields.
     */
    public function createAddressFields(array $pages = ['detail', 'form']): array
    {
        return [
            $this->createField('street', 'text', 'Street Address', $pages),
            $this->createField('zip', 'text', 'ZIP/Postal Code', $pages),
            $this->createField('city', 'text', 'City', $pages),
            $this->createField('countryCode', 'country', 'Country', $pages),
        ];
    }

    /**
     * Create standard contact fields.
     */
    public function createContactFields(array $pages = ['detail', 'form']): array
    {
        return [
            $this->createField('email', 'email', 'Email Address', $pages),
            $this->createField('phone', 'telephone', 'Phone Number', $pages),
            $this->createField('cell', 'telephone', 'Mobile/Cell Phone', $pages),
            $this->createField('url', 'url', 'Website', $pages),
        ];
    }

    /**
     * Quick builder for relationship tabs.
     */
    public function createRelationshipTab(
        string $property,
        string $entityName,
        array $tableColumns,
        bool $includeFormField = true,
    ): array {
        $tab = $this->createTab(
            $property . '_tab',
            ucfirst($entityName),
            [
                $this->createEmbeddedTableField(
                    $property,
                    ucfirst($entityName),
                    $tableColumns,
                    ucfirst($entityName)
                ),
            ]
        );

        if ($includeFormField) {
            $tab['fields'][] = $this->createAssociationField(
                $property,
                ucfirst($entityName),
                true,
                true
            );
        }

        return $tab;
    }
}
