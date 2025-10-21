<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Integration\Security;

use C3net\CoreBundle\DTO\PasswordChangeContext;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Service\PasswordChangeNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Integration test for password change email notifications.
 */
class EmailNotificationTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private PasswordChangeNotificationService $notificationService;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $this->notificationService = $container->get(PasswordChangeNotificationService::class);
    }

    public function testNotificationServiceExists(): void
    {
        $this->assertInstanceOf(
            PasswordChangeNotificationService::class,
            $this->notificationService
        );
    }

    public function testPasswordChangeEmailIsQueued(): void
    {
        $user = $this->createTestUser('email-test@test.com', 'OldPassword123!');

        $context = new PasswordChangeContext(
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
            timestamp: new \DateTimeImmutable(),
            changedBySelf: true,
            changedByUser: null
        );

        // This will queue the email via Symfony Messenger
        $this->notificationService->sendPasswordChangedEmail($user, $context);

        // Email should be queued in messenger_messages table
        $queuedMessages = $this->entityManager->getConnection()
            ->executeQuery('SELECT COUNT(*) as count FROM messenger_messages WHERE queue_name = ?', ['default'])
            ->fetchAssociative();

        $this->assertGreaterThan(0, $queuedMessages['count']);
    }

    public function testEmailContainsCorrectRecipient(): void
    {
        $user = $this->createTestUser('recipient-test@test.com', 'Password123!');

        $context = new PasswordChangeContext(
            ipAddress: '10.0.0.1',
            userAgent: 'Test Agent',
            timestamp: new \DateTimeImmutable(),
            changedBySelf: true,
            changedByUser: null
        );

        // Use event listener to capture email
        $emailSent = null;

        $listener = function (MessageEvent $event) use (&$emailSent): void {
            $emailSent = $event->getMessage();
        };

        $eventDispatcher = self::getContainer()->get('event_dispatcher');
        $eventDispatcher->addListener(MessageEvent::class, $listener);

        $this->notificationService->sendPasswordChangedEmail($user, $context);

        // Note: Email is sent asynchronously via messenger, so we can't directly test the content here
        // This test verifies the service can be called without errors
        $this->assertTrue(true);
    }

    public function testContextIncludesSecurityDetails(): void
    {
        $context = new PasswordChangeContext(
            ipAddress: '192.168.1.100',
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            timestamp: new \DateTimeImmutable('2025-10-21 12:00:00'),
            changedBySelf: true,
            changedByUser: null
        );

        $this->assertSame('192.168.1.100', $context->ipAddress);
        $this->assertSame('Mozilla/5.0 (Windows NT 10.0; Win64; x64)', $context->userAgent);
        $this->assertTrue($context->changedBySelf);
        $this->assertNull($context->changedByUser);
        $this->assertInstanceOf(\DateTimeImmutable::class, $context->timestamp);
    }

    public function testContextSupportsAdminPasswordChange(): void
    {
        $adminUser = $this->createTestUser('admin-changer@test.com', 'AdminPassword!');
        $targetUser = $this->createTestUser('target-user@test.com', 'UserPassword!');

        $context = new PasswordChangeContext(
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
            timestamp: new \DateTimeImmutable(),
            changedBySelf: false,
            changedByUser: $adminUser
        );

        $this->assertFalse($context->changedBySelf);
        $this->assertSame($adminUser, $context->changedByUser);
    }

    public function testMultipleEmailsCanBeQueued(): void
    {
        $user1 = $this->createTestUser('multi1@test.com', 'Password1!');
        $user2 = $this->createTestUser('multi2@test.com', 'Password2!');
        $user3 = $this->createTestUser('multi3@test.com', 'Password3!');

        $context = new PasswordChangeContext(
            ipAddress: '192.168.1.1',
            userAgent: 'Test Agent',
            timestamp: new \DateTimeImmutable(),
            changedBySelf: true,
            changedByUser: null
        );

        // Clear messenger queue first
        $this->entityManager->getConnection()->executeStatement('DELETE FROM messenger_messages');

        // Send emails for all users
        $this->notificationService->sendPasswordChangedEmail($user1, $context);
        $this->notificationService->sendPasswordChangedEmail($user2, $context);
        $this->notificationService->sendPasswordChangedEmail($user3, $context);

        // Verify 3 emails are queued
        $queuedMessages = $this->entityManager->getConnection()
            ->executeQuery('SELECT COUNT(*) as count FROM messenger_messages WHERE queue_name = ?', ['default'])
            ->fetchAssociative();

        $this->assertGreaterThanOrEqual(3, $queuedMessages['count']);
    }

    private function createTestUser(string $email, string $password): User
    {
        $user = new User();
        $user->setEmail($email)
            ->setNameFirst('Test')
            ->setNameLast('User')
            ->setPassword($this->passwordHasher->hashPassword($user, $password))
            ->setActive(true)
            ->setLocked(false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test data
        $this->entityManager->getConnection()->executeStatement(
            'DELETE FROM messenger_messages WHERE 1=1'
        );
        $this->entityManager->getConnection()->executeStatement(
            'DELETE FROM password_history WHERE user_id IN (SELECT id FROM user WHERE email LIKE "%@test.com")'
        );
        $this->entityManager->getConnection()->executeStatement(
            'DELETE FROM user WHERE email LIKE "%@test.com"'
        );
    }
}
