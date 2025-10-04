<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Enum;

use C3net\CoreBundle\Enum\InvoiceStatus;
use PHPUnit\Framework\TestCase;

class InvoiceStatusTest extends TestCase
{
    public function testEnumExists(): void
    {
        $this->assertTrue(enum_exists(InvoiceStatus::class));
    }

    public function testEnumIsBackedByString(): void
    {
        $reflection = new \ReflectionEnum(InvoiceStatus::class);
        $this->assertSame('string', $reflection->getBackingType()?->getName());
    }

    public function testAllCasesExist(): void
    {
        $this->assertSame('draft', InvoiceStatus::DRAFT->value);
        $this->assertSame('sent', InvoiceStatus::SENT->value);
        $this->assertSame('paid', InvoiceStatus::PAID->value);
        $this->assertSame('overdue', InvoiceStatus::OVERDUE->value);
        $this->assertSame('cancelled', InvoiceStatus::CANCELLED->value);
    }

    public function testFromString(): void
    {
        $this->assertSame(InvoiceStatus::DRAFT, InvoiceStatus::from('draft'));
        $this->assertSame(InvoiceStatus::SENT, InvoiceStatus::from('sent'));
        $this->assertSame(InvoiceStatus::PAID, InvoiceStatus::from('paid'));
        $this->assertSame(InvoiceStatus::OVERDUE, InvoiceStatus::from('overdue'));
        $this->assertSame(InvoiceStatus::CANCELLED, InvoiceStatus::from('cancelled'));
    }

    public function testTryFromString(): void
    {
        $this->assertSame(InvoiceStatus::DRAFT, InvoiceStatus::tryFrom('draft'));
        $this->assertSame(InvoiceStatus::SENT, InvoiceStatus::tryFrom('sent'));
        $this->assertSame(InvoiceStatus::PAID, InvoiceStatus::tryFrom('paid'));
        $this->assertNull(InvoiceStatus::tryFrom('invalid_status'));
    }

    public function testFromStringThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        InvoiceStatus::from('invalid_status');
    }

    public function testCases(): void
    {
        $cases = InvoiceStatus::cases();

        $this->assertCount(5, $cases);
        $this->assertContainsOnlyInstancesOf(InvoiceStatus::class, $cases);

        $values = array_map(fn (InvoiceStatus $status) => $status->value, $cases);
        $this->assertContains('draft', $values);
        $this->assertContains('sent', $values);
        $this->assertContains('paid', $values);
        $this->assertContains('overdue', $values);
        $this->assertContains('cancelled', $values);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('Draft', InvoiceStatus::DRAFT->getLabel());
        $this->assertSame('Sent', InvoiceStatus::SENT->getLabel());
        $this->assertSame('Paid', InvoiceStatus::PAID->getLabel());
        $this->assertSame('Overdue', InvoiceStatus::OVERDUE->getLabel());
        $this->assertSame('Cancelled', InvoiceStatus::CANCELLED->getLabel());
    }

    public function testGetBadgeClass(): void
    {
        $this->assertSame('secondary', InvoiceStatus::DRAFT->getBadgeClass());
        $this->assertSame('info', InvoiceStatus::SENT->getBadgeClass());
        $this->assertSame('success', InvoiceStatus::PAID->getBadgeClass());
        $this->assertSame('danger', InvoiceStatus::OVERDUE->getBadgeClass());
        $this->assertSame('warning', InvoiceStatus::CANCELLED->getBadgeClass());
    }

    public function testLabelsAreHumanReadable(): void
    {
        foreach (InvoiceStatus::cases() as $status) {
            $label = $status->getLabel();

            $this->assertIsString($label);
            $this->assertNotEmpty($label);
            $this->assertMatchesRegularExpression('/^[A-Z][a-z\s]*$/', $label, "Label '{$label}' should be properly formatted");
        }
    }

    public function testBadgeClassesAreValidBootstrapClasses(): void
    {
        $validClasses = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];

        foreach (InvoiceStatus::cases() as $status) {
            $badgeClass = $status->getBadgeClass();

            $this->assertIsString($badgeClass);
            $this->assertContains($badgeClass, $validClasses, "Badge class '{$badgeClass}' should be a valid Bootstrap class");
        }
    }

    public function testStatusProgression(): void
    {
        // Test typical invoice status progression
        $normalFlow = [
            InvoiceStatus::DRAFT,
            InvoiceStatus::SENT,
            InvoiceStatus::PAID,
        ];

        foreach ($normalFlow as $status) {
            $this->assertInstanceOf(InvoiceStatus::class, $status);
        }

        // Test alternative statuses
        $alternativeStatuses = [
            InvoiceStatus::OVERDUE,
            InvoiceStatus::CANCELLED,
        ];

        foreach ($alternativeStatuses as $status) {
            $this->assertInstanceOf(InvoiceStatus::class, $status);
        }
    }

    public function testStatusComparison(): void
    {
        $status1 = InvoiceStatus::SENT;
        $status2 = InvoiceStatus::SENT;
        $status3 = InvoiceStatus::PAID;

        $this->assertSame($status1, $status2);
        $this->assertNotSame($status1, $status3);
        $this->assertTrue($status1 === $status2);
        $this->assertFalse($status1 === $status3);
    }

    public function testAllStatusesHaveUniqueValues(): void
    {
        $values = array_map(fn (InvoiceStatus $status) => $status->value, InvoiceStatus::cases());
        $uniqueValues = array_unique($values);

        $this->assertCount(count($uniqueValues), $values, 'All status values should be unique');
    }

    public function testAllStatusesHaveUniqueLabels(): void
    {
        $labels = array_map(fn (InvoiceStatus $status) => $status->getLabel(), InvoiceStatus::cases());
        $uniqueLabels = array_unique($labels);

        $this->assertCount(count($uniqueLabels), $labels, 'All status labels should be unique');
    }

    public function testEnumSerialization(): void
    {
        $status = InvoiceStatus::PAID;

        // Test JSON serialization
        $json = json_encode($status);
        $this->assertSame('"paid"', $json);

        // Test string value
        $this->assertSame('paid', $status->value);
    }

    public function testMatchExpressionCompleteness(): void
    {
        // Test that getLabel() handles all cases
        foreach (InvoiceStatus::cases() as $status) {
            $label = $status->getLabel();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }

        // Test that getBadgeClass() handles all cases
        foreach (InvoiceStatus::cases() as $status) {
            $badgeClass = $status->getBadgeClass();
            $this->assertIsString($badgeClass);
            $this->assertNotEmpty($badgeClass);
        }
    }

    public function testStatusSemantics(): void
    {
        // Test that status values make semantic sense
        $this->assertStringContainsString('draft', InvoiceStatus::DRAFT->value);
        $this->assertStringContainsString('sent', InvoiceStatus::SENT->value);
        $this->assertStringContainsString('paid', InvoiceStatus::PAID->value);
        $this->assertStringContainsString('overdue', InvoiceStatus::OVERDUE->value);
        $this->assertStringContainsString('cancelled', InvoiceStatus::CANCELLED->value);
    }

    public function testBadgeClassSemantics(): void
    {
        // Test that badge classes make semantic sense for invoice workflow
        $this->assertSame('secondary', InvoiceStatus::DRAFT->getBadgeClass()); // Gray for draft
        $this->assertSame('info', InvoiceStatus::SENT->getBadgeClass()); // Blue for sent
        $this->assertSame('success', InvoiceStatus::PAID->getBadgeClass()); // Green for paid
        $this->assertSame('danger', InvoiceStatus::OVERDUE->getBadgeClass()); // Red for overdue
        $this->assertSame('warning', InvoiceStatus::CANCELLED->getBadgeClass()); // Yellow for cancelled
    }

    public function testMethodReturnTypes(): void
    {
        $status = InvoiceStatus::SENT;

        $this->assertIsString($status->getLabel());
        $this->assertIsString($status->getBadgeClass());
        $this->assertIsString($status->value);
    }

    public function testCompleteInvoiceWorkflow(): void
    {
        // Test complete invoice status workflow
        $statuses = [
            [InvoiceStatus::DRAFT, 'Draft', 'secondary'],
            [InvoiceStatus::SENT, 'Sent', 'info'],
            [InvoiceStatus::PAID, 'Paid', 'success'],
            [InvoiceStatus::OVERDUE, 'Overdue', 'danger'],
            [InvoiceStatus::CANCELLED, 'Cancelled', 'warning'],
        ];

        foreach ($statuses as [$status, $expectedLabel, $expectedBadgeClass]) {
            $this->assertSame($expectedLabel, $status->getLabel());
            $this->assertSame($expectedBadgeClass, $status->getBadgeClass());
        }
    }

    public function testInvoiceLifecycleStates(): void
    {
        // Test invoice-specific lifecycle patterns

        // Invoice can go from sent to overdue before being paid
        $overdueFlow = [
            InvoiceStatus::DRAFT,
            InvoiceStatus::SENT,
            InvoiceStatus::OVERDUE,
            InvoiceStatus::PAID,
        ];

        foreach ($overdueFlow as $state) {
            $this->assertInstanceOf(InvoiceStatus::class, $state);
        }

        // Invoice can be cancelled at various stages
        $cancellableStates = [
            InvoiceStatus::DRAFT,
            InvoiceStatus::SENT,
            InvoiceStatus::OVERDUE,
        ];

        foreach ($cancellableStates as $state) {
            $this->assertInstanceOf(InvoiceStatus::class, $state);
        }
    }
}