<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Enum;

use C3net\CoreBundle\Enum\Gender;
use PHPUnit\Framework\TestCase;

class GenderTest extends TestCase
{
    public function testEnumExists(): void
    {
        $this->assertTrue(enum_exists(Gender::class));
    }

    public function testEnumIsBackedByString(): void
    {
        $reflection = new \ReflectionEnum(Gender::class);
        $this->assertSame('string', $reflection->getBackingType()?->getName());
    }

    public function testAllCasesExist(): void
    {
        $this->assertSame('male', Gender::MALE->value);
        $this->assertSame('female', Gender::FEMALE->value);
        $this->assertSame('non_binary', Gender::NON_BINARY->value);
        $this->assertSame('diverse', Gender::DIVERSE->value);
        $this->assertSame('prefer_not_to_say', Gender::PREFER_NOT_TO_SAY->value);
    }

    public function testFromString(): void
    {
        $this->assertSame(Gender::MALE, Gender::from('male'));
        $this->assertSame(Gender::FEMALE, Gender::from('female'));
        $this->assertSame(Gender::NON_BINARY, Gender::from('non_binary'));
        $this->assertSame(Gender::DIVERSE, Gender::from('diverse'));
        $this->assertSame(Gender::PREFER_NOT_TO_SAY, Gender::from('prefer_not_to_say'));
    }

    public function testTryFromString(): void
    {
        $this->assertSame(Gender::MALE, Gender::tryFrom('male'));
        $this->assertSame(Gender::FEMALE, Gender::tryFrom('female'));
        $this->assertNull(Gender::tryFrom('invalid_gender'));
    }

