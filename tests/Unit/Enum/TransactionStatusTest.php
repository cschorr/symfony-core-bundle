<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Enum;

use C3net\CoreBundle\Enum\TransactionStatus;
use PHPUnit\Framework\TestCase;

class TransactionStatusTest extends TestCase
{
    public function testEnumExists(): void
    {
        $this->assertTrue(enum_exists(TransactionStatus::class));
    }

    public function testEnumIsBackedByString(): void
    {
        $reflection = new \ReflectionEnum(TransactionStatus::class);
        $this->assertSame('string', $reflection->getBackingType()?->getName());
    }

    public function testAllCasesExist(): void
    {
        $this->assertSame('draft', TransactionStatus::DRAFT->value);
        $this->assertSame('quoted', TransactionStatus::QUOTED->value);
        $this->assertSame('ordered', TransactionStatus::ORDERED->value);
        $this->assertSame('in_production', TransactionStatus::IN_PRODUCTION->value);
        $this->assertSame('delivered', TransactionStatus::DELIVERED->value);
        $this->assertSame('invoiced', TransactionStatus::INVOICED->value);
        $this->assertSame('paid', TransactionStatus::PAID->value);
        $this->assertSame('cancelled', TransactionStatus::CANCELLED->value);
    }

    public function testFromString(): void
    {
        $this->assertSame(TransactionStatus::DRAFT, TransactionStatus::from('draft'));
        $this->assertSame(TransactionStatus::QUOTED, TransactionStatus::from('quoted'));
        $this->assertSame(TransactionStatus::ORDERED, TransactionStatus::from('ordered'));
        $this->assertSame(TransactionStatus::IN_PRODUCTION, TransactionStatus::from('in_production'));
        $this->assertSame(TransactionStatus::DELIVERED, TransactionStatus::from('delivered'));
        $this->assertSame(TransactionStatus::INVOICED, TransactionStatus::from('invoiced'));
        $this->assertSame(TransactionStatus::PAID, TransactionStatus::from('paid'));
        $this->assertSame(TransactionStatus::CANCELLED, TransactionStatus::from('cancelled'));
    }

    public function testTryFromString(): void
    {
        $this->assertSame(TransactionStatus::DRAFT, TransactionStatus::tryFrom('draft'));
        $this->assertSame(TransactionStatus::QUOTED, TransactionStatus::tryFrom('quoted'));
        $this->assertSame(TransactionStatus::ORDERED, TransactionStatus::tryFrom('ordered'));
        $this->assertNull(TransactionStatus::tryFrom('invalid_status'));
    }

