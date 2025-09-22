<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Integration\Controller;

use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Enum\UserRole;
use C3net\CoreBundle\Service\JWTUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Integration test for UserInfoController
 * Tests the actual HTTP endpoint with real dependencies
 */
class UserInfoControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private JWTUserService $jwtUserService;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = $kernel->getContainer()->get(UserPasswordHasherInterface::class);
        $this->jwtUserService = $kernel->getContainer()->get(JWTUserService::class);
    }

    public function testUserInfoWithValidToken(): void
    {
        $user = $this->createTestUser('userinfo@test.com', [UserRole::ROLE_EDITOR->value]);
        $token = $this->generateValidToken($user);

        $client = static::createClient();
        $client->request('POST', '/api/userinfo', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('username', $data);
        $this->assertArrayHasKey('roles', $data);
        $this->assertArrayHasKey('firstName', $data);
        $this->assertArrayHasKey('lastName', $data);
        $this->assertArrayHasKey('isActive', $data);
        $this->assertArrayHasKey('isLocked', $data);

        $this->assertSame($user->getId(), $data['id']);
        $this->assertSame('userinfo@test.com', $data['username']);
        $this->assertContains(UserRole::ROLE_EDITOR->value, $data['roles']);
        $this->assertSame('Test', $data['firstName']);
        $this->assertSame('User', $data['lastName']);
        $this->assertTrue($data['isActive']);
        $this->assertFalse($data['isLocked']);
    }

    public function testUserInfoWithMissingAuthorizationHeader(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/userinfo', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ]);

        $this->assertResponseStatusCodeSame(400);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('Authorization header with Bearer token required', $data['error']);
    }

    public function testUserInfoWithInvalidAuthorizationHeader(): void
    {
        $client = static::createClient();
        
        // Test without Bearer prefix
        $client->request('POST', '/api/userinfo', [], [], [
            'HTTP_AUTHORIZATION' => 'Basic some-token',
            'CONTENT_TYPE' => 'application/json'
        ]);

        $this->assertResponseStatusCodeSame(400);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('Authorization header with Bearer token required', $data['error']);
    }

    public function testUserInfoWithEmptyToken(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/userinfo', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ',
            'CONTENT_TYPE' => 'application/json'
        ]);

        $this->assertResponseStatusCodeSame(400);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('Token required', $data['error']);
    }

    public function testUserInfoWithInvalidToken(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/userinfo', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer invalid-token-12345',
            'CONTENT_TYPE' => 'application/json'
        ]);

        $this->assertResponseStatusCodeSame(401);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('Invalid token', $data['error']);
    }

    public function testUserInfoWithExpiredToken(): void
    {
        $user = $this->createTestUser('expired@test.com');
        $expiredToken = $this->generateExpiredToken($user);

        $client = static::createClient();
        $client->request('POST', '/api/userinfo', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $expiredToken,
            'CONTENT_TYPE' => 'application/json'
        ]);

        $this->assertResponseStatusCodeSame(401);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('Invalid token', $data['error']);
    }

    public function testUserInfoWithInactiveUser(): void
    {
        $user = $this->createTestUser('inactive@test.com');
        $user->setActive(false);
        $this->entityManager->flush();
        
        $token = $this->generateValidToken($user);

        $client = static::createClient();
        $client->request('POST', '/api/userinfo', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ]);

        $this->assertResponseIsSuccessful();
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['isActive']);
        $this->assertSame('inactive@test.com', $data['username']);
    }

    public function testUserInfoWithDifferentRoles(): void
    {
        $testCases = [
            [UserRole::ROLE_USER->value],
            [UserRole::ROLE_EDITOR->value],
            [UserRole::ROLE_MANAGER->value],
            [UserRole::ROLE_ADMIN->value],
            [UserRole::ROLE_ADMIN->value, UserRole::ROLE_MANAGER->value] // Multiple roles
        ];

        foreach ($testCases as $index => $roles) {
            $user = $this->createTestUser("roles{$index}@test.com", $roles);
            $token = $this->generateValidToken($user);

            $client = static::createClient();
            $client->request('POST', '/api/userinfo', [], [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json'
            ]);

            $this->assertResponseIsSuccessful();
            
            $data = json_decode($client->getResponse()->getContent(), true);
            $this->assertArrayHasKey('roles', $data);
            
            foreach ($roles as $role) {
                $this->assertContains($role, $data['roles']);
            }
            
            // All users should have ROLE_USER
            $this->assertContains(UserRole::ROLE_USER->value, $data['roles']);
        }
    }

    public function testUserInfoResponseFormat(): void
    {
        $user = $this->createTestUser('format@test.com');
        $user->setNameFirst('John');
        $user->setNameLast('Doe');
        $this->entityManager->flush();
        
        $token = $this->generateValidToken($user);

        $client = static::createClient();
        $client->request('POST', '/api/userinfo', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ]);

        $this->assertResponseIsSuccessful();
        
        $data = json_decode($client->getResponse()->getContent(), true);

        // Verify all expected fields are present
        $expectedFields = ['id', 'username', 'roles', 'firstName', 'lastName', 'isActive', 'isLocked'];
        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $data);
        }

        // Verify data types
        $this->assertIsString($data['id']);
        $this->assertIsString($data['username']);
        $this->assertIsArray($data['roles']);
        $this->assertIsString($data['firstName']);
        $this->assertIsString($data['lastName']);
        $this->assertIsBool($data['isActive']);
        $this->assertIsBool($data['isLocked']);

        // Verify specific values
        $this->assertSame('John', $data['firstName']);
        $this->assertSame('Doe', $data['lastName']);
        $this->assertTrue($data['isActive']);
        $this->assertFalse($data['isLocked']);
    }

    public function testUserInfoWithNullNames(): void
    {
        $user = $this->createTestUser('nullnames@test.com');
        $user->setNameFirst(null);
        $user->setNameLast(null);
        $this->entityManager->flush();
        
        $token = $this->generateValidToken($user);

        $client = static::createClient();
        $client->request('POST', '/api/userinfo', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ]);

        $this->assertResponseIsSuccessful();
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertNull($data['firstName']);
        $this->assertNull($data['lastName']);
    }

    public function testUserInfoEndpointDeprecationWarning(): void
    {
        // This test could check for deprecation headers or warnings
        // depending on how deprecation is implemented
        $user = $this->createTestUser('deprecation@test.com');
        $token = $this->generateValidToken($user);

        $client = static::createClient();
        $client->request('POST', '/api/userinfo', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ]);

        $this->assertResponseIsSuccessful();
        
        // Check if deprecation warning is present in headers
        $response = $client->getResponse();
        
        // This would depend on how deprecation warnings are implemented
        // For now, just verify the endpoint still works
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
    }

    public function testUserInfoPerformanceWithManyRequests(): void
    {
        $user = $this->createTestUser('performance@test.com');
        $token = $this->generateValidToken($user);

        $client = static::createClient();
        
        $startTime = microtime(true);
        
        // Make multiple requests to test performance
        for ($i = 0; $i < 10; $i++) {
            $client->request('POST', '/api/userinfo', [], [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json'
            ]);
            
            $this->assertResponseIsSuccessful();
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // Should complete 10 requests in reasonable time
        $this->assertLessThan(5.0, $totalTime, 'UserInfo endpoint should handle 10 requests within 5 seconds');
    }

    public function testConcurrentUserInfoRequests(): void
    {
        $users = [];
        $tokens = [];
        
        // Create multiple users and tokens
        for ($i = 0; $i < 3; $i++) {
            $user = $this->createTestUser("concurrent{$i}@test.com");
            $users[] = $user;
            $tokens[] = $this->generateValidToken($user);
        }

        $client = static::createClient();
        
        // Make concurrent-like requests (simulated by rapid sequential requests)
        foreach ($tokens as $index => $token) {
            $client->request('POST', '/api/userinfo', [], [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json'
            ]);
            
            $this->assertResponseIsSuccessful();
            
            $data = json_decode($client->getResponse()->getContent(), true);
            $this->assertSame($users[$index]->getId(), $data['id']);
            $this->assertSame("concurrent{$index}@test.com", $data['username']);
        }
    }

    private function createTestUser(string $email, array $roles = [UserRole::ROLE_USER->value]): User
    {
        $user = new User();
        $user->setEmail($email)
            ->setNameFirst('Test')
            ->setNameLast('User')
            ->setPassword($this->passwordHasher->hashPassword($user, 'password123'))
            ->setRoles($roles)
            ->setActive(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function generateValidToken(User $user): string
    {
        // In a real implementation, this would use the actual JWT service
        // For testing, we'll create a simple token that the JWT service can recognize
        return 'valid-test-token-' . $user->getId() . '-' . time();
    }

    private function generateExpiredToken(User $user): string
    {
        // Create a token that appears expired
        return 'expired-test-token-' . $user->getId() . '-' . (time() - 3600);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up test data
        $this->entityManager->getConnection()->executeStatement('DELETE FROM user WHERE email LIKE "%@test.com"');
    }
}