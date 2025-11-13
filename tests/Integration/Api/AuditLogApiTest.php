<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Integration\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use C3net\CoreBundle\Entity\AuditLogs;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuditLogApiTest extends ApiTestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = $kernel->getContainer()->get(UserPasswordHasherInterface::class);
    }

    public function testGetAuditLogsCollection(): void
    {
        $adminUser = $this->createTestUser('admin@test.com', [UserRole::ROLE_ADMIN->value]);
        $this->createTestAuditLog($adminUser);
        $token = $this->getAuthToken($adminUser);

        $response = static::createClient()->request('GET', '/api/audit_logs', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/AuditLogs',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testGetAuditLogItem(): void
    {
        $adminUser = $this->createTestUser('admin@test.com', [UserRole::ROLE_ADMIN->value]);
        $auditLog = $this->createTestAuditLog($adminUser);
        $token = $this->getAuthToken($adminUser);

        $response = static::createClient()->request('GET', '/api/audit_logs/' . $auditLog->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/AuditLogs',
            '@type' => 'AuditLogs',
            '@id' => '/api/audit_logs/' . $auditLog->getId(),
            'resource' => 'User',
            'action' => 'create',
        ]);
    }

    public function testAuditLogFiltering(): void
    {
        $adminUser = $this->createTestUser('admin@test.com', [UserRole::ROLE_ADMIN->value]);
        $user1 = $this->createTestUser('user1@test.com');
        $user2 = $this->createTestUser('user2@test.com');

        $this->createTestAuditLog($user1, 'User', 'create');
        $this->createTestAuditLog($user2, 'Project', 'update');
        $this->createTestAuditLog($user1, 'Company', 'delete');

        $token = $this->getAuthToken($adminUser);

        // Filter by resource
        $response = static::createClient()->request('GET', '/api/audit_logs?resource=User', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, count($data['hydra:member']));

        foreach ($data['hydra:member'] as $item) {
            $this->assertSame('User', $item['resource']);
        }

        // Filter by action
        $response = static::createClient()->request('GET', '/api/audit_logs?action=create', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, count($data['hydra:member']));

        foreach ($data['hydra:member'] as $item) {
            $this->assertSame('create', $item['action']);
        }

        // Filter by author
        $response = static::createClient()->request('GET', '/api/audit_logs?author=' . $user1->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(2, count($data['hydra:member'])); // user1 created 2 logs
    }

    public function testAuditLogPagination(): void
    {
        $adminUser = $this->createTestUser('admin@test.com', [UserRole::ROLE_ADMIN->value]);
        $token = $this->getAuthToken($adminUser);

        // Create multiple audit logs
        for ($i = 0; $i < 25; ++$i) {
            $this->createTestAuditLog($adminUser, 'User', 'test_action_' . $i);
        }

        $response = static::createClient()->request('GET', '/api/audit_logs?page=1', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertArrayHasKey('hydra:view', $data);
        $this->assertArrayHasKey('hydra:first', $data['hydra:view']);
        $this->assertArrayHasKey('hydra:totalItems', $data);
        $this->assertGreaterThanOrEqual(25, $data['hydra:totalItems']);
    }

    public function testAuditLogOrdering(): void
    {
        $adminUser = $this->createTestUser('admin@test.com', [UserRole::ROLE_ADMIN->value]);
        $token = $this->getAuthToken($adminUser);

        // Create audit logs with different timestamps
        $log1 = $this->createTestAuditLog($adminUser, 'User', 'action1');
        sleep(1); // Ensure different timestamps
        $log2 = $this->createTestAuditLog($adminUser, 'User', 'action2');

        // Test ordering by createdAt descending (newest first)
        $response = static::createClient()->request('GET', '/api/audit_logs?order[createdAt]=desc', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        if (count($data['hydra:member']) >= 2) {
            $firstItem = $data['hydra:member'][0];
            $secondItem = $data['hydra:member'][1];
            $this->assertGreaterThan($secondItem['createdAt'], $firstItem['createdAt']);
        }
    }

    public function testAuditLogReadOnlyAccess(): void
    {
        $adminUser = $this->createTestUser('admin@test.com', [UserRole::ROLE_ADMIN->value]);
        $auditLog = $this->createTestAuditLog($adminUser);
        $token = $this->getAuthToken($adminUser);

        // Test that POST is not allowed
        $response = static::createClient()->request('POST', '/api/audit_logs', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [
                'resource' => 'Test',
                'action' => 'create',
            ],
        ]);

        $this->assertResponseStatusCodeSame(405); // Method Not Allowed

        // Test that PUT/PATCH is not allowed
        static::createClient()->request('PUT', '/api/audit_logs/' . $auditLog->getId(), [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [
                'action' => 'updated',
            ],
        ]);

        $this->assertResponseStatusCodeSame(405);

        // Test that DELETE is not allowed
        static::createClient()->request('DELETE', '/api/audit_logs/' . $auditLog->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(405);
    }

    public function testAuditLogAuthorsEndpoint(): void
    {
        $adminUser = $this->createTestUser('admin@test.com', [UserRole::ROLE_ADMIN->value]);
        $user1 = $this->createTestUser('user1@test.com', [], 'John', 'Doe');
        $user2 = $this->createTestUser('user2@test.com', [], 'Jane', 'Smith');

        $this->createTestAuditLog($user1);
        $this->createTestAuditLog($user2);

        $token = $this->getAuthToken($adminUser);

        $response = static::createClient()->request('GET', '/api/audit_log_authors', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(2, count($data));

        // Check that each author has the expected structure
        foreach ($data as $author) {
            $this->assertArrayHasKey('@id', $author);
            $this->assertArrayHasKey('@type', $author);
            $this->assertArrayHasKey('id', $author);
            $this->assertArrayHasKey('email', $author);
            $this->assertArrayHasKey('fullname', $author);
            $this->assertSame('User', $author['@type']);
        }
    }

    public function testAuditLogResourcesEndpoint(): void
    {
        $adminUser = $this->createTestUser('admin@test.com', [UserRole::ROLE_ADMIN->value]);

        $this->createTestAuditLog($adminUser, 'User', 'create');
        $this->createTestAuditLog($adminUser, 'Project', 'update');
        $this->createTestAuditLog($adminUser, 'Company', 'delete');

        $token = $this->getAuthToken($adminUser);

        $response = static::createClient()->request('GET', '/api/audit_log_resources', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertIsArray($data);
        $this->assertContains('User', $data);
        $this->assertContains('Project', $data);
        $this->assertContains('Company', $data);
    }

    public function testAuditLogActionsEndpoint(): void
    {
        $adminUser = $this->createTestUser('admin@test.com', [UserRole::ROLE_ADMIN->value]);

        $this->createTestAuditLog($adminUser, 'User', 'create');
        $this->createTestAuditLog($adminUser, 'User', 'update');
        $this->createTestAuditLog($adminUser, 'User', 'delete');

        $token = $this->getAuthToken($adminUser);

        $response = static::createClient()->request('GET', '/api/audit_log_actions', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertIsArray($data);
        $this->assertContains('create', $data);
        $this->assertContains('update', $data);
        $this->assertContains('delete', $data);
    }

    public function testAuditLogFiltersEndpoint(): void
    {
        $adminUser = $this->createTestUser('admin@test.com', [UserRole::ROLE_ADMIN->value]);
        $user1 = $this->createTestUser('user1@test.com', [], 'John', 'Doe');

        $this->createTestAuditLog($user1, 'User', 'create');
        $this->createTestAuditLog($adminUser, 'Project', 'update');

        $token = $this->getAuthToken($adminUser);

        $response = static::createClient()->request('GET', '/api/audit_log_filters', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('authors', $data);
        $this->assertArrayHasKey('resources', $data);
        $this->assertArrayHasKey('actions', $data);

        $this->assertIsArray($data['authors']);
        $this->assertIsArray($data['resources']);
        $this->assertIsArray($data['actions']);

        $this->assertGreaterThanOrEqual(2, count($data['authors']));
        $this->assertContains('User', $data['resources']);
        $this->assertContains('Project', $data['resources']);
        $this->assertContains('create', $data['actions']);
        $this->assertContains('update', $data['actions']);
    }

    private function createTestUser(string $email, array $roles = [UserRole::ROLE_USER->value], string $firstName = 'Test', string $lastName = 'User'): User
    {
        $user = new User();
        $user->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setPassword($this->passwordHasher->hashPassword($user, 'password123'))
            ->setRoles($roles)
            ->setActive(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createTestAuditLog(User $author, string $resource = 'User', string $action = 'create'): AuditLogs
    {
        $auditLog = new AuditLogs();
        $auditLog->setResource($resource)
            ->setAction($action)
            ->setResourceId(123)
            ->setAuthor($author)
            ->setMeta(['test' => 'metadata'])
            ->setData(['field1' => 'value1', 'field2' => 'value2'])
            ->setPreviousData(['field1' => 'old_value1']);

        $this->entityManager->persist($auditLog);
        $this->entityManager->flush();

        return $auditLog;
    }

    private function getAuthToken(User $user): string
    {
        return 'test-token-' . $user->getId();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test data
        $this->entityManager->getConnection()->executeStatement('DELETE FROM audit_logs WHERE resource IN ("User", "Project", "Company") AND action LIKE "test_action_%"');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM user WHERE email LIKE "%@test.com"');
    }
}
