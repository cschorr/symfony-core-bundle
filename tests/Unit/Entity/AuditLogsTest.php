<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Entity;

use C3net\CoreBundle\Entity\AuditLogs;
use C3net\CoreBundle\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class AuditLogsTest extends TestCase
{
    private AuditLogs $auditLog;

    protected function setUp(): void
    {
        $this->auditLog = new AuditLogs();
    }

    public function testConstructor(): void
    {
        $auditLog = new AuditLogs();

        // Test that AbstractEntity traits are properly initialized
        // Note: UUID is null until persisted by Doctrine
        $this->assertNull($auditLog->getId());
        $this->assertNotNull($auditLog->getCreatedAt());
        $this->assertNotNull($auditLog->getUpdatedAt());
        $this->assertTrue($auditLog->isActive());
    }

    public function testResourceProperty(): void
    {
        $resource = 'User';

        $this->auditLog->setResource($resource);

        $this->assertSame($resource, $this->auditLog->getResource());
    }

    public function testResourcePropertyAcceptsNull(): void
    {
        $this->auditLog->setResource(null);

        $this->assertNull($this->auditLog->getResource());
    }

    public function testMetaProperty(): void
    {
        $meta = 'Large JSON metadata with extensive information about the audit action';

        $this->auditLog->setMeta($meta);

        $this->assertSame($meta, $this->auditLog->getMeta());
    }

    public function testMetaPropertyAcceptsNull(): void
    {
        $this->auditLog->setMeta(null);

        $this->assertNull($this->auditLog->getMeta());
    }

    public function testMetaPropertyCanHandleLargeText(): void
    {
        // Test that meta can handle TEXT field size (up to 65,535 characters)
        $largeMeta = str_repeat('Large metadata content with JSON and other data. ', 1000);

        $this->auditLog->setMeta($largeMeta);

        $this->assertSame($largeMeta, $this->auditLog->getMeta());
        $this->assertGreaterThan(255, strlen($largeMeta)); // Ensure it's larger than VARCHAR(255)
    }

    public function testActionProperty(): void
    {
        $action = 'create';

        $this->auditLog->setAction($action);

        $this->assertSame($action, $this->auditLog->getAction());
    }

    public function testActionPropertyAcceptsNull(): void
    {
        $this->auditLog->setAction(null);

        $this->assertNull($this->auditLog->getAction());
    }

    public function testAuthorProperty(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $this->auditLog->setAuthor($user);

        $this->assertSame($user, $this->auditLog->getAuthor());
    }

    public function testAuthorPropertyAcceptsNull(): void
    {
        $this->auditLog->setAuthor(null);

        $this->assertNull($this->auditLog->getAuthor());
    }

    public function testDataProperty(): void
    {
        $data = '{"id": 123, "name": "Test Entity", "description": "A very long description that would exceed VARCHAR(255) limits"}';

        $this->auditLog->setData($data);

        $this->assertSame($data, $this->auditLog->getData());
    }

    public function testDataPropertyAcceptsNull(): void
    {
        $this->auditLog->setData(null);

        $this->assertNull($this->auditLog->getData());
    }

    public function testDataPropertyCanHandleLargeText(): void
    {
        // Test that data can handle TEXT field size
        $largeData = json_encode([
            'entities' => array_fill(0, 100, [
                'id' => 'uuid-' . uniqid(),
                'name' => 'Entity name with some description',
                'description' => 'Long description that would normally exceed VARCHAR limits',
                'metadata' => array_fill(0, 10, 'additional data'),
            ]),
        ]);

        $this->auditLog->setData($largeData);

        $this->assertSame($largeData, $this->auditLog->getData());
        $this->assertGreaterThan(255, strlen($largeData));
    }

    public function testPreviousDataProperty(): void
    {
        $previousData = '{"id": 123, "name": "Old Name", "status": "inactive"}';

        $this->auditLog->setPreviousData($previousData);

        $this->assertSame($previousData, $this->auditLog->getPreviousData());
    }

    public function testPreviousDataPropertyAcceptsNull(): void
    {
        $this->auditLog->setPreviousData(null);

        $this->assertNull($this->auditLog->getPreviousData());
    }

    public function testPreviousDataPropertyCanHandleLargeText(): void
    {
        // Test that previousData can handle TEXT field size
        $largePreviousData = json_encode([
            'oldState' => array_fill(0, 50, [
                'field' => 'value',
                'complexObject' => ['nested' => 'data', 'array' => [1, 2, 3, 4, 5]],
            ]),
        ]);

        $this->auditLog->setPreviousData($largePreviousData);

        $this->assertSame($largePreviousData, $this->auditLog->getPreviousData());
        $this->assertGreaterThan(255, strlen($largePreviousData));
    }

    public function testCompleteAuditLogWorkflow(): void
    {
        $user = new User();
        $user->setEmail('auditor@example.com');

        $this->auditLog
            ->setResource('Project')
            ->setAction('update')
            ->setAuthor($user)
            ->setMeta('{"ip": "192.168.1.1", "userAgent": "PHPUnit Test"}')
            ->setData('{"id": 456, "name": "Updated Project", "status": "active"}')
            ->setPreviousData('{"id": 456, "name": "Old Project", "status": "draft"}');

        $this->assertSame('Project', $this->auditLog->getResource());
        $this->assertSame('update', $this->auditLog->getAction());
        $this->assertSame($user, $this->auditLog->getAuthor());
        $this->assertStringContainsString('192.168.1.1', $this->auditLog->getMeta());
        $this->assertStringContainsString('Updated Project', $this->auditLog->getData());
        $this->assertStringContainsString('Old Project', $this->auditLog->getPreviousData());
    }

    public function testInheritedAbstractEntityMethods(): void
    {
        // Test inherited UUID trait (UUID is null until persisted)
        $this->assertNull($this->auditLog->getId());

        // Test inherited active trait
        $this->assertTrue($this->auditLog->isActive());
        $this->auditLog->setActive(false);
        $this->assertFalse($this->auditLog->isActive());

        // Test inherited notes trait
        $notes = 'Test audit log notes';
        $this->auditLog->setNotes($notes);
        $this->assertSame($notes, $this->auditLog->getNotes());

        // Test inherited timestamp trait
        $this->assertNotNull($this->auditLog->getCreatedAt());
        $this->assertNotNull($this->auditLog->getUpdatedAt());
        $this->assertInstanceOf(\DateTime::class, $this->auditLog->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $this->auditLog->getUpdatedAt());
    }

    public function testStringableInterface(): void
    {
        // AuditLogs inherits from AbstractEntity which implements \Stringable
        $this->assertInstanceOf(\Stringable::class, $this->auditLog);

        // Call __toString() method directly instead of casting to string
        $stringRepresentation = $this->auditLog->__toString();

        $this->assertIsString($stringRepresentation);
        // AbstractEntity returns empty string by default
        $this->assertSame('', $stringRepresentation);
    }

    public function testAuditLogWithUserRelationship(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        // Test bidirectional relationship
        $this->auditLog->setAuthor($user);
        $user->addAuditLog($this->auditLog);

        $this->assertSame($user, $this->auditLog->getAuthor());
        $this->assertTrue($user->getAuditLogs()->contains($this->auditLog));
    }

    public function testFieldValidationConstraints(): void
    {
        // Test that resource field accepts valid strings
        $validResources = ['User', 'Project', 'Company', 'Category'];

        foreach ($validResources as $resource) {
            $this->auditLog->setResource($resource);
            $this->assertSame($resource, $this->auditLog->getResource());
        }

        // Test that action field accepts valid actions
        $validActions = ['create', 'update', 'delete', 'view'];

        foreach ($validActions as $action) {
            $this->auditLog->setAction($action);
            $this->assertSame($action, $this->auditLog->getAction());
        }
    }
}
