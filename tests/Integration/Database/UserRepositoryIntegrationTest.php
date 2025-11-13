<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Integration\Database;

use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRepositoryIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get(EntityManagerInterface::class);
        $this->userRepository = $this->entityManager->getRepository(User::class);
        $this->passwordHasher = $kernel->getContainer()->get(UserPasswordHasherInterface::class);
    }

    public function testFindByEmail(): void
    {
        $user = $this->createTestUser('find@test.com');

        $foundUser = $this->userRepository->findOneBy(['email' => 'find@test.com']);

        $this->assertNotNull($foundUser);
        $this->assertSame('find@test.com', $foundUser->getEmail());
        $this->assertSame($user->getId(), $foundUser->getId());
    }

    public function testUpgradePasswordIntegration(): void
    {
        $user = $this->createTestUser('upgrade@test.com', 'oldPassword');
        $originalPassword = $user->getPassword();
        $newPassword = 'newHashedPassword123';

        $this->userRepository->upgradePassword($user, $newPassword);

        // Refresh from database
        $this->entityManager->refresh($user);

        $this->assertSame($newPassword, $user->getPassword());
        $this->assertNotSame($originalPassword, $user->getPassword());
    }

    public function testFindActiveUsers(): void
    {
        $activeUser = $this->createTestUser('active@test.com');
        $activeUser->setActive(true);

        $inactiveUser = $this->createTestUser('inactive@test.com');
        $inactiveUser->setActive(false);

        $this->entityManager->flush();

        $activeUsers = $this->userRepository->findBy(['active' => true]);
        $inactiveUsers = $this->userRepository->findBy(['active' => false]);

        $activeEmails = array_map(fn ($u) => $u->getEmail(), $activeUsers);
        $inactiveEmails = array_map(fn ($u) => $u->getEmail(), $inactiveUsers);

        $this->assertContains('active@test.com', $activeEmails);
        $this->assertContains('inactive@test.com', $inactiveEmails);
        $this->assertNotContains('inactive@test.com', $activeEmails);
        $this->assertNotContains('active@test.com', $inactiveEmails);
    }

    public function testFindByRoles(): void
    {
        $adminUser = $this->createTestUser('admin@test.com');
        $adminUser->setRoles(['ROLE_ADMIN', 'ROLE_USER']);

        $editorUser = $this->createTestUser('editor@test.com');
        $editorUser->setRoles(['ROLE_EDITOR', 'ROLE_USER']);

        $regularUser = $this->createTestUser('user@test.com');
        $regularUser->setRoles(['ROLE_USER']);

        $this->entityManager->flush();

        // Find users with ROLE_ADMIN
        $qb = $this->userRepository->createQueryBuilder('u');
        $qb->where('u.roles LIKE :role')
           ->setParameter('role', '%ROLE_ADMIN%');
        $adminUsers = $qb->getQuery()->getResult();

        $this->assertCount(1, $adminUsers);
        $this->assertSame('admin@test.com', $adminUsers[0]->getEmail());

        // Find users with ROLE_EDITOR
        $qb = $this->userRepository->createQueryBuilder('u');
        $qb->where('u.roles LIKE :role')
           ->setParameter('role', '%ROLE_EDITOR%');
        $editorUsers = $qb->getQuery()->getResult();

        $this->assertCount(1, $editorUsers);
        $this->assertSame('editor@test.com', $editorUsers[0]->getEmail());
    }

    public function testSoftDeleteIntegration(): void
    {
        $user = $this->createTestUser('delete@test.com');
        $userId = $user->getId();

        // Soft delete
        $user->setDeletedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        // Verify user still exists in database but is marked as deleted
        $deletedUser = $this->entityManager->find(User::class, $userId);
        $this->assertNotNull($deletedUser);
        $this->assertNotNull($deletedUser->getDeletedAt());

        // Verify user is excluded from normal queries (depending on Gedmo config)
        $foundUser = $this->userRepository->findOneBy(['email' => 'delete@test.com']);
        $this->assertNull($foundUser); // Should be null due to soft delete filter
    }

    public function testUserWithRelationships(): void
    {
        $user = $this->createTestUser('relationships@test.com');
        $user->setNameFirst('John');
        $user->setNameLast('Doe');

        $this->entityManager->flush();

        // Test custom query methods if they exist
        $users = $this->userRepository->findBy(['firstName' => 'John']);
        $this->assertCount(1, $users);
        $this->assertSame('relationships@test.com', $users[0]->getEmail());
    }

    public function testBulkOperations(): void
    {
        $users = [];
        for ($i = 0; $i < 5; ++$i) {
            $users[] = $this->createTestUser("bulk{$i}@test.com");
        }

        $this->entityManager->flush();

        // Test bulk find
        $emails = array_map(fn ($u) => $u->getEmail(), $users);
        $qb = $this->userRepository->createQueryBuilder('u');
        $qb->where('u.email IN (:emails)')
           ->setParameter('emails', $emails);
        $foundUsers = $qb->getQuery()->getResult();

        $this->assertCount(5, $foundUsers);

        // Test bulk update
        $qb = $this->userRepository->createQueryBuilder('u');
        $qb->update()
           ->set('u.active', ':active')
           ->where('u.email LIKE :pattern')
           ->setParameter('active', false)
           ->setParameter('pattern', 'bulk%@test.com');
        $updatedCount = $qb->getQuery()->execute();

        $this->assertSame(5, $updatedCount);

        // Verify update
        $this->entityManager->clear();
        $updatedUsers = $this->userRepository->findBy(['active' => false]);
        $updatedEmails = array_map(fn ($u) => $u->getEmail(), $updatedUsers);

        foreach ($emails as $email) {
            $this->assertContains($email, $updatedEmails);
        }
    }

    public function testComplexQueries(): void
    {
        // Create users with different patterns
        $user1 = $this->createTestUser('complex1@test.com');
        $user1->setNameFirst('Alice')->setNameLast('Johnson')->setActive(true);

        $user2 = $this->createTestUser('complex2@test.com');
        $user2->setNameFirst('Bob')->setNameLast('Smith')->setActive(false);

        $user3 = $this->createTestUser('complex3@test.com');
        $user3->setNameFirst('Alice')->setNameLast('Brown')->setActive(true);

        $this->entityManager->flush();

        // Find active users named Alice
        $qb = $this->userRepository->createQueryBuilder('u');
        $qb->where('u.firstName = :firstName')
           ->andWhere('u.active = :active')
           ->setParameter('firstName', 'Alice')
           ->setParameter('active', true)
           ->orderBy('u.lastName', 'ASC');

        $aliceUsers = $qb->getQuery()->getResult();

        $this->assertCount(2, $aliceUsers);
        $this->assertSame('Brown', $aliceUsers[0]->getNameLast());
        $this->assertSame('Johnson', $aliceUsers[1]->getNameLast());
    }

    public function testTransactionRollback(): void
    {
        $this->entityManager->beginTransaction();

        try {
            $user = $this->createTestUser('transaction@test.com');
            $this->entityManager->flush();

            // Verify user exists
            $foundUser = $this->userRepository->findOneBy(['email' => 'transaction@test.com']);
            $this->assertNotNull($foundUser);

            // Rollback transaction
            $this->entityManager->rollback();

            // Clear entity manager to force fresh query
            $this->entityManager->clear();

            // Verify user no longer exists after rollback
            $foundUserAfterRollback = $this->userRepository->findOneBy(['email' => 'transaction@test.com']);
            $this->assertNull($foundUserAfterRollback);
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    public function testConcurrentAccess(): void
    {
        $user = $this->createTestUser('concurrent@test.com');
        $user->setNameFirst('Original');
        $this->entityManager->flush();

        // Simulate concurrent access by creating another entity manager
        $kernel = self::bootKernel();
        $em2 = $kernel->getContainer()->get(EntityManagerInterface::class);
        $repo2 = $em2->getRepository(User::class);

        // Load same user in both entity managers
        $user1 = $this->userRepository->findOneBy(['email' => 'concurrent@test.com']);
        $user2 = $repo2->findOneBy(['email' => 'concurrent@test.com']);

        // Modify in first EM
        $user1->setNameFirst('Modified1');
        $this->entityManager->flush();

        // Modify in second EM
        $user2->setNameFirst('Modified2');
        $em2->flush();

        // Refresh first user to see the latest state
        $this->entityManager->refresh($user1);

        // The last write should win
        $this->assertSame('Modified2', $user1->getNameFirst());
    }

    private function createTestUser(string $email, string $password = 'password123'): User
    {
        $user = new User();
        $user->setEmail($email)
            ->setNameFirst('Test')
            ->setNameLast('User')
            ->setPassword($this->passwordHasher->hashPassword($user, $password))
            ->setRoles(['ROLE_USER'])
            ->setActive(true);

        $this->entityManager->persist($user);

        return $user;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test data
        $this->entityManager->getConnection()->executeStatement('DELETE FROM user WHERE email LIKE "%@test.com"');
    }
}