    public function testFromStringThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        TransactionStatus::from('invalid_status');
    }

    public function testCases(): void
    {
        $cases = TransactionStatus::cases();

        $this->assertCount(8, $cases);
        $this->assertContainsOnlyInstancesOf(TransactionStatus::class, $cases);

        $values = array_map(fn (TransactionStatus $status) => $status->value, $cases);
        $this->assertContains('draft', $values);
        $this->assertContains('quoted', $values);
        $this->assertContains('ordered', $values);
        $this->assertContains('in_production', $values);
        $this->assertContains('delivered', $values);
        $this->assertContains('invoiced', $values);
        $this->assertContains('paid', $values);
        $this->assertContains('cancelled', $values);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('Draft', TransactionStatus::DRAFT->getLabel());
        $this->assertSame('Quoted', TransactionStatus::QUOTED->getLabel());
        $this->assertSame('Ordered', TransactionStatus::ORDERED->getLabel());
        $this->assertSame('In Production', TransactionStatus::IN_PRODUCTION->getLabel());
        $this->assertSame('Delivered', TransactionStatus::DELIVERED->getLabel());
        $this->assertSame('Invoiced', TransactionStatus::INVOICED->getLabel());
        $this->assertSame('Paid', TransactionStatus::PAID->getLabel());
        $this->assertSame('Cancelled', TransactionStatus::CANCELLED->getLabel());
    }

    public function testGetBadgeClass(): void
    {
        $this->assertSame('secondary', TransactionStatus::DRAFT->getBadgeClass());
        $this->assertSame('info', TransactionStatus::QUOTED->getBadgeClass());
        $this->assertSame('primary', TransactionStatus::ORDERED->getBadgeClass());
        $this->assertSame('warning', TransactionStatus::IN_PRODUCTION->getBadgeClass());
        $this->assertSame('success', TransactionStatus::DELIVERED->getBadgeClass());
        $this->assertSame('info', TransactionStatus::INVOICED->getBadgeClass());
        $this->assertSame('success', TransactionStatus::PAID->getBadgeClass());
        $this->assertSame('danger', TransactionStatus::CANCELLED->getBadgeClass());
    }

    public function testLabelsAreHumanReadable(): void
    {
        foreach (TransactionStatus::cases() as $status) {
            $label = $status->getLabel();

            $this->assertIsString($label);
            $this->assertNotEmpty($label);
            // Allow uppercase letters after spaces for multi-word labels like "In Production"
            $this->assertMatchesRegularExpression('/^[A-Z][A-Za-z\s]*$/', $label, "Label '{$label}' should be properly formatted");
        }
    }

    public function testBadgeClassesAreValidBootstrapClasses(): void
    {
        $validClasses = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];

        foreach (TransactionStatus::cases() as $status) {
            $badgeClass = $status->getBadgeClass();

            $this->assertIsString($badgeClass);
            $this->assertContains($badgeClass, $validClasses, "Badge class '{$badgeClass}' should be a valid Bootstrap class");
        }
    }

    public function testStatusProgression(): void
    {
        // Test logical transaction status progression
        $normalFlow = [
            TransactionStatus::DRAFT,
            TransactionStatus::QUOTED,
            TransactionStatus::ORDERED,
            TransactionStatus::IN_PRODUCTION,
            TransactionStatus::DELIVERED,
            TransactionStatus::INVOICED,
            TransactionStatus::PAID,
        ];

        foreach ($normalFlow as $status) {
            $this->assertInstanceOf(TransactionStatus::class, $status);
        }

        // Test alternative status
        $this->assertInstanceOf(TransactionStatus::class, TransactionStatus::CANCELLED);
    }

    public function testStatusComparison(): void
    {
        $status1 = TransactionStatus::DRAFT;
        $status2 = TransactionStatus::DRAFT;
        $status3 = TransactionStatus::PAID;

        $this->assertSame($status1, $status2);
        $this->assertNotSame($status1, $status3);
        $this->assertTrue($status1 === $status2);
        $this->assertFalse($status1 === $status3);
    }

    public function testAllStatusesHaveUniqueValues(): void
    {
        $values = array_map(fn (TransactionStatus $status) => $status->value, TransactionStatus::cases());
        $uniqueValues = array_unique($values);

        $this->assertCount(count($uniqueValues), $values, 'All status values should be unique');
    }

    public function testAllStatusesHaveUniqueLabels(): void
    {
        $labels = array_map(fn (TransactionStatus $status) => $status->getLabel(), TransactionStatus::cases());
        $uniqueLabels = array_unique($labels);

        $this->assertCount(count($uniqueLabels), $labels, 'All status labels should be unique');
    }

    public function testEnumSerialization(): void
    {
        $status = TransactionStatus::QUOTED;

        // Test JSON serialization
        $json = json_encode($status);
        $this->assertSame('"quoted"', $json);

        // Test string value
        $this->assertSame('quoted', $status->value);
    }

    public function testMatchExpressionCompleteness(): void
    {
        // Test that getLabel() handles all cases
        foreach (TransactionStatus::cases() as $status) {
            $label = $status->getLabel();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }

        // Test that getBadgeClass() handles all cases
        foreach (TransactionStatus::cases() as $status) {
            $badgeClass = $status->getBadgeClass();
            $this->assertIsString($badgeClass);
            $this->assertNotEmpty($badgeClass);
        }
    }

    public function testStatusSemantics(): void
    {
        // Test that status values make semantic sense
        $this->assertStringContainsString('draft', TransactionStatus::DRAFT->value);
        $this->assertStringContainsString('quoted', TransactionStatus::QUOTED->value);
        $this->assertStringContainsString('ordered', TransactionStatus::ORDERED->value);
        $this->assertStringContainsString('production', TransactionStatus::IN_PRODUCTION->value);
        $this->assertStringContainsString('delivered', TransactionStatus::DELIVERED->value);
        $this->assertStringContainsString('invoiced', TransactionStatus::INVOICED->value);
        $this->assertStringContainsString('paid', TransactionStatus::PAID->value);
        $this->assertStringContainsString('cancelled', TransactionStatus::CANCELLED->value);
    }

    public function testBadgeClassSemantics(): void
    {
        // Test that badge classes make semantic sense for transaction workflow
        $this->assertSame('secondary', TransactionStatus::DRAFT->getBadgeClass()); // Gray for draft
        $this->assertSame('info', TransactionStatus::QUOTED->getBadgeClass()); // Blue for information
        $this->assertSame('primary', TransactionStatus::ORDERED->getBadgeClass()); // Primary for active order
        $this->assertSame('warning', TransactionStatus::IN_PRODUCTION->getBadgeClass()); // Yellow for in progress
        $this->assertSame('success', TransactionStatus::DELIVERED->getBadgeClass()); // Green for success
        $this->assertSame('info', TransactionStatus::INVOICED->getBadgeClass()); // Blue for invoiced
        $this->assertSame('success', TransactionStatus::PAID->getBadgeClass()); // Green for paid
        $this->assertSame('danger', TransactionStatus::CANCELLED->getBadgeClass()); // Red for cancelled
    }

    public function testMethodReturnTypes(): void
    {
        $status = TransactionStatus::IN_PRODUCTION;

        $this->assertIsString($status->getLabel());
        $this->assertIsString($status->getBadgeClass());
        $this->assertIsString($status->value);
    }

    public function testCompleteTransactionWorkflow(): void
    {
        // Test complete transaction status workflow
        $statuses = [
            [TransactionStatus::DRAFT, 'Draft', 'secondary'],
            [TransactionStatus::QUOTED, 'Quoted', 'info'],
            [TransactionStatus::ORDERED, 'Ordered', 'primary'],
            [TransactionStatus::IN_PRODUCTION, 'In Production', 'warning'],
            [TransactionStatus::DELIVERED, 'Delivered', 'success'],
            [TransactionStatus::INVOICED, 'Invoiced', 'info'],
            [TransactionStatus::PAID, 'Paid', 'success'],
            [TransactionStatus::CANCELLED, 'Cancelled', 'danger'],
        ];

        foreach ($statuses as [$status, $expectedLabel, $expectedBadgeClass]) {
            $this->assertSame($expectedLabel, $status->getLabel());
            $this->assertSame($expectedBadgeClass, $status->getBadgeClass());
        }
    }
}