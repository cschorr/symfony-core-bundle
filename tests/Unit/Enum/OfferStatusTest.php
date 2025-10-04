<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Enum;

use C3net\CoreBundle\Enum\OfferStatus;
use PHPUnit\Framework\TestCase;

class OfferStatusTest extends TestCase
{
    public function testEnumExists(): void
    {
        $this->assertTrue(enum_exists(OfferStatus::class));
    }

    public function testEnumIsBackedByString(): void
    {
        $reflection = new \ReflectionEnum(OfferStatus::class);
        $this->assertSame('string', $reflection->getBackingType()?->getName());
    }

    public function testAllCasesExist(): void
    {
        $this->assertSame('draft', OfferStatus::DRAFT->value);
        $this->assertSame('sent', OfferStatus::SENT->value);
        $this->assertSame('accepted', OfferStatus::ACCEPTED->value);
        $this->assertSame('rejected', OfferStatus::REJECTED->value);
        $this->assertSame('expired', OfferStatus::EXPIRED->value);
    }

    public function testFromString(): void
    {
        $this->assertSame(OfferStatus::DRAFT, OfferStatus::from('draft'));
        $this->assertSame(OfferStatus::SENT, OfferStatus::from('sent'));
        $this->assertSame(OfferStatus::ACCEPTED, OfferStatus::from('accepted'));
        $this->assertSame(OfferStatus::REJECTED, OfferStatus::from('rejected'));
        $this->assertSame(OfferStatus::EXPIRED, OfferStatus::from('expired'));
    }

    public function testTryFromString(): void
    {
        $this->assertSame(OfferStatus::DRAFT, OfferStatus::tryFrom('draft'));
        $this->assertSame(OfferStatus::SENT, OfferStatus::tryFrom('sent'));
        $this->assertSame(OfferStatus::ACCEPTED, OfferStatus::tryFrom('accepted'));
        $this->assertNull(OfferStatus::tryFrom('invalid_status'));
    }

    public function testFromStringThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        OfferStatus::from('invalid_status');
    }

    public function testCases(): void
    {
        $cases = OfferStatus::cases();

        $this->assertCount(5, $cases);
        $this->assertContainsOnlyInstancesOf(OfferStatus::class, $cases);

        $values = array_map(fn (OfferStatus $status) => $status->value, $cases);
        $this->assertContains('draft', $values);
        $this->assertContains('sent', $values);
        $this->assertContains('accepted', $values);
        $this->assertContains('rejected', $values);
        $this->assertContains('expired', $values);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('Draft', OfferStatus::DRAFT->getLabel());
        $this->assertSame('Sent', OfferStatus::SENT->getLabel());
        $this->assertSame('Accepted', OfferStatus::ACCEPTED->getLabel());
        $this->assertSame('Rejected', OfferStatus::REJECTED->getLabel());
        $this->assertSame('Expired', OfferStatus::EXPIRED->getLabel());
    }

    public function testGetBadgeClass(): void
    {
        $this->assertSame('secondary', OfferStatus::DRAFT->getBadgeClass());
        $this->assertSame('info', OfferStatus::SENT->getBadgeClass());
        $this->assertSame('success', OfferStatus::ACCEPTED->getBadgeClass());
        $this->assertSame('danger', OfferStatus::REJECTED->getBadgeClass());
        $this->assertSame('warning', OfferStatus::EXPIRED->getBadgeClass());
    }

    public function testLabelsAreHumanReadable(): void
    {
        foreach (OfferStatus::cases() as $status) {
            $label = $status->getLabel();

            $this->assertIsString($label);
            $this->assertNotEmpty($label);
            $this->assertMatchesRegularExpression('/^[A-Z][a-z\s]*$/', $label, "Label '{$label}' should be properly formatted");
        }
    }

    public function testBadgeClassesAreValidBootstrapClasses(): void
    {
        $validClasses = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];

        foreach (OfferStatus::cases() as $status) {
            $badgeClass = $status->getBadgeClass();

            $this->assertIsString($badgeClass);
            $this->assertContains($badgeClass, $validClasses, "Badge class '{$badgeClass}' should be a valid Bootstrap class");
        }
    }

    public function testStatusProgression(): void
    {
        // Test typical offer status progression
        $normalFlow = [
            OfferStatus::DRAFT,
            OfferStatus::SENT,
            OfferStatus::ACCEPTED,
        ];

        foreach ($normalFlow as $status) {
            $this->assertInstanceOf(OfferStatus::class, $status);
        }

        // Test alternative outcomes
        $alternativeOutcomes = [
            OfferStatus::REJECTED,
            OfferStatus::EXPIRED,
        ];

        foreach ($alternativeOutcomes as $status) {
            $this->assertInstanceOf(OfferStatus::class, $status);
        }
    }

    public function testStatusComparison(): void
    {
        $status1 = OfferStatus::SENT;
        $status2 = OfferStatus::SENT;
        $status3 = OfferStatus::ACCEPTED;

        $this->assertSame($status1, $status2);
        $this->assertNotSame($status1, $status3);
        $this->assertTrue($status1 === $status2);
        $this->assertFalse($status1 === $status3);
    }

    public function testAllStatusesHaveUniqueValues(): void
    {
        $values = array_map(fn (OfferStatus $status) => $status->value, OfferStatus::cases());
        $uniqueValues = array_unique($values);

        $this->assertCount(count($uniqueValues), $values, 'All status values should be unique');
    }

    public function testAllStatusesHaveUniqueLabels(): void
    {
        $labels = array_map(fn (OfferStatus $status) => $status->getLabel(), OfferStatus::cases());
        $uniqueLabels = array_unique($labels);

        $this->assertCount(count($uniqueLabels), $labels, 'All status labels should be unique');
    }

    public function testEnumSerialization(): void
    {
        $status = OfferStatus::ACCEPTED;

        // Test JSON serialization
        $json = json_encode($status);
        $this->assertSame('"accepted"', $json);

        // Test string value
        $this->assertSame('accepted', $status->value);
    }

    public function testMatchExpressionCompleteness(): void
    {
        // Test that getLabel() handles all cases
        foreach (OfferStatus::cases() as $status) {
            $label = $status->getLabel();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }

        // Test that getBadgeClass() handles all cases
        foreach (OfferStatus::cases() as $status) {
            $badgeClass = $status->getBadgeClass();
            $this->assertIsString($badgeClass);
            $this->assertNotEmpty($badgeClass);
        }
    }

    public function testStatusSemantics(): void
    {
        // Test that status values make semantic sense
        $this->assertStringContainsString('draft', OfferStatus::DRAFT->value);
        $this->assertStringContainsString('sent', OfferStatus::SENT->value);
        $this->assertStringContainsString('accepted', OfferStatus::ACCEPTED->value);
        $this->assertStringContainsString('rejected', OfferStatus::REJECTED->value);
        $this->assertStringContainsString('expired', OfferStatus::EXPIRED->value);
    }

    public function testBadgeClassSemantics(): void
    {
        // Test that badge classes make semantic sense for offer workflow
        $this->assertSame('secondary', OfferStatus::DRAFT->getBadgeClass()); // Gray for draft
        $this->assertSame('info', OfferStatus::SENT->getBadgeClass()); // Blue for sent
        $this->assertSame('success', OfferStatus::ACCEPTED->getBadgeClass()); // Green for accepted
        $this->assertSame('danger', OfferStatus::REJECTED->getBadgeClass()); // Red for rejected
        $this->assertSame('warning', OfferStatus::EXPIRED->getBadgeClass()); // Yellow for expired
    }

    public function testMethodReturnTypes(): void
    {
        $status = OfferStatus::SENT;

        $this->assertIsString($status->getLabel());
        $this->assertIsString($status->getBadgeClass());
        $this->assertIsString($status->value);
    }

    public function testCompleteOfferWorkflow(): void
    {
        // Test complete offer status workflow
        $statuses = [
            [OfferStatus::DRAFT, 'Draft', 'secondary'],
            [OfferStatus::SENT, 'Sent', 'info'],
            [OfferStatus::ACCEPTED, 'Accepted', 'success'],
            [OfferStatus::REJECTED, 'Rejected', 'danger'],
            [OfferStatus::EXPIRED, 'Expired', 'warning'],
        ];

        foreach ($statuses as [$status, $expectedLabel, $expectedBadgeClass]) {
            $this->assertSame($expectedLabel, $status->getLabel());
            $this->assertSame($expectedBadgeClass, $status->getBadgeClass());
        }
    }

    public function testOfferLifecycleStates(): void
    {
        // Test that offer can be in different final states
        $finalStates = [
            OfferStatus::ACCEPTED, // Success path
            OfferStatus::REJECTED, // Customer rejection
            OfferStatus::EXPIRED,  // Time expiration
        ];

        foreach ($finalStates as $state) {
            $this->assertInstanceOf(OfferStatus::class, $state);
        }
    }
}