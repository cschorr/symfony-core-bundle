<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Entity\UserGroup;
use C3net\CoreBundle\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Functional tests for complete user management workflows.
 */
class UserWorkflowTest extends ApiTestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = $kernel->getContainer()->get(UserPasswordHasherInterface::class);
    }

    public function testCompleteUserLifecycle(): void
    {
        // 1. Setup: Create admin user and dependencies
        $adminUser = $this->createAdminUser();
        $company = $this->createTestCompany();
        $category = $this->createTestCategory();
        $userGroup = $this->createTestUserGroup();
        $token = $this->getAuthToken($adminUser);

        // 2. Create a new user
        $response = static::createClient()->request('POST', '/api/users', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [
                'email' => 'lifecycle@workflow.test',
                'firstName' =>'John',
                'lastName' =>'Lifecycle',
                'password' => 'secure_password_123',
                'active' => true,
                'roles' => [UserRole::ROLE_EDITOR->value],
                'company' => '/api/companies/' . $company->getId(),
                'category' => '/api/categories/' . $category->getId(),
                'userGroups' => ['/api/user_groups/' . $userGroup->getId()],
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $userData = $response->toArray();
        $userId = $userData['id'];

        // 3. Verify user was created with all relationships
        $createdUser = $this->entityManager->getRepository(User::class)->find($userId);
        $this->assertNotNull($createdUser);
        $this->assertSame('lifecycle@workflow.test', $createdUser->getEmail());
        $this->assertSame($company->getId(), $createdUser->getCompany()->getId());
        $this->assertSame($category->getId(), $createdUser->getCategory()->getId());
        $this->assertCount(1, $createdUser->getUserGroups());

        // 4. Update user information
        $response = static::createClient()->request('PATCH', '/api/users/' . $userId, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'firstName' =>'John Updated',
                'notes' => 'Updated during lifecycle test',
                'roles' => [UserRole::ROLE_EDITOR->value, UserRole::ROLE_MANAGER->value],
            ],
        ]);

        $this->assertResponseIsSuccessful();

        // Verify update
        $this->entityManager->refresh($createdUser);
        $this->assertSame('John Updated', $createdUser->getNameFirst());
        $this->assertSame('Updated during lifecycle test', $createdUser->getNotes());
        $this->assertContains(UserRole::ROLE_MANAGER->value, $createdUser->getRoles());

        // 5. Test user authentication (login simulation)
        $newUserToken = $this->getAuthToken($createdUser);
        $response = static::createClient()->request('GET', '/api/users/' . $userId, [
            'headers' => ['Authorization' => 'Bearer ' . $newUserToken],
        ]);

        $this->assertResponseIsSuccessful();

        // 6. Test user accessing their own data
        $response = static::createClient()->request('GET', '/api/users/me', [
            'headers' => ['Authorization' => 'Bearer ' . $newUserToken],
        ]);

        $this->assertResponseIsSuccessful();
        $meData = $response->toArray();
        $this->assertSame($userId, $meData['id']);

        // 7. Deactivate user
        $response = static::createClient()->request('PATCH', '/api/users/' . $userId, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'active' => false,
            ],
        ]);

        $this->assertResponseIsSuccessful();

        // Verify deactivation
        $this->entityManager->refresh($createdUser);
        $this->assertFalse($createdUser->isActive());

        // 8. Test that deactivated user cannot authenticate
        $response = static::createClient()->request('GET', '/api/users/me', [
            'headers' => ['Authorization' => 'Bearer ' . $newUserToken],
        ]);

        $this->assertResponseStatusCodeSame(401); // Should be unauthorized

        // 9. Soft delete user
        $response = static::createClient()->request('DELETE', '/api/users/' . $userId, [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(204);

        // Verify soft delete
        $this->entityManager->refresh($createdUser);
        $this->assertNotNull($createdUser->getDeletedAt());

        // 10. Verify deleted user is not accessible
        $response = static::createClient()->request('GET', '/api/users/' . $userId, [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testUserGroupMembershipWorkflow(): void
    {
        $adminUser = $this->createAdminUser();
        $token = $this->getAuthToken($adminUser);

        // Create multiple user groups with different permissions
        $editorGroup = $this->createTestUserGroup('Editors', [UserRole::ROLE_EDITOR->value]);
        $managerGroup = $this->createTestUserGroup('Managers', [UserRole::ROLE_MANAGER->value]);

        // Create user
        $response = static::createClient()->request('POST', '/api/users', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [
                'email' => 'groupmember@workflow.test',
                'firstName' =>'Group',
                'lastName' =>'Member',
                'password' => 'password123',
                'active' => true,
                'userGroups' => ['/api/user_groups/' . $editorGroup->getId()],
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $userData = $response->toArray();
        $userId = $userData['id'];

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        $this->assertCount(1, $user->getUserGroups());
        $this->assertSame('Editors', $user->getUserGroups()->first()->getName());

        // Add user to additional group
        $response = static::createClient()->request('PATCH', '/api/users/' . $userId, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'userGroups' => [
                    '/api/user_groups/' . $editorGroup->getId(),
                    '/api/user_groups/' . $managerGroup->getId(),
                ],
            ],
        ]);

        $this->assertResponseIsSuccessful();

        // Verify multiple group membership
        $this->entityManager->refresh($user);
        $this->assertCount(2, $user->getUserGroups());

        $groupNames = $user->getUserGroups()->map(fn ($group) => $group->getName())->toArray();
        $this->assertContains('Editors', $groupNames);
        $this->assertContains('Managers', $groupNames);

        // Remove from one group
        $response = static::createClient()->request('PATCH', '/api/users/' . $userId, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'userGroups' => ['/api/user_groups/' . $managerGroup->getId()],
            ],
        ]);

        $this->assertResponseIsSuccessful();

        // Verify group removal
        $this->entityManager->refresh($user);
        $this->assertCount(1, $user->getUserGroups());
        $this->assertSame('Managers', $user->getUserGroups()->first()->getName());
    }

    public function testUserPermissionEscalationWorkflow(): void
    {
        $adminUser = $this->createAdminUser();
        $token = $this->getAuthToken($adminUser);

        // Create regular user
        $response = static::createClient()->request('POST', '/api/users', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [
                'email' => 'escalation@workflow.test',
                'firstName' =>'Permission',
                'lastName' =>'Test',
                'password' => 'password123',
                'active' => true,
                'roles' => [UserRole::ROLE_USER->value],
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $userData = $response->toArray();
        $userId = $userData['id'];

        $user = $this->entityManager->getRepository(User::class)->find($userId);
        $userToken = $this->getAuthToken($user);

        // Test that regular user cannot create other users
        $response = static::createClient()->request('POST', '/api/users', [
            'headers' => [
                'Authorization' => 'Bearer ' . $userToken,
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [
                'email' => 'unauthorized@workflow.test',
                'password' => 'password123',
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);

        // Escalate user to editor role
        $response = static::createClient()->request('PATCH', '/api/users/' . $userId, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'roles' => [UserRole::ROLE_EDITOR->value],
            ],
        ]);

        $this->assertResponseIsSuccessful();

        // Refresh user and token
        $this->entityManager->refresh($user);
        $editorToken = $this->getAuthToken($user);

        // Test that editor still cannot create users (assuming business rules)
        $response = static::createClient()->request('POST', '/api/users', [
            'headers' => [
                'Authorization' => 'Bearer ' . $editorToken,
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [
                'email' => 'stillunauthorized@workflow.test',
                'password' => 'password123',
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);

        // Escalate to admin role
        $response = static::createClient()->request('PATCH', '/api/users/' . $userId, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'roles' => [UserRole::ROLE_ADMIN->value],
            ],
        ]);

        $this->assertResponseIsSuccessful();

        // Refresh user and token
        $this->entityManager->refresh($user);
        $adminToken = $this->getAuthToken($user);

        // Test that admin can now create users
        $response = static::createClient()->request('POST', '/api/users', [
            'headers' => [
                'Authorization' => 'Bearer ' . $adminToken,
                'Content-Type' => 'application/ld+json',
            ],
            'json' => [
                'email' => 'nowauthorized@workflow.test',
                'firstName' =>'Now',
                'lastName' =>'Authorized',
                'password' => 'password123',
                'active' => true,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    public function testBulkUserOperationsWorkflow(): void
    {
        $adminUser = $this->createAdminUser();
        $token = $this->getAuthToken($adminUser);
        $company = $this->createTestCompany();

        $userEmails = [];
        $userIds = [];

        // Create multiple users
        for ($i = 1; $i <= 5; ++$i) {
            $email = "bulkuser{$i}@workflow.test";
            $userEmails[] = $email;

            $response = static::createClient()->request('POST', '/api/users', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/ld+json',
                ],
                'json' => [
                    'email' => $email,
                    'firstName' =>"Bulk{$i}",
                    'lastName' =>'User',
                    'password' => 'password123',
                    'active' => true,
                    'roles' => [UserRole::ROLE_USER->value],
                    'company' => '/api/companies/' . $company->getId(),
                ],
            ]);

            $this->assertResponseStatusCodeSame(201);
            $userData = $response->toArray();
            $userIds[] = $userData['id'];
        }

        // Verify all users were created
        $response = static::createClient()->request('GET', '/api/users?company=' . $company->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(5, count($data['hydra:member']));

        // Bulk update: Change all users to editor role (simulate via individual PATCH requests)
        foreach ($userIds as $userId) {
            $response = static::createClient()->request('PATCH', '/api/users/' . $userId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'json' => [
                    'roles' => [UserRole::ROLE_EDITOR->value],
                ],
            ]);

            $this->assertResponseIsSuccessful();
        }

        // Verify bulk update
        foreach ($userIds as $userId) {
            $user = $this->entityManager->getRepository(User::class)->find($userId);
            $this->assertContains(UserRole::ROLE_EDITOR->value, $user->getRoles());
        }

        // Bulk deactivation
        foreach ($userIds as $userId) {
            $response = static::createClient()->request('PATCH', '/api/users/' . $userId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/merge-patch+json',
                ],
                'json' => [
                    'active' => false,
                ],
            ]);

            $this->assertResponseIsSuccessful();
        }

        // Verify bulk deactivation
        $response = static::createClient()->request('GET', '/api/users?active=false&company=' . $company->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(5, count($data['hydra:member']));
    }

    public function testUserInfoControllerWorkflow(): void
    {
        $user = $this->createTestUser('userinfo@workflow.test', [UserRole::ROLE_EDITOR->value]);
        $token = $this->getAuthToken($user);

        // Test the deprecated userinfo endpoint
        $response = static::createClient()->request('POST', '/api/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('username', $data);
        $this->assertArrayHasKey('roles', $data);
        $this->assertArrayHasKey('firstName', $data);
        $this->assertArrayHasKey('lastName', $data);
        $this->assertArrayHasKey('isActive', $data);
        $this->assertArrayHasKey('isLocked', $data);

        $this->assertSame($user->getId(), $data['id']);
        $this->assertSame('userinfo@workflow.test', $data['username']);
        $this->assertContains(UserRole::ROLE_EDITOR->value, $data['roles']);
        $this->assertTrue($data['isActive']);
        $this->assertFalse($data['isLocked']);

        // Test with invalid token
        $response = static::createClient()->request('POST', '/api/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer invalid-token',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);

        // Test without authorization header
        $response = static::createClient()->request('POST', '/api/userinfo', [
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    private function createAdminUser(): User
    {
        return $this->createTestUser('admin@workflow.test', [UserRole::ROLE_ADMIN->value]);
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

    private function createTestCompany(): Company
    {
        $company = new Company();
        $company->setName('Test Company')
            ->setEmail('test@company.com')
            ->setActive(true);

        $this->entityManager->persist($company);
        $this->entityManager->flush();

        return $company;
    }

    private function createTestCategory(): Category
    {
        $category = new Category();
        $category->setName('Test Category')
            ->setColor('blue')
            ->setIcon('fas fa-test')
            ->setActive(true);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    private function createTestUserGroup(string $name = 'Test Group', array $roles = [UserRole::ROLE_USER->value]): UserGroup
    {
        $userGroup = new UserGroup();
        $userGroup->setName($name)
            ->setRoles($roles)
            ->setActive(true);

        $this->entityManager->persist($userGroup);
        $this->entityManager->flush();

        return $userGroup;
    }

    private function getAuthToken(User $user): string
    {
        // In a real implementation, this would generate a proper JWT token
        return 'test-token-' . $user->getId();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test data
        $this->entityManager->getConnection()->executeStatement('DELETE FROM user WHERE email LIKE "%@workflow.test"');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM company WHERE name = "Test Company"');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM category WHERE name = "Test Category"');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM user_group WHERE name IN ("Test Group", "Editors", "Managers")');
    }
}
