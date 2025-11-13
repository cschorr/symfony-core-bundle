<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Integration\Security;

use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Integration test for user authentication with UserChecker.
 * Tests that locked and inactive users cannot authenticate via the API.
 */
class UserAuthenticationTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = $kernel->getContainer()->get(UserPasswordHasherInterface::class);
    }

    public function testActiveAndUnlockedUserCanLogin(): void
    {
        $user = $this->createTestUser('active@test.com', 'password123', true, false);

        $client = static::createClient();
        $client->request('POST', '/api/auth', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'active@test.com',
            'password' => 'password123',
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }

    public function testLockedUserCannotLogin(): void
    {
        $user = $this->createTestUser('locked@test.com', 'password123', true, true);

        $client = static::createClient();
        $client->request('POST', '/api/auth', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'locked@test.com',
            'password' => 'password123',
        ]));

        $this->assertResponseStatusCodeSame(401);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('locked', strtolower($data['message']));
    }

    public function testInactiveUserCannotLogin(): void
    {
        $user = $this->createTestUser('inactive@test.com', 'password123', false, false);

        $client = static::createClient();
        $client->request('POST', '/api/auth', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'inactive@test.com',
            'password' => 'password123',
        ]));

        $this->assertResponseStatusCodeSame(401);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('inactive', strtolower($data['message']));
    }

    public function testLockedAndInactiveUserCannotLogin(): void
    {
        $user = $this->createTestUser('locked-inactive@test.com', 'password123', false, true);

        $client = static::createClient();
        $client->request('POST', '/api/auth', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'locked-inactive@test.com',
            'password' => 'password123',
        ]));

        $this->assertResponseStatusCodeSame(401);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        // Locked check comes first, so we expect locked message
        $this->assertStringContainsString('locked', strtolower($data['message']));
    }

    public function testUserCanLoginThenGetsLockedThenCannotLogin(): void
    {
        $user = $this->createTestUser('toggle-lock@test.com', 'password123', true, false);

        $client = static::createClient();

        // First login should succeed
        $client->request('POST', '/api/auth', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'toggle-lock@test.com',
            'password' => 'password123',
        ]));

        $this->assertResponseIsSuccessful();
        $firstResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $firstResponse);

        // Lock the user
        $user->setLocked(true);
        $this->entityManager->flush();

        // Second login attempt should fail
        $client->request('POST', '/api/auth', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'toggle-lock@test.com',
            'password' => 'password123',
        ]));

        $this->assertResponseStatusCodeSame(401);
        $secondResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $secondResponse);
        $this->assertStringContainsString('locked', strtolower($secondResponse['message']));
    }

    public function testUserCanLoginThenGetsDeactivatedThenCannotLogin(): void
    {
        $user = $this->createTestUser('toggle-active@test.com', 'password123', true, false);

        $client = static::createClient();

        // First login should succeed
        $client->request('POST', '/api/auth', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'toggle-active@test.com',
            'password' => 'password123',
        ]));

        $this->assertResponseIsSuccessful();
        $firstResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $firstResponse);

        // Deactivate the user
        $user->setActive(false);
        $this->entityManager->flush();

        // Second login attempt should fail
        $client->request('POST', '/api/auth', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'toggle-active@test.com',
            'password' => 'password123',
        ]));

        $this->assertResponseStatusCodeSame(401);
        $secondResponse = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $secondResponse);
        $this->assertStringContainsString('inactive', strtolower($secondResponse['message']));
    }

    public function testLockedUserWithWrongPasswordStillGetsLockedMessage(): void
    {
        $user = $this->createTestUser('locked-wrong-pass@test.com', 'correctpassword', true, true);

        $client = static::createClient();
        $client->request('POST', '/api/auth', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'locked-wrong-pass@test.com',
            'password' => 'wrongpassword',
        ]));

        $this->assertResponseStatusCodeSame(401);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        // Should get locked message, not invalid credentials
        $this->assertStringContainsString('locked', strtolower($data['message']));
    }

    public function testInactiveUserWithWrongPasswordStillGetsInactiveMessage(): void
    {
        $user = $this->createTestUser('inactive-wrong-pass@test.com', 'correctpassword', false, false);

        $client = static::createClient();
        $client->request('POST', '/api/auth', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'inactive-wrong-pass@test.com',
            'password' => 'wrongpassword',
        ]));

        $this->assertResponseStatusCodeSame(401);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        // Should get inactive message, not invalid credentials
        $this->assertStringContainsString('inactive', strtolower($data['message']));
    }

    public function testMultipleLockedUsersCannotLogin(): void
    {
        $users = [
            $this->createTestUser('locked1@test.com', 'password123', true, true),
            $this->createTestUser('locked2@test.com', 'password123', true, true),
            $this->createTestUser('locked3@test.com', 'password123', true, true),
        ];

        $client = static::createClient();

        foreach ($users as $index => $user) {
            $client->request('POST', '/api/auth', [], [], [
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([
                'email' => $user->getEmail(),
                'password' => 'password123',
            ]));

            $this->assertResponseStatusCodeSame(401, "User {$index} should not be able to login");

            $data = json_decode($client->getResponse()->getContent(), true);
            $this->assertStringContainsString('locked', strtolower($data['message']));
        }
    }

    public function testMultipleInactiveUsersCannotLogin(): void
    {
        $users = [
            $this->createTestUser('inactive1@test.com', 'password123', false, false),
            $this->createTestUser('inactive2@test.com', 'password123', false, false),
            $this->createTestUser('inactive3@test.com', 'password123', false, false),
        ];

        $client = static::createClient();

        foreach ($users as $index => $user) {
            $client->request('POST', '/api/auth', [], [], [
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([
                'email' => $user->getEmail(),
                'password' => 'password123',
            ]));

            $this->assertResponseStatusCodeSame(401, "User {$index} should not be able to login");

            $data = json_decode($client->getResponse()->getContent(), true);
            $this->assertStringContainsString('inactive', strtolower($data['message']));
        }
    }

    public function testUserWithDifferentRolesCanBeLockedOrInactive(): void
    {
        $testCases = [
            ['email' => 'admin-locked@test.com', 'role' => UserRole::ROLE_ADMIN, 'active' => true, 'locked' => true],
            ['email' => 'admin-inactive@test.com', 'role' => UserRole::ROLE_ADMIN, 'active' => false, 'locked' => false],
            ['email' => 'editor-locked@test.com', 'role' => UserRole::ROLE_EDITOR, 'active' => true, 'locked' => true],
            ['email' => 'editor-inactive@test.com', 'role' => UserRole::ROLE_EDITOR, 'active' => false, 'locked' => false],
            ['email' => 'manager-locked@test.com', 'role' => UserRole::ROLE_MANAGER, 'active' => true, 'locked' => true],
            ['email' => 'manager-inactive@test.com', 'role' => UserRole::ROLE_MANAGER, 'active' => false, 'locked' => false],
        ];

        $client = static::createClient();

        foreach ($testCases as $testCase) {
            $user = $this->createTestUser(
                $testCase['email'],
                'password123',
                $testCase['active'],
                $testCase['locked'],
                [$testCase['role']->value]
            );

            $client->request('POST', '/api/auth', [], [], [
                'CONTENT_TYPE' => 'application/json',
            ], json_encode([
                'email' => $testCase['email'],
                'password' => 'password123',
            ]));

            $this->assertResponseStatusCodeSame(401, "User {$testCase['email']} should not be able to login");

            $data = json_decode($client->getResponse()->getContent(), true);
            if ($testCase['locked']) {
                $this->assertStringContainsString('locked', strtolower($data['message']));
            } else {
                $this->assertStringContainsString('inactive', strtolower($data['message']));
            }
        }
    }

    public function testErrorResponseFormat(): void
    {
        $user = $this->createTestUser('error-format@test.com', 'password123', true, true);

        $client = static::createClient();
        $client->request('POST', '/api/auth', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'error-format@test.com',
            'password' => 'password123',
        ]));

        $this->assertResponseStatusCodeSame(401);

        $data = json_decode($client->getResponse()->getContent(), true);

        // Verify response structure
        $this->assertIsArray($data);
        $this->assertArrayHasKey('message', $data);
        $this->assertIsString($data['message']);
        $this->assertNotEmpty($data['message']);

        // Verify it's user-friendly
        $this->assertStringNotContainsString('Exception', $data['message']);
        $this->assertStringNotContainsString('Error', $data['message']);
        $this->assertStringNotContainsString('Stack trace', $data['message']);
    }

    private function createTestUser(
        string $email,
        string $password,
        bool $active = true,
        bool $locked = false,
        array $roles = [UserRole::ROLE_USER->value],
    ): User {
        $user = new User();
        $user->setEmail($email)
            ->setFirstName('Test')
            ->setLastName('User')
            ->setPassword($this->passwordHasher->hashPassword($user, $password))
            ->setRoles($roles)
            ->setActive($active)
            ->setLocked($locked);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test data
        $this->entityManager->getConnection()->executeStatement('DELETE FROM user WHERE email LIKE "%@test.com"');
    }
}
