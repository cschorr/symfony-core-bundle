<?php

declare(strict_types=1);

namespace App\Service;

use App\Controller\Admin\ProjectCrudController;
use App\Controller\Admin\UserCrudController;
use App\Enum\ProjectStatus;
use Doctrine\Common\Collections\Collection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmbeddedTableService
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Create a formatted value function for embedding tables in EasyAdmin fields
     * This is a convenience method that returns a closure for use in field configurations.
     */
    public function createEmbeddedTableFormatter(array $columns, string $title, ?string $emptyMessage = null): \Closure
    {
        // Use translator for empty message if not provided
        if (null === $emptyMessage) {
            $defaultKey = 'No ' . strtolower($title) . ' assigned';
            $emptyMessage = $this->translator->trans($defaultKey);
        }

        return function ($value, $entity) use ($columns, $title, $emptyMessage) {
            if ($value instanceof Collection && $value->count() > 0) {
                return '<div class="w-100" style="width: 100% !important; max-width: 100% !important;">' .
                       $this->renderNativeEasyAdminTable($value, $columns, $title) .
                       '</div>';
            }

            return '<em class="text-muted">' . $emptyMessage . '</em>';
        };
    }

    /**
     * Render embedded table using native EasyAdmin table structure and classes
     * This mimics exactly how EasyAdmin renders its own index tables.
     */
    public function renderNativeEasyAdminTable(Collection $items, array $columns, string $title): string
    {
        if (0 === $items->count()) {
            return '<div class="empty-collection">
                        <div class="empty-collection-icon">
                            <i class="far fa-folder-open"></i>
                        </div>
                        <div class="empty-collection-text">
                            No ' . strtolower($title) . ' found
                        </div>
                    </div>';
        }

        $html = '<div class="content-section-body without-header without-footer w-100 p-0 m-0" style="width: 100% !important; max-width: 100% !important; padding: 0 !important; margin: 0 !important; overflow-x: auto !important;">';
        $html .= '<div class="table-responsive w-100 p-0 m-0" style="width: 100% !important; max-width: 100% !important; padding: 0 !important; margin: 0 !important;">';
        $html .= '<table class="table datagrid w-100 m-0" data-ea-selector="table" style="width: 100% !important; max-width: 100% !important; margin: 0 !important; table-layout: fixed !important;">';

        // Table header - exactly like EasyAdmin index pages
        $html .= '<thead>';
        $html .= '<tr>';
        $columnCount = count($columns);
        $columnWidth = floor(100 / $columnCount);
        foreach ($columns as $property => $label) {
            $translatedLabel = $this->translator->trans($label);
            $html .= '<th data-ea-property-name="' . $property . '" class="text text-start text-truncate" style="width: ' . $columnWidth . '% !important;">';
            $html .= '<span class="text-truncate">' . htmlspecialchars($translatedLabel) . '</span>';
            $html .= '</th>';
        }

        $html .= '</tr>';
        $html .= '</thead>';

        // Table body with native EasyAdmin styling
        $html .= '<tbody>';
        $columnCount = count($columns);
        $columnWidth = floor(100 / $columnCount);
        foreach ($items as $item) {
            $html .= '<tr data-ea-selector="item-row">';
            foreach ($columns as $property => $label) {
                $value = $this->getEntityPropertyValue($item, $property);
                $html .= '<td data-ea-property-name="' . $property . '" class="text text-start text-truncate" style="width: ' . $columnWidth . '% !important;">';
                $html .= $this->formatTableCellValue($value, $property, $item);
                $html .= '</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>'; // Close table-responsive

        // Add count info like native EasyAdmin
        $html .= '<div class="list-pagination-counter text-muted">';
        $html .= $this->translator->trans('Showing') . ' <strong>' . $items->count() . '</strong> ' . $this->translator->trans($title);
        $html .= '</div>';

        $html .= '</div>'; // Close content-section-body

        return $html;
    }

    /**
     * Get property value from entity using getter methods or toString.
     */
    private function getEntityPropertyValue(object $entity, string $property): mixed
    {
        // Special handling for common properties
        switch ($property) {
            case 'id':
                return method_exists($entity, 'getId') ? $entity->getId() : '-';
            case 'name':
                return method_exists($entity, 'getName') ? $entity->getName() : (string) $entity;
            case 'email':
                return method_exists($entity, 'getEmail') ? $entity->getEmail() : '-';
            case 'active':
                return method_exists($entity, 'isActive') ? $entity->isActive() : false;
            case 'status':
                return method_exists($entity, 'getStatus') ? $entity->getStatus() : '-';
            case 'createdAt':
                return method_exists($entity, 'getCreatedAt') ? $entity->getCreatedAt() : null;
            default:
                // Try getter method
                $getter = 'get' . ucfirst($property);
                if (method_exists($entity, $getter)) {
                    return $entity->$getter();
                }

                return '-';
        }
    }

    /**
     * Format table cell values with proper EasyAdmin styling.
     */
    private function formatTableCellValue(mixed $value, string $property, object $entity): string
    {
        switch ($property) {
            case 'id':
                // Handle enum values for ID field
                if ($value instanceof \BackedEnum) {
                    $value = $value->value;
                }

                return '<span class="field-id text-truncate">' . htmlspecialchars((string) $value) . '</span>';

            case 'active':
                $isActive = (bool) $value;

                return '<span class="badge badge-boolean badge-boolean-' . ($isActive ? 'true' : 'false') . '">'
                     . '<i class="fa fa-' . ($isActive ? 'check' : 'times') . '"></i>'
                     . '</span>';

            case 'status':
                if (empty($value) || '-' === $value) {
                    return '<span class="text-muted text-truncate">-</span>';
                }

                // Handle ProjectStatus enum directly
                if ($value instanceof ProjectStatus) {
                    return '<span class="badge bg-' . $value->getBadgeClass() . ' text-truncate">' . $value->getLabel() . '</span>';
                }

                // Convert any other value to enum (handles both legacy int and string values)
                $enum = null;
                if (is_numeric($value)) {
                    // Legacy numeric conversion
                    $enum = match ((int) $value) {
                        0 => ProjectStatus::PLANNING,
                        1 => ProjectStatus::IN_PROGRESS,
                        2 => ProjectStatus::ON_HOLD,
                        3 => ProjectStatus::COMPLETED,
                        4 => ProjectStatus::CANCELLED,
                        default => null,
                    };
                } elseif (is_string($value)) {
                    // String to enum conversion
                    $enum = ProjectStatus::tryFrom($value);
                }

                // Use enum if found, otherwise show unknown
                if ($enum) {
                    return '<span class="badge bg-' . $enum->getBadgeClass() . ' text-truncate">' . $enum->getLabel() . '</span>';
                }

                return '<span class="badge bg-secondary text-truncate">' . $this->translator->trans('Unknown') . '</span>';

            case 'email':
                if (empty($value)) {
                    return '<span class="text-muted text-truncate">-</span>';
                }

                // Create link to user detail page
                try {
                    $showUrl = $this->adminUrlGenerator
                        ->setController(UserCrudController::class)
                        ->setAction(Action::DETAIL)
                        ->setEntityId($entity->getId())
                        ->generateUrl();

                    return '<a href="' . $showUrl . '" class="text-decoration-none text-truncate">' . htmlspecialchars((string) $value) . '</a>';
                } catch (\Exception) {
                    return '<span class="text-truncate">' . htmlspecialchars((string) $value) . '</span>';
                }

            case 'name':
                if (empty($value)) {
                    return '<span class="text-muted text-truncate">-</span>';
                }

                // Create link to project detail page
                try {
                    $showUrl = $this->adminUrlGenerator
                        ->setController(ProjectCrudController::class)
                        ->setAction(Action::DETAIL)
                        ->setEntityId($entity->getId())
                        ->generateUrl();

                    return '<a href="' . $showUrl . '" class="text-decoration-none text-truncate">' . htmlspecialchars((string) $value) . '</a>';
                } catch (\Exception) {
                    return '<span class="text-truncate">' . htmlspecialchars((string) $value) . '</span>';
                }

            case 'createdAt':
                if ($value instanceof \DateTimeInterface) {
                    return '<span class="field-datetime text-truncate" title="' . $value->format('Y-m-d H:i:s') . '">'
                         . $value->format('M j, Y') . '</span>';
                }

                return '<span class="text-muted text-truncate">-</span>';

            default:
                if (empty($value)) {
                    return '<span class="text-muted text-truncate">-</span>';
                }

                // Handle enum values
                if ($value instanceof \BackedEnum) {
                    $value = $value->value;
                } elseif ($value instanceof \UnitEnum) {
                    $value = $value->name;
                }

                return '<span class="text-truncate">' . htmlspecialchars((string) $value) . '</span>';
        }
    }
}
