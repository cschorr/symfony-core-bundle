<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Enum;

use C3net\CoreBundle\Enum\ProjectPriority;
use PHPUnit\Framework\TestCase;

class ProjectPriorityTest extends TestCase
{
    public function testEnumExists(): void
    {
        $this->assertTrue(enum_exists(ProjectPriority::class));
    }

    public function testEnumIsBackedByString(): void
    {
        $reflection = new \ReflectionEnum(ProjectPriority::class);
        $this->assertSame('string', $reflection->getBackingType()?->getName());
    }

    public function testAllCasesExist(): void
    {
        $this->assertSame('low', ProjectPriority::LOW->value);
        $this->assertSame('medium', ProjectPriority::MEDIUM->value);
        $this->assertSame('high', ProjectPriority::HIGH->value);
        $this->assertSame('urgent', ProjectPriority::URGENT->value);
        $this->assertSame('critical', ProjectPriority::CRITICAL->value);
    }

    public function testFromString(): void
    {
        $this->assertSame(ProjectPriority::LOW, ProjectPriority::from('low'));
        $this->assertSame(ProjectPriority::MEDIUM, ProjectPriority::from('medium'));
        $this->assertSame(ProjectPriority::HIGH, ProjectPriority::from('high'));
        $this->assertSame(ProjectPriority::URGENT, ProjectPriority::from('urgent'));
        $this->assertSame(ProjectPriority::CRITICAL, ProjectPriority::from('critical'));
    }

    public function testTryFromString(): void
    {
        $this->assertSame(ProjectPriority::LOW, ProjectPriority::tryFrom('low'));
        $this->assertSame(ProjectPriority::CRITICAL, ProjectPriority::tryFrom('critical'));
        $this->assertNull(ProjectPriority::tryFrom('invalid_priority'));
    }

