<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Service;

use C3net\CoreBundle\DTO\PasswordChangeContext;
use C3net\CoreBundle\Entity\User;
use Psr\Log\LoggerInterface;

class PasswordChangeAuditService
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Log password change event with full security context.
     */
    public function logPasswordChange(
        User $user,
        PasswordChangeContext $context,
        bool $success = true,
        ?string $failureReason = null,
    ): void {
        $logData = [
            'event' => 'password_changed',
            'user_id' => $user->getId()?->toString(),
            'user_email' => $user->getEmail(),
            'ip_address' => $context->ipAddress,
            'user_agent' => $context->userAgent,
            'timestamp' => $context->timestamp->format(\DateTimeInterface::ATOM),
            'changed_by_self' => $context->changedBySelf,
            'changed_by_user_id' => $context->changedByUser?->getId()?->toString(),
            'changed_by_user_email' => $context->changedByUser?->getEmail(),
            'success' => $success,
        ];

        if (null !== $failureReason) {
            $logData['failure_reason'] = $failureReason;
        }

        if ($success) {
            $this->logger->info('Password changed successfully', $logData);
        } else {
            $this->logger->warning('Password change failed', $logData);
        }
    }
}
