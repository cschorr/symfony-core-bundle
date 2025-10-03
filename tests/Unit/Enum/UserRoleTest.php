<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Enum;

use C3net\CoreBundle\Enum\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function testEnumExists(): void
    {
        $this->assertTrue(enum_exists(UserRole::class));
    }

    public function testEnumIsBackedByString(): void
    {
        $reflection = new \ReflectionEnum(UserRole::class);
        $this->assertSame('string', $reflection->getBackingType()?->getName());
    }

    public function testBasicRoles(): void
    {
        $this->assertSame('ROLE_USER', UserRole::ROLE_USER->value);
        $this->assertSame('ROLE_ADMIN', UserRole::ROLE_ADMIN->value);
        $this->assertSame('ROLE_MODERATOR', UserRole::ROLE_MODERATOR->value);
        $this->assertSame('ROLE_SUPER_ADMIN', UserRole::ROLE_SUPER_ADMIN->value);
        $this->assertSame('ROLE_ALLOWED_TO_SWITCH', UserRole::ROLE_ALLOWED_TO_SWITCH->value);
    }

    public function testBusinessRoles(): void
    {
        $this->assertSame('ROLE_MANAGER', UserRole::ROLE_MANAGER->value);
        $this->assertSame('ROLE_TEAMLEAD', UserRole::ROLE_TEAMLEAD->value);
        $this->assertSame('ROLE_FINANCE', UserRole::ROLE_FINANCE->value);
        $this->assertSame('ROLE_QUALITY', UserRole::ROLE_QUALITY->value);
        $this->assertSame('ROLE_PROJECT_MANAGEMENT', UserRole::ROLE_PROJECT_MANAGEMENT->value);
        $this->assertSame('ROLE_EDITOR', UserRole::ROLE_EDITOR->value);
        $this->assertSame('ROLE_EXTERNAL', UserRole::ROLE_EXTERNAL->value);
    }

    public function testCustomRoles(): void
    {
        $this->assertSame('ROLE_CONTENT_CREATOR', UserRole::ROLE_CONTENT_CREATOR->value);
    }

    public function testFromString(): void
    {
        $this->assertSame(UserRole::ROLE_USER, UserRole::from('ROLE_USER'));
        $this->assertSame(UserRole::ROLE_ADMIN, UserRole::from('ROLE_ADMIN'));
        $this->assertSame(UserRole::ROLE_MANAGER, UserRole::from('ROLE_MANAGER'));
        $this->assertSame(UserRole::ROLE_CONTENT_CREATOR, UserRole::from('ROLE_CONTENT_CREATOR'));
    }

    public function testTryFromString(): void
    {
        $this->assertSame(UserRole::ROLE_USER, UserRole::tryFrom('ROLE_USER'));
        $this->assertSame(UserRole::ROLE_ADMIN, UserRole::tryFrom('ROLE_ADMIN'));
        $this->assertNull(UserRole::tryFrom('INVALID_ROLE'));
    }

    public function testFromStringThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        UserRole::from('INVALID_ROLE');
    }

    public function testCases(): void
    {
        $cases = UserRole::cases();

        $this->assertIsArray($cases);
        $this->assertNotEmpty($cases);

        // Test that all cases are UserRole instances
        foreach ($cases as $case) {
            $this->assertInstanceOf(UserRole::class, $case);
        }

        // Test specific cases are present
        $caseValues = array_map(fn (UserRole $role) => $role->value, $cases);
        $this->assertContains('ROLE_USER', $caseValues);
        $this->assertContains('ROLE_ADMIN', $caseValues);
        $this->assertContains('ROLE_MANAGER', $caseValues);
        $this->assertContains('ROLE_CONTENT_CREATOR', $caseValues);
    }

    public function testValuesMethod(): void
    {
        $values = UserRole::values();

        $this->assertIsArray($values);
        $this->assertNotEmpty($values);

        // Test that all values are strings
        foreach ($values as $value) {
            $this->assertIsString($value);
        }

        // Test specific values are present
        $this->assertContains('ROLE_USER', $values);
        $this->assertContains('ROLE_ADMIN', $values);
        $this->assertContains('ROLE_MANAGER', $values);
        $this->assertContains('ROLE_TEAMLEAD', $values);
        $this->assertContains('ROLE_FINANCE', $values);
        $this->assertContains('ROLE_QUALITY', $values);
        $this->assertContains('ROLE_PROJECT_MANAGEMENT', $values);
        $this->assertContains('ROLE_EDITOR', $values);
        $this->assertContains('ROLE_EXTERNAL', $values);
        $this->assertContains('ROLE_CONTENT_CREATOR', $values);
        $this->assertContains('ROLE_SUPER_ADMIN', $values);
        $this->assertContains('ROLE_MODERATOR', $values);
        $this->assertContains('ROLE_ALLOWED_TO_SWITCH', $values);
    }

    public function testValuesMethodReturnsAllCaseValues(): void
    {
        $cases = UserRole::cases();
        $values = UserRole::values();

        $this->assertCount(count($cases), $values);

        foreach ($cases as $case) {
            $this->assertContains($case->value, $values);
        }
    }

    public function testRoleHierarchy(): void
    {
        // Test that certain roles exist for hierarchy
        $adminRoles = [
            UserRole::ROLE_SUPER_ADMIN,
            UserRole::ROLE_ADMIN,
            UserRole::ROLE_ALLOWED_TO_SWITCH,
        ];

        foreach ($adminRoles as $role) {
            $this->assertInstanceOf(UserRole::class, $role);
        }
    }

    public function testBusinessDomainRoles(): void
    {
        // Test business-specific roles
        $businessRoles = [
            UserRole::ROLE_MANAGER,
            UserRole::ROLE_TEAMLEAD,
            UserRole::ROLE_FINANCE,
            UserRole::ROLE_QUALITY,
            UserRole::ROLE_PROJECT_MANAGEMENT,
            UserRole::ROLE_EDITOR,
            UserRole::ROLE_EXTERNAL,
            UserRole::ROLE_CONTENT_CREATOR,
        ];

        foreach ($businessRoles as $role) {
            $this->assertInstanceOf(UserRole::class, $role);
            $this->assertStringStartsWith('ROLE_', $role->value);
        }
    }

    public function testAllRolesStartWithRolePrefix(): void
    {
        foreach (UserRole::cases() as $role) {
            $this->assertStringStartsWith('ROLE_', $role->value);
        }
    }

    public function testNoEmptyRoles(): void
    {
        foreach (UserRole::cases() as $role) {
            $this->assertNotEmpty($role->value);
            $this->assertGreaterThan(5, strlen($role->value)); // At least 'ROLE_X'
        }
    }

    public function testRoleUniqueness(): void
    {
        $values = UserRole::values();
        $uniqueValues = array_unique($values);

        $this->assertCount(count($uniqueValues), $values, 'All role values should be unique');
    }

    public function testSpecificRoleCount(): void
    {
        $expectedRoles = [
            'ROLE_USER',
            'ROLE_ADMIN',
            'ROLE_MODERATOR',
            'ROLE_SUPER_ADMIN',
            'ROLE_ALLOWED_TO_SWITCH',
            'ROLE_MANAGER',
            'ROLE_TEAMLEAD',
            'ROLE_FINANCE',
            'ROLE_QUALITY',
            'ROLE_PROJECT_MANAGEMENT',
            'ROLE_EDITOR',
            'ROLE_EXTERNAL',
            'ROLE_CONTENT_CREATOR',
        ];

        $this->assertCount(count($expectedRoles), UserRole::cases());
    }

    public function testEnumSerialization(): void
    {
        $role = UserRole::ROLE_ADMIN;

        // Test JSON serialization
        $json = json_encode($role);
        $this->assertSame('"ROLE_ADMIN"', $json);

        // Test string casting
        $this->assertSame('ROLE_ADMIN', (string) $role->value);
    }

    public function testRoleComparison(): void
    {
        $role1 = UserRole::ROLE_ADMIN;
        $role2 = UserRole::ROLE_ADMIN;
        $role3 = UserRole::ROLE_USER;

        $this->assertSame($role1, $role2);
        $this->assertNotSame($role1, $role3);
        $this->assertTrue($role1 === $role2);
        $this->assertFalse($role1 === $role3);
    }

    public function testMethodReturnTypes(): void
    {
        $values = UserRole::values();

        // Test return type annotation
        $this->assertIsArray($values);

        // Verify it returns list<string> as documented
        $this->assertTrue(array_is_list($values));
        foreach ($values as $value) {
            $this->assertIsString($value);
        }
    }
}
