<?php

declare(strict_types=1);

namespace C3net\CoreBundle\EventListener;

use C3net\CoreBundle\DTO\PasswordChangeContext;
use C3net\CoreBundle\Entity\RefreshToken;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Service\PasswordChangeAuditService;
use C3net\CoreBundle\Service\PasswordChangeNotificationService;
use C3net\CoreBundle\Service\PasswordHistoryService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postPersist)]
class PasswordChangedEventListener
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PasswordHistoryService $passwordHistoryService,
        private readonly PasswordChangeNotificationService $notificationService,
        private readonly PasswordChangeAuditService $auditService,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function postUpdate(PostUpdateEventArgs $event): void
    {
        $entity = $event->getObject();

        if (!$entity instanceof User) {
            return;
        }

        // Check if password was actually changed
        $changeSet = $event->getObjectManager()
            ->getUnitOfWork()
            ->getEntityChangeSet($entity);

        if (!isset($changeSet['password'])) {
            return; // Password not changed
        }

        $passwordChange = $changeSet['password'];
        if (!\is_array($passwordChange) || 2 !== \count($passwordChange)) {
            return; // Invalid password change set
        }

        $oldHash = $passwordChange[0];
        $newHash = $passwordChange[1];

        if ($oldHash === $newHash) {
            return; // Password unchanged
        }

        $this->handlePasswordChange($entity, $newHash);
    }

    public function postPersist(PostPersistEventArgs $event): void
    {
        $entity = $event->getObject();

        if (!$entity instanceof User) {
            return;
        }

        // For new users, we don't trigger password change logic
        // (no need to invalidate tokens or send notifications)
    }

    private function handlePasswordChange(User $user, string $newPasswordHash): void
    {
        $this->logger->info('PasswordChangedEventListener::handlePasswordChange - Starting', [
            'user_id' => $user->getId()?->toString(),
            'user_email' => $user->getEmail(),
        ]);

        // 1. Set passwordChangedAt timestamp
        $user->setPasswordChangedAt(new \DateTimeImmutable());
        $this->logger->debug('Step 1: Set passwordChangedAt timestamp');

        // 2. Invalidate all refresh tokens
        $this->invalidateAllRefreshTokens($user);
        $this->logger->debug('Step 2: Invalidated refresh tokens');

        // 3. Clear password reset token if exists
        if (null !== $user->getPasswordResetToken()) {
            $user->setPasswordResetToken(null);
            $user->setPasswordResetTokenExpiresAt(null);
            $this->logger->debug('Step 3: Cleared password reset token');
        }

        // 4. Store password in history
        $this->passwordHistoryService->storePasswordHash($user, $newPasswordHash);
        $this->logger->debug('Step 4: Stored password in history');

        // 5. Create password change context
        $context = $this->createPasswordChangeContext($user);
        $this->logger->debug('Step 5: Created password change context', [
            'ip_address' => $context->ipAddress,
            'user_agent' => $context->userAgent,
        ]);

        // 6. Send email notification
        $this->logger->info('Step 6: About to send email notification', [
            'user_email' => $user->getEmail(),
        ]);
        $this->notificationService->sendPasswordChangedEmail($user, $context);
        $this->logger->info('Step 6: Email notification sent (or attempted)');

        // 7. Create audit log
        $this->logger->debug('Step 7: About to create audit log');
        $this->auditService->logPasswordChange($user, $context, true);
        $this->logger->debug('Step 7: Audit log created');

        $this->logger->info('PasswordChangedEventListener::handlePasswordChange - Completed');

        // Note: No flush() needed here - we're inside a postUpdate event,
        // so all entity changes will be persisted automatically by the
        // ongoing flush operation that triggered this event.
    }

    private function invalidateAllRefreshTokens(User $user): void
    {
        $refreshTokens = $this->entityManager
            ->getRepository(RefreshToken::class)
            ->findBy(['username' => $user->getUserIdentifier()]);

        foreach ($refreshTokens as $token) {
            $this->entityManager->remove($token);
        }
    }

    private function createPasswordChangeContext(User $user): PasswordChangeContext
    {
        $request = $this->requestStack->getCurrentRequest();

        $ipAddress = $request?->getClientIp() ?? 'unknown';
        $userAgent = $request?->headers->get('User-Agent') ?? 'unknown';

        // TODO: Determine if changed by self or admin
        // For now, assume changed by self
        $changedBySelf = true;
        $changedByUser = null;

        return new PasswordChangeContext(
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            timestamp: new \DateTimeImmutable(),
            changedBySelf: $changedBySelf,
            changedByUser: $changedByUser,
        );
    }
}