    public function testFromStringThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        Gender::from('invalid_gender');
    }

    public function testCases(): void
    {
        $cases = Gender::cases();

        $this->assertCount(5, $cases);
        $this->assertContainsOnlyInstancesOf(Gender::class, $cases);

        $values = array_map(fn (Gender $gender) => $gender->value, $cases);
        $this->assertContains('male', $values);
        $this->assertContains('female', $values);
        $this->assertContains('non_binary', $values);
        $this->assertContains('diverse', $values);
        $this->assertContains('prefer_not_to_say', $values);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('Male', Gender::MALE->getLabel());
        $this->assertSame('Female', Gender::FEMALE->getLabel());
        $this->assertSame('Non-Binary', Gender::NON_BINARY->getLabel());
        $this->assertSame('Diverse', Gender::DIVERSE->getLabel());
        $this->assertSame('Prefer Not to Say', Gender::PREFER_NOT_TO_SAY->getLabel());
    }

    public function testGetPronoun(): void
    {
        $this->assertSame('he/him', Gender::MALE->getPronoun());
        $this->assertSame('she/her', Gender::FEMALE->getPronoun());
        $this->assertSame('they/them', Gender::NON_BINARY->getPronoun());
        $this->assertSame('they/them', Gender::DIVERSE->getPronoun());
        $this->assertSame('they/them', Gender::PREFER_NOT_TO_SAY->getPronoun());
    }

    public function testGetSalutation(): void
    {
        $this->assertSame('Mr.', Gender::MALE->getSalutation());
        $this->assertSame('Ms.', Gender::FEMALE->getSalutation());
        $this->assertSame('Mx.', Gender::NON_BINARY->getSalutation());
        $this->assertSame('Mx.', Gender::DIVERSE->getSalutation());
        $this->assertSame('', Gender::PREFER_NOT_TO_SAY->getSalutation());
    }

    public function testLabelsAreHumanReadable(): void
    {
        foreach (Gender::cases() as $gender) {
            $label = $gender->getLabel();

            $this->assertIsString($label);
            $this->assertNotEmpty($label);
            $this->assertMatchesRegularExpression('/^[A-Z]/', $label, "Label '{$label}' should start with uppercase");
        }
    }

    public function testGenderComparison(): void
    {
        $gender1 = Gender::MALE;
        $gender2 = Gender::MALE;
        $gender3 = Gender::FEMALE;

        $this->assertSame($gender1, $gender2);
        $this->assertNotSame($gender1, $gender3);
        $this->assertTrue($gender1 === $gender2);
        $this->assertFalse($gender1 === $gender3);
    }

    public function testAllGendersHaveUniqueValues(): void
    {
        $values = array_map(fn (Gender $gender) => $gender->value, Gender::cases());
        $uniqueValues = array_unique($values);

        $this->assertCount(count($uniqueValues), $values, 'All gender values should be unique');
    }

    public function testAllGendersHaveUniqueLabels(): void
    {
        $labels = array_map(fn (Gender $gender) => $gender->getLabel(), Gender::cases());
        $uniqueLabels = array_unique($labels);

        $this->assertCount(count($uniqueLabels), $labels, 'All gender labels should be unique');
    }

    public function testEnumSerialization(): void
    {
        $gender = Gender::FEMALE;

        // Test JSON serialization
        $json = json_encode($gender);
        $this->assertSame('"female"', $json);

        // Test string value
        $this->assertSame('female', $gender->value);
    }

    public function testMatchExpressionCompleteness(): void
    {
        // Test that getLabel() handles all cases
        foreach (Gender::cases() as $gender) {
            $label = $gender->getLabel();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }

        // Test that getPronoun() handles all cases
        foreach (Gender::cases() as $gender) {
            $pronoun = $gender->getPronoun();
            $this->assertIsString($pronoun);
            $this->assertNotEmpty($pronoun);
        }

        // Test that getSalutation() handles all cases
        foreach (Gender::cases() as $gender) {
            $salutation = $gender->getSalutation();
            $this->assertIsString($salutation);
        }
    }

    public function testGenderSemantics(): void
    {
        // Test that gender values make semantic sense
        $this->assertStringContainsString('male', Gender::MALE->value);
        $this->assertStringContainsString('female', Gender::FEMALE->value);
        $this->assertStringContainsString('non_binary', Gender::NON_BINARY->value);
        $this->assertStringContainsString('diverse', Gender::DIVERSE->value);
    }

    public function testMethodReturnTypes(): void
    {
        $gender = Gender::MALE;

        $this->assertIsString($gender->getLabel());
        $this->assertIsString($gender->getPronoun());
        $this->assertIsString($gender->getSalutation());
        $this->assertIsString($gender->value);
    }

    public function testInclusiveGenderOptions(): void
    {
        // Test that modern, inclusive gender options are available
        $genders = Gender::cases();

        $this->assertContains(Gender::MALE, $genders);
        $this->assertContains(Gender::FEMALE, $genders);
        $this->assertContains(Gender::NON_BINARY, $genders);
        $this->assertContains(Gender::DIVERSE, $genders);
        $this->assertContains(Gender::PREFER_NOT_TO_SAY, $genders);
    }

    public function testNonBinaryPronouns(): void
    {
        // Test that non-binary and diverse genders use inclusive pronouns
        $this->assertSame('they/them', Gender::NON_BINARY->getPronoun());
        $this->assertSame('they/them', Gender::DIVERSE->getPronoun());
        $this->assertSame('Mx.', Gender::NON_BINARY->getSalutation());
        $this->assertSame('Mx.', Gender::DIVERSE->getSalutation());
    }

    public function testPreferNotToSayRespected(): void
    {
        // Test that prefer not to say option is handled respectfully
        $this->assertSame('Prefer Not to Say', Gender::PREFER_NOT_TO_SAY->getLabel());
        $this->assertSame('they/them', Gender::PREFER_NOT_TO_SAY->getPronoun());
        $this->assertSame('', Gender::PREFER_NOT_TO_SAY->getSalutation());
    }

    public function testPronounFormats(): void
    {
        // Test that all pronouns follow the format "x/y" or are empty
        foreach (Gender::cases() as $gender) {
            $pronoun = $gender->getPronoun();

            if (!empty($pronoun)) {
                $this->assertMatchesRegularExpression('/^[a-z]+\/[a-z]+$/', $pronoun, "Pronoun '{$pronoun}' should follow format 'x/y'");
            }
        }
    }

    public function testSalutationFormats(): void
    {
        // Test that all non-empty salutations end with a period
        foreach (Gender::cases() as $gender) {
            $salutation = $gender->getSalutation();

            if (!empty($salutation)) {
                $this->assertStringEndsWith('.', $salutation, "Salutation '{$salutation}' should end with a period");
            }
        }
    }

    public function testCompleteGenderData(): void
    {
        // Test complete gender data structure
        $testCases = [
            ['gender' => Gender::MALE, 'label' => 'Male', 'pronoun' => 'he/him', 'salutation' => 'Mr.'],
            ['gender' => Gender::FEMALE, 'label' => 'Female', 'pronoun' => 'she/her', 'salutation' => 'Ms.'],
            ['gender' => Gender::NON_BINARY, 'label' => 'Non-Binary', 'pronoun' => 'they/them', 'salutation' => 'Mx.'],
            ['gender' => Gender::DIVERSE, 'label' => 'Diverse', 'pronoun' => 'they/them', 'salutation' => 'Mx.'],
            ['gender' => Gender::PREFER_NOT_TO_SAY, 'label' => 'Prefer Not to Say', 'pronoun' => 'they/them', 'salutation' => ''],
        ];

        foreach ($testCases as $testCase) {
            $gender = $testCase['gender'];
            $this->assertSame($testCase['label'], $gender->getLabel());
            $this->assertSame($testCase['pronoun'], $gender->getPronoun());
            $this->assertSame($testCase['salutation'], $gender->getSalutation());
        }
    }
}
