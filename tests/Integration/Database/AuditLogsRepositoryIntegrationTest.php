<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Integration\Database;

use C3net\CoreBundle\Entity\AuditLogs;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Repository\AuditLogsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuditLogsRepositoryIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private AuditLogsRepository $auditLogsRepository;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get(EntityManagerInterface::class);
        $this->auditLogsRepository = $this->entityManager->getRepository(AuditLogs::class);
        $this->passwordHasher = $kernel->getContainer()->get(UserPasswordHasherInterface::class);
    }

    public function testFindUniqueAuthorsIntegration(): void
    {
        $user1 = $this->createTestUser('author1@test.com', 'John', 'Doe');
        $user2 = $this->createTestUser('author2@test.com', 'Jane', 'Smith');
        $user3 = $this->createTestUser('author3@test.com', 'Bob', 'Johnson');

        // Create audit logs
        $this->createTestAuditLog($user1, 'User', 'create');
        $this->createTestAuditLog($user2, 'Project', 'update');
        $this->createTestAuditLog($user1, 'Company', 'delete'); // User1 appears twice
        $this->createTestAuditLog($user3, 'User', 'create');

        $authors = $this->auditLogsRepository->findUniqueAuthors();

        $this->assertCount(3, $authors); // Should be unique authors

        $authorIds = array_column($authors, 'author_id');
        $this->assertContains($user1->getId(), $authorIds);
        $this->assertContains($user2->getId(), $authorIds);
        $this->assertContains($user3->getId(), $authorIds);

        // Verify each author has complete information
        foreach ($authors as $author) {
            $this->assertArrayHasKey('author_id', $author);
            $this->assertArrayHasKey('id', $author);
            $this->assertArrayHasKey('email', $author);
            $this->assertArrayHasKey('firstname', $author);
            $this->assertArrayHasKey('lastname', $author);
            $this->assertNotNull($author['email']);
        }
    }

    public function testFindUniqueResourcesIntegration(): void
    {
        $user = $this->createTestUser('resources@test.com');

        // Create audit logs with different resources
        $this->createTestAuditLog($user, 'User', 'create');
        $this->createTestAuditLog($user, 'Project', 'update');
        $this->createTestAuditLog($user, 'Company', 'delete');
        $this->createTestAuditLog($user, 'User', 'update'); // Duplicate resource
        $this->createTestAuditLog($user, 'Category', 'create');

        $resources = $this->auditLogsRepository->findUniqueResources();

        $resourceNames = array_column($resources, 'resource');
        $uniqueResources = array_unique($resourceNames);

        $this->assertCount(count($uniqueResources), $resources); // Should be unique
        $this->assertContains('User', $resourceNames);
        $this->assertContains('Project', $resourceNames);
        $this->assertContains('Company', $resourceNames);
        $this->assertContains('Category', $resourceNames);
    }

    public function testFindUniqueActionsIntegration(): void
    {
        $user = $this->createTestUser('actions@test.com');

        // Create audit logs with different actions
        $this->createTestAuditLog($user, 'User', 'create');
        $this->createTestAuditLog($user, 'User', 'update');
        $this->createTestAuditLog($user, 'User', 'delete');
        $this->createTestAuditLog($user, 'Project', 'create'); // Duplicate action
        $this->createTestAuditLog($user, 'Company', 'soft_delete');

        $actions = $this->auditLogsRepository->findUniqueActions();

        $actionNames = array_column($actions, 'action');
        $uniqueActions = array_unique($actionNames);

        $this->assertCount(count($uniqueActions), $actions); // Should be unique
        $this->assertContains('create', $actionNames);
        $this->assertContains('update', $actionNames);
        $this->assertContains('delete', $actionNames);
        $this->assertContains('soft_delete', $actionNames);
    }

    public function testComplexQueryFiltering(): void
    {
        $user1 = $this->createTestUser('query1@test.com', 'Alice', 'Johnson');
        $user2 = $this->createTestUser('query2@test.com', 'Bob', 'Smith');

        // Create diverse audit logs
        $log1 = $this->createTestAuditLog($user1, 'User', 'create');
        $log2 = $this->createTestAuditLog($user2, 'Project', 'update');
        $log3 = $this->createTestAuditLog($user1, 'Company', 'delete');
        $log4 = $this->createTestAuditLog($user2, 'User', 'soft_delete');

        // Test filtering by resource
        $userLogs = $this->auditLogsRepository->findBy(['resource' => 'User']);
        $this->assertCount(2, $userLogs);

        // Test filtering by action
        $createLogs = $this->auditLogsRepository->findBy(['action' => 'create']);
        $this->assertCount(1, $createLogs);
        $this->assertSame($log1->getId(), $createLogs[0]->getId());

        // Test filtering by author
        $user1Logs = $this->auditLogsRepository->findBy(['author' => $user1]);
        $this->assertCount(2, $user1Logs);

        $user2Logs = $this->auditLogsRepository->findBy(['author' => $user2]);
        $this->assertCount(2, $user2Logs);
    }

    public function testOrderingAndPagination(): void
    {
        $user = $this->createTestUser('ordering@test.com');

        // Create multiple logs with known order
        $logs = [];
        for ($i = 0; $i < 10; ++$i) {
            $logs[] = $this->createTestAuditLog($user, 'User', "action_{$i}");
            usleep(1000); // Ensure different microsecond timestamps
        }

        // Test ordering by creation date (newest first)
        $qb = $this->auditLogsRepository->createQueryBuilder('al');
        $qb->orderBy('al.createdAt', 'DESC');
        $orderedLogs = $qb->getQuery()->getResult();

        $this->assertCount(10, $orderedLogs);
        $this->assertTrue($orderedLogs[0]->getCreatedAt() >= $orderedLogs[1]->getCreatedAt());
        $this->assertTrue($orderedLogs[8]->getCreatedAt() >= $orderedLogs[9]->getCreatedAt());

        // Test pagination
        $qb = $this->auditLogsRepository->createQueryBuilder('al');
        $qb->orderBy('al.id', 'ASC')
           ->setFirstResult(3)
           ->setMaxResults(4);
        $paginatedLogs = $qb->getQuery()->getResult();

        $this->assertCount(4, $paginatedLogs);
    }

    public function testMetaDataAndJsonFields(): void
    {
        $user = $this->createTestUser('metadata@test.com');

        $complexMeta = [
            'request_id' => 'req-123',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0...',
            'nested' => [
                'key1' => 'value1',
                'key2' => ['nested_array' => [1, 2, 3]],
            ],
        ];

        $complexData = [
            'field1' => 'value1',
            'field2' => 42,
            'field3' => true,
            'complex_object' => [
                'property1' => 'prop_value',
                'property2' => ['array', 'of', 'strings'],
            ],
        ];

        $previousData = [
            'field1' => 'old_value1',
            'field2' => 24,
            'field3' => false,
        ];

        $auditLog = $this->createTestAuditLog($user, 'User', 'update');
        $auditLog->setMeta($complexMeta)
                 ->setData($complexData)
                 ->setPreviousData($previousData);

        $this->entityManager->flush();
        $this->entityManager->clear();

        // Retrieve and verify JSON fields are properly stored and retrieved
        $retrievedLog = $this->auditLogsRepository->find($auditLog->getId());

        $this->assertNotNull($retrievedLog);
        $this->assertSame($complexMeta, $retrievedLog->getMeta());
        $this->assertSame($complexData, $retrievedLog->getData());
        $this->assertSame($previousData, $retrievedLog->getPreviousData());

        // Test querying by JSON fields (if supported by database)
        if ('sqlite' !== $this->entityManager->getConnection()->getDatabasePlatform()->getName()) {
            $qb = $this->auditLogsRepository->createQueryBuilder('al');
            $qb->where("JSON_EXTRACT(al.meta, '$.request_id') = :requestId")
               ->setParameter('requestId', 'req-123');
            $jsonQueryResults = $qb->getQuery()->getResult();

            $this->assertCount(1, $jsonQueryResults);
            $this->assertSame($auditLog->getId(), $jsonQueryResults[0]->getId());
        }
    }

    public function testPerformanceWithLargeDataset(): void
    {
        $user = $this->createTestUser('performance@test.com');

        // Create a reasonable number of audit logs for performance testing
        $batchSize = 100;
        for ($i = 0; $i < $batchSize; ++$i) {
            $this->createTestAuditLog($user, 'PerformanceTest', "action_{$i}");

            // Flush periodically to avoid memory issues
            if (0 === $i % 20) {
                $this->entityManager->flush();
            }
        }
        $this->entityManager->flush();

        $startTime = microtime(true);

        // Test performance of unique authors query
        $authors = $this->auditLogsRepository->findUniqueAuthors();

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertLessThan(2.0, $executionTime, 'findUniqueAuthors should execute within 2 seconds');
        $this->assertGreaterThanOrEqual(1, count($authors));

        $startTime = microtime(true);

        // Test performance of unique resources query
        $resources = $this->auditLogsRepository->findUniqueResources();

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertLessThan(1.0, $executionTime, 'findUniqueResources should execute within 1 second');
        $this->assertContains('PerformanceTest', array_column($resources, 'resource'));
    }

    public function testDataIntegrityAndConstraints(): void
    {
        $user = $this->createTestUser('integrity@test.com');

        // Test that required fields are enforced
        $auditLog = new AuditLogs();
        $auditLog->setResource('TestResource')
                 ->setAction('create')
                 ->setResourceId(123)
                 ->setAuthor($user);

        $this->entityManager->persist($auditLog);
        $this->entityManager->flush();

        $this->assertNotNull($auditLog->getId());
        $this->assertNotNull($auditLog->getCreatedAt());

        // Test cascade operations
        $userId = $user->getId();
        $auditLogId = $auditLog->getId();

        // Verify audit log exists
        $foundLog = $this->auditLogsRepository->find($auditLogId);
        $this->assertNotNull($foundLog);
        $this->assertSame($userId, $foundLog->getAuthor()->getId());
    }

    public function testCustomRepositoryMethods(): void
    {
        $user1 = $this->createTestUser('custom1@test.com', 'Alice', 'Test');
        $user2 = $this->createTestUser('custom2@test.com', 'Bob', 'Test');

        // Create audit logs with specific patterns
        $this->createTestAuditLog($user1, 'User', 'create');
        $this->createTestAuditLog($user1, 'User', 'update');
        $this->createTestAuditLog($user2, 'Project', 'create');
        $this->createTestAuditLog($user2, 'Project', 'delete');

        // Test that unique methods return properly formatted data
        $authors = $this->auditLogsRepository->findUniqueAuthors();

        foreach ($authors as $author) {
            $this->assertIsArray($author);
            $this->assertArrayHasKey('author_id', $author);
            $this->assertArrayHasKey('email', $author);
            $this->assertArrayHasKey('firstname', $author);
            $this->assertArrayHasKey('lastname', $author);
            $this->assertIsString($author['email']);
        }

        $resources = $this->auditLogsRepository->findUniqueResources();

        foreach ($resources as $resource) {
            $this->assertIsArray($resource);
            $this->assertArrayHasKey('resource', $resource);
            $this->assertIsString($resource['resource']);
        }

        $actions = $this->auditLogsRepository->findUniqueActions();

        foreach ($actions as $action) {
            $this->assertIsArray($action);
            $this->assertArrayHasKey('action', $action);
            $this->assertIsString($action['action']);
        }
    }

    private function createTestUser(string $email, string $firstName = 'Test', string $lastName = 'User'): User
    {
        $user = new User();
        $user->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setPassword($this->passwordHasher->hashPassword($user, 'password123'))
            ->setRoles(['ROLE_USER'])
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
                 ->setResourceId(rand(1, 1000))
                 ->setAuthor($author)
                 ->setMeta(['ip' => '127.0.0.1', 'user_agent' => 'test'])
                 ->setData(['field' => 'value'])
                 ->setPreviousData(['field' => 'old_value']);

        $this->entityManager->persist($auditLog);

        return $auditLog;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test data
        $this->entityManager->getConnection()->executeStatement('DELETE FROM audit_logs WHERE resource IN ("User", "Project", "Company", "Category", "PerformanceTest", "TestResource")');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM user WHERE email LIKE "%@test.com"');
    }
}