    public function testFromStringThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        ProjectPriority::from('invalid_priority');
    }

    public function testCases(): void
    {
        $cases = ProjectPriority::cases();

        $this->assertCount(5, $cases);
        $this->assertContainsOnlyInstancesOf(ProjectPriority::class, $cases);

        $values = array_map(fn (ProjectPriority $priority) => $priority->value, $cases);
        $this->assertContains('low', $values);
        $this->assertContains('medium', $values);
        $this->assertContains('high', $values);
        $this->assertContains('urgent', $values);
        $this->assertContains('critical', $values);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('Low', ProjectPriority::LOW->getLabel());
        $this->assertSame('Medium', ProjectPriority::MEDIUM->getLabel());
        $this->assertSame('High', ProjectPriority::HIGH->getLabel());
        $this->assertSame('Urgent', ProjectPriority::URGENT->getLabel());
        $this->assertSame('Critical', ProjectPriority::CRITICAL->getLabel());
    }

    public function testGetBadgeClass(): void
    {
        $this->assertSame('secondary', ProjectPriority::LOW->getBadgeClass());
        $this->assertSame('info', ProjectPriority::MEDIUM->getBadgeClass());
        $this->assertSame('primary', ProjectPriority::HIGH->getBadgeClass());
        $this->assertSame('warning', ProjectPriority::URGENT->getBadgeClass());
        $this->assertSame('danger', ProjectPriority::CRITICAL->getBadgeClass());
    }

    public function testGetSortOrder(): void
    {
        $this->assertSame(1, ProjectPriority::LOW->getSortOrder());
        $this->assertSame(2, ProjectPriority::MEDIUM->getSortOrder());
        $this->assertSame(3, ProjectPriority::HIGH->getSortOrder());
        $this->assertSame(4, ProjectPriority::URGENT->getSortOrder());
        $this->assertSame(5, ProjectPriority::CRITICAL->getSortOrder());
    }

    public function testLabelsAreHumanReadable(): void
    {
        foreach (ProjectPriority::cases() as $priority) {
            $label = $priority->getLabel();

            $this->assertIsString($label);
            $this->assertNotEmpty($label);
            $this->assertMatchesRegularExpression('/^[A-Z][a-z]*$/', $label, "Label '{$label}' should be properly formatted");
        }
    }

    public function testBadgeClassesAreValidBootstrapClasses(): void
    {
        $validClasses = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];

        foreach (ProjectPriority::cases() as $priority) {
            $badgeClass = $priority->getBadgeClass();

            $this->assertIsString($badgeClass);
            $this->assertContains($badgeClass, $validClasses, "Badge class '{$badgeClass}' should be a valid Bootstrap class");
        }
    }

    public function testPriorityComparison(): void
    {
        $priority1 = ProjectPriority::HIGH;
        $priority2 = ProjectPriority::HIGH;
        $priority3 = ProjectPriority::LOW;

        $this->assertSame($priority1, $priority2);
        $this->assertNotSame($priority1, $priority3);
        $this->assertTrue($priority1 === $priority2);
        $this->assertFalse($priority1 === $priority3);
    }

    public function testAllPrioritiesHaveUniqueValues(): void
    {
        $values = array_map(fn (ProjectPriority $priority) => $priority->value, ProjectPriority::cases());
        $uniqueValues = array_unique($values);

        $this->assertCount(count($uniqueValues), $values, 'All priority values should be unique');
    }

    public function testAllPrioritiesHaveUniqueLabels(): void
    {
        $labels = array_map(fn (ProjectPriority $priority) => $priority->getLabel(), ProjectPriority::cases());
        $uniqueLabels = array_unique($labels);

        $this->assertCount(count($uniqueLabels), $labels, 'All priority labels should be unique');
    }

    public function testAllPrioritiesHaveUniqueSortOrders(): void
    {
        $sortOrders = array_map(fn (ProjectPriority $priority) => $priority->getSortOrder(), ProjectPriority::cases());
        $uniqueSortOrders = array_unique($sortOrders);

        $this->assertCount(count($uniqueSortOrders), $sortOrders, 'All priority sort orders should be unique');
    }

    public function testEnumSerialization(): void
    {
        $priority = ProjectPriority::HIGH;

        // Test JSON serialization
        $json = json_encode($priority);
        $this->assertSame('"high"', $json);

        // Test string value
        $this->assertSame('high', $priority->value);
    }

    public function testMatchExpressionCompleteness(): void
    {
        // Test that getLabel() handles all cases
        foreach (ProjectPriority::cases() as $priority) {
            $label = $priority->getLabel();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }

        // Test that getBadgeClass() handles all cases
        foreach (ProjectPriority::cases() as $priority) {
            $badgeClass = $priority->getBadgeClass();
            $this->assertIsString($badgeClass);
            $this->assertNotEmpty($badgeClass);
        }

        // Test that getSortOrder() handles all cases
        foreach (ProjectPriority::cases() as $priority) {
            $sortOrder = $priority->getSortOrder();
            $this->assertIsInt($sortOrder);
            $this->assertGreaterThan(0, $sortOrder);
        }
    }

    public function testPrioritySemantics(): void
    {
        // Test that priority values make semantic sense
        $this->assertStringContainsString('low', ProjectPriority::LOW->value);
        $this->assertStringContainsString('medium', ProjectPriority::MEDIUM->value);
        $this->assertStringContainsString('high', ProjectPriority::HIGH->value);
        $this->assertStringContainsString('urgent', ProjectPriority::URGENT->value);
        $this->assertStringContainsString('critical', ProjectPriority::CRITICAL->value);
    }

    public function testBadgeClassSemantics(): void
    {
        // Test that badge classes make semantic sense
        $this->assertSame('secondary', ProjectPriority::LOW->getBadgeClass()); // Gray for low priority
        $this->assertSame('info', ProjectPriority::MEDIUM->getBadgeClass()); // Light blue for medium
        $this->assertSame('primary', ProjectPriority::HIGH->getBadgeClass()); // Blue for high
        $this->assertSame('warning', ProjectPriority::URGENT->getBadgeClass()); // Yellow for urgent
        $this->assertSame('danger', ProjectPriority::CRITICAL->getBadgeClass()); // Red for critical
    }

    public function testMethodReturnTypes(): void
    {
        $priority = ProjectPriority::HIGH;

        $this->assertIsString($priority->getLabel());
        $this->assertIsString($priority->getBadgeClass());
        $this->assertIsInt($priority->getSortOrder());
        $this->assertIsString($priority->value);
    }

    public function testCompletePriorityScale(): void
    {
        // Test complete project priority scale
        $testCases = [
            ['priority' => ProjectPriority::LOW, 'label' => 'Low', 'badgeClass' => 'secondary', 'sortOrder' => 1],
            ['priority' => ProjectPriority::MEDIUM, 'label' => 'Medium', 'badgeClass' => 'info', 'sortOrder' => 2],
            ['priority' => ProjectPriority::HIGH, 'label' => 'High', 'badgeClass' => 'primary', 'sortOrder' => 3],
            ['priority' => ProjectPriority::URGENT, 'label' => 'Urgent', 'badgeClass' => 'warning', 'sortOrder' => 4],
            ['priority' => ProjectPriority::CRITICAL, 'label' => 'Critical', 'badgeClass' => 'danger', 'sortOrder' => 5],
        ];

        foreach ($testCases as $testCase) {
            $priority = $testCase['priority'];
            $this->assertSame($testCase['label'], $priority->getLabel());
            $this->assertSame($testCase['badgeClass'], $priority->getBadgeClass());
            $this->assertSame($testCase['sortOrder'], $priority->getSortOrder());
        }
    }

    public function testSortOrderIsAscending(): void
    {
        $priorities = ProjectPriority::cases();
        $sortOrders = array_map(fn (ProjectPriority $priority) => $priority->getSortOrder(), $priorities);

        // Check that sort orders form an ascending sequence
        $sortedOrders = $sortOrders;
        sort($sortedOrders);

        $this->assertSame($sortedOrders, $sortOrders, 'Sort orders should be in ascending order');
    }

    public function testPriorityOrdering(): void
    {
        // Test that priorities can be compared using sort order
        $this->assertLessThan(ProjectPriority::HIGH->getSortOrder(), ProjectPriority::MEDIUM->getSortOrder());
        $this->assertLessThan(ProjectPriority::URGENT->getSortOrder(), ProjectPriority::HIGH->getSortOrder());
        $this->assertLessThan(ProjectPriority::CRITICAL->getSortOrder(), ProjectPriority::URGENT->getSortOrder());
    }
}
