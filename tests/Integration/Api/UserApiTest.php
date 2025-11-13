<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Integration\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Entity\UserGroup;
use C3net\CoreBundle\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserApiTest extends ApiTestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = $kernel->getContainer()->get(UserPasswordHasherInterface::class);
    }

    public function testGetUsersCollection(): void
    {
        // Create test user for authentication
        $adminUser = $this->createTestUser('admin@test.com', [UserRole::ROLE_ADMIN->value]);
        $token = $this->getAuthToken($adminUser);

        $response = static::createClient()->request('GET', '/api/users', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/User',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testGetUserItem(): void
    {
        $adminUser = $this->createTestUser('admin@test.com', [UserRole::ROLE_ADMIN->value]);
        $testUser = $this->createTestUser('test@test.com', [UserRole::ROLE_USER->value]);
        $token = $this->getAuthToken($adminUser);

        $response = static::createClient()->request('GET', '/api/users/' . $testUser->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/User',
            '@type' => 'User',
            '@id' => '/api/users/' . $testUser->getId(),
            'email' => 'test@test.com',
        ]);
    }

    public function testCreateUser(): void
    {
        $adminUser = $this->createTestUser('admin@test.com', [UserRole::ROLE_ADMIN->value]);
        $token = $this->getAuthToken($adminUser);

        $response = static::createClient()->request('POST', '/api/users', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [
                'email' => 'newuser@test.com',
                'firstName' =>'New',
                'lastName' =>'User',
                'password' => 'password123',
                'active' => true,
                'roles' => [UserRole::ROLE_USER->value],
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains([
            '@context' => '/api/contexts/User',
            '@type' => 'User',
            'email' => 'newuser@test.com',
            'firstName' =>'New',
            'lastName' =>'User',
            'active' => true,
        ]);

        // Verify user was created in database
        $createdUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'newuser@test.com']);
        $this->assertNotNull($createdUser);
        $this->assertSame('New', $createdUser->getNameFirst());
    }

    public function testUpdateUser(): void
    {
        $adminUser = $this->createTestUser('admin@test.com', [UserRole::ROLE_ADMIN->value]);
        $testUser = $this->createTestUser('test@test.com', [UserRole::ROLE_USER->value]);
        $token = $this->getAuthToken($adminUser);

        $response = static::createClient()->request('PATCH', '/api/users/' . $testUser->getId(), [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'firstName' =>'Updated',
                'lastName' =>'Name',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            'firstName' =>'Updated',
            'lastName' =>'Name',
        ]);

        // Verify changes in database
        $this->entityManager->refresh($testUser);
        $this->assertSame('Updated', $testUser->getNameFirst());
        $this->assertSame('Name', $testUser->getNameLast());
    }

    public function testDeleteUser(): void
    {
        $adminUser = $this->createTestUser('admin@test.com', [UserRole::ROLE_ADMIN->value]);
        $testUser = $this->createTestUser('delete@test.com', [UserRole::ROLE_USER->value]);
        $token = $this->getAuthToken($adminUser);

        static::createClient()->request('DELETE', '/api/users/' . $testUser->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(204);

        // Verify user is soft-deleted (still exists but marked as deleted)
        $this->entityManager->refresh($testUser);
        $this->assertNotNull($testUser->getDeletedAt());
    }

    public function testUnauthorizedAccess(): void
    {
        static::createClient()->request('GET', '/api/users');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testForbiddenAccess(): void
    {
        $userWithoutPermissions = $this->createTestUser('user@test.com', [UserRole::ROLE_USER->value]);
        $token = $this->getAuthToken($userWithoutPermissions);

        static::createClient()->request('POST', '/api/users', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [
                'email' => 'forbidden@test.com',
                'password' => 'password123',
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testValidationErrors(): void
    {
        $adminUser = $this->createTestUser('admin@test.com', [UserRole::ROLE_ADMIN->value]);
        $token = $this->getAuthToken($adminUser);

        $response = static::createClient()->request('POST', '/api/users', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [
                'email' => 'invalid-email',
                'password' => '123', // Too short
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertJsonContains([
            '@type' => 'ConstraintViolationList',
        ]);
    }

    public function testUserGroupAssignment(): void
    {
        $adminUser = $this->createTestUser('admin@test.com', [UserRole::ROLE_ADMIN->value]);
        $token = $this->getAuthToken($adminUser);

        // Create a user group
        $userGroup = new UserGroup();
        $userGroup->setName('Test Group')
            ->setRoles([UserRole::ROLE_EDITOR->value])
            ->setActive(true);
        $this->entityManager->persist($userGroup);
        $this->entityManager->flush();

        $response = static::createClient()->request('POST', '/api/users', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [
                'email' => 'groupuser@test.com',
                'firstName' =>'Group',
                'lastName' =>'User',
                'password' => 'password123',
                'active' => true,
                'userGroups' => ['/api/user_groups/' . $userGroup->getId()],
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);

        $createdUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'groupuser@test.com']);
        $this->assertNotNull($createdUser);
        $this->assertCount(1, $createdUser->getUserGroups());
        $this->assertSame('Test Group', $createdUser->getUserGroups()->first()->getName());
    }

    public function testUserSearchAndFiltering(): void
    {
        $adminUser = $this->createTestUser('admin@test.com', [UserRole::ROLE_ADMIN->value]);
        $this->createTestUser('john@example.com', [UserRole::ROLE_USER->value], 'John', 'Doe');
        $this->createTestUser('jane@example.com', [UserRole::ROLE_EDITOR->value], 'Jane', 'Smith');
        $token = $this->getAuthToken($adminUser);

        // Test filtering by email
        $response = static::createClient()->request('GET', '/api/users?email=john@example.com', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertCount(1, $data['hydra:member']);
        $this->assertSame('john@example.com', $data['hydra:member'][0]['email']);

        // Test filtering by role
        $response = static::createClient()->request('GET', '/api/users?roles[]=' . UserRole::ROLE_EDITOR->value, [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, count($data['hydra:member']));
    }

    private function createTestUser(string $email, array $roles = [], string $firstName = 'Test', string $lastName = 'User'): User
    {
        $user = new User();
        $user->setEmail($email)
            ->setNameFirst($firstName)
            ->setNameLast($lastName)
            ->setPassword($this->passwordHasher->hashPassword($user, 'password123'))
            ->setRoles($roles)
            ->setActive(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function getAuthToken(User $user): string
    {
        // This would typically use a JWT service or mock authentication
        // For now, we'll return a simple token that works with test configuration
        return 'test-token-' . $user->getId();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test data
        $this->entityManager->getConnection()->executeStatement('DELETE FROM user WHERE email LIKE "%@test.com" OR email LIKE "%@example.com"');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM user_group WHERE name = "Test Group"');
    }
}
