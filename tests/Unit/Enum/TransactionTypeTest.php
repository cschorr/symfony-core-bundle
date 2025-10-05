<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Enum;

use C3net\CoreBundle\Enum\TransactionType;
use PHPUnit\Framework\TestCase;

class TransactionTypeTest extends TestCase
{
    public function testEnumExists(): void
    {
        $this->assertTrue(enum_exists(TransactionType::class));
    }

    public function testEnumIsBackedByString(): void
    {
        $reflection = new \ReflectionEnum(TransactionType::class);
        $this->assertSame('string', $reflection->getBackingType()?->getName());
    }

    public function testAllCasesExist(): void
    {
        $this->assertSame('quote', TransactionType::QUOTE->value);
        $this->assertSame('order', TransactionType::ORDER->value);
        $this->assertSame('service', TransactionType::SERVICE->value);
        $this->assertSame('retainer', TransactionType::RETAINER->value);
        $this->assertSame('project', TransactionType::PROJECT->value);
        $this->assertSame('other', TransactionType::OTHER->value);
    }

    public function testFromString(): void
    {
        $this->assertSame(TransactionType::QUOTE, TransactionType::from('quote'));
        $this->assertSame(TransactionType::ORDER, TransactionType::from('order'));
        $this->assertSame(TransactionType::SERVICE, TransactionType::from('service'));
        $this->assertSame(TransactionType::RETAINER, TransactionType::from('retainer'));
        $this->assertSame(TransactionType::PROJECT, TransactionType::from('project'));
        $this->assertSame(TransactionType::OTHER, TransactionType::from('other'));
    }

    public function testTryFromString(): void
    {
        $this->assertSame(TransactionType::QUOTE, TransactionType::tryFrom('quote'));
        $this->assertSame(TransactionType::ORDER, TransactionType::tryFrom('order'));
        $this->assertSame(TransactionType::SERVICE, TransactionType::tryFrom('service'));
        $this->assertNull(TransactionType::tryFrom('invalid_type'));
    }

    public function testFromStringThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        TransactionType::from('invalid_type');
    }

    public function testCases(): void
    {
        $cases = TransactionType::cases();

        $this->assertCount(6, $cases);
        $this->assertContainsOnlyInstancesOf(TransactionType::class, $cases);

        $values = array_map(fn (TransactionType $type) => $type->value, $cases);
        $this->assertContains('quote', $values);
        $this->assertContains('order', $values);
        $this->assertContains('service', $values);
        $this->assertContains('retainer', $values);
        $this->assertContains('project', $values);
        $this->assertContains('other', $values);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('Quote', TransactionType::QUOTE->getLabel());
        $this->assertSame('Order', TransactionType::ORDER->getLabel());
        $this->assertSame('Service', TransactionType::SERVICE->getLabel());
        $this->assertSame('Retainer', TransactionType::RETAINER->getLabel());
        $this->assertSame('Project', TransactionType::PROJECT->getLabel());
        $this->assertSame('Other', TransactionType::OTHER->getLabel());
    }

    public function testLabelsAreHumanReadable(): void
    {
        foreach (TransactionType::cases() as $type) {
            $label = $type->getLabel();

            $this->assertIsString($label);
            $this->assertNotEmpty($label);
            $this->assertMatchesRegularExpression('/^[A-Z][a-z\s]*$/', $label, "Label '{$label}' should be properly formatted");
        }
    }

    public function testTypeComparison(): void
    {
        $type1 = TransactionType::ORDER;
        $type2 = TransactionType::ORDER;
        $type3 = TransactionType::PROJECT;

        $this->assertSame($type1, $type2);
        $this->assertNotSame($type1, $type3);
        $this->assertTrue($type1 === $type2);
        $this->assertFalse($type1 === $type3);
    }

    public function testAllTypesHaveUniqueValues(): void
    {
        $values = array_map(fn (TransactionType $type) => $type->value, TransactionType::cases());
        $uniqueValues = array_unique($values);

        $this->assertCount(count($uniqueValues), $values, 'All type values should be unique');
    }

    public function testAllTypesHaveUniqueLabels(): void
    {
        $labels = array_map(fn (TransactionType $type) => $type->getLabel(), TransactionType::cases());
        $uniqueLabels = array_unique($labels);

        $this->assertCount(count($uniqueLabels), $labels, 'All type labels should be unique');
    }

    public function testEnumSerialization(): void
    {
        $type = TransactionType::SERVICE;

        // Test JSON serialization
        $json = json_encode($type);
        $this->assertSame('"service"', $json);

        // Test string value
        $this->assertSame('service', $type->value);
    }

    public function testMatchExpressionCompleteness(): void
    {
        // Test that getLabel() handles all cases
        foreach (TransactionType::cases() as $type) {
            $label = $type->getLabel();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }

    public function testTypeSemantics(): void
    {
        // Test that type values make semantic sense
        $this->assertStringContainsString('quote', TransactionType::QUOTE->value);
        $this->assertStringContainsString('order', TransactionType::ORDER->value);
        $this->assertStringContainsString('service', TransactionType::SERVICE->value);
        $this->assertStringContainsString('retainer', TransactionType::RETAINER->value);
        $this->assertStringContainsString('project', TransactionType::PROJECT->value);
        $this->assertStringContainsString('other', TransactionType::OTHER->value);
    }

    public function testMethodReturnTypes(): void
    {
        $type = TransactionType::ORDER;

        $this->assertIsString($type->getLabel());
        $this->assertIsString($type->value);
    }

    public function testCompleteTransactionTypes(): void
    {
        // Test all transaction types
        $types = [
            [TransactionType::QUOTE, 'Quote'],
            [TransactionType::ORDER, 'Order'],
            [TransactionType::SERVICE, 'Service'],
            [TransactionType::RETAINER, 'Retainer'],
            [TransactionType::PROJECT, 'Project'],
            [TransactionType::OTHER, 'Other'],
        ];

        foreach ($types as [$type, $expectedLabel]) {
            $this->assertSame($expectedLabel, $type->getLabel());
        }
    }

    public function testTransactionTypeCategories(): void
    {
        // Test different categories of transaction types

        // One-time transactions
        $oneTimeTypes = [
            TransactionType::QUOTE,
            TransactionType::ORDER,
        ];

        // Ongoing/recurring transactions
        $recurringTypes = [
            TransactionType::SERVICE,
            TransactionType::RETAINER,
        ];

        // Project-based transactions
        $projectTypes = [
            TransactionType::PROJECT,
        ];

        // Catch-all type
        $otherType = TransactionType::OTHER;

        foreach ($oneTimeTypes as $type) {
            $this->assertInstanceOf(TransactionType::class, $type);
        }

        foreach ($recurringTypes as $type) {
            $this->assertInstanceOf(TransactionType::class, $type);
        }

        foreach ($projectTypes as $type) {
            $this->assertInstanceOf(TransactionType::class, $type);
        }

        $this->assertInstanceOf(TransactionType::class, $otherType);
    }
}
