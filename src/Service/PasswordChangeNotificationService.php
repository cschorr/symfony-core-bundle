<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Service;

use C3net\CoreBundle\DTO\PasswordChangeContext;
use C3net\CoreBundle\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class PasswordChangeNotificationService
{
    private const string EMAIL_TEMPLATE = '@C3netCore/emails/password_changed.html.twig';
    private const int MAX_RETRY_ATTEMPTS = 1;

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly LoggerInterface $logger,
        private readonly string $mailFrom = 'noreply@example.com',
    ) {
    }

    /**
     * Send password change notification email to the user.
     */
    public function sendPasswordChangedEmail(User $user, PasswordChangeContext $context): void
    {
        try {
            $this->sendEmailWithRetry($user, $context);
        } catch (\Throwable $e) {
            // Log the error but don't block the password change
            $this->logger->error('Failed to send password change notification email', [
                'user_id' => $user->getId()?->toString(),
                'user_email' => $user->getEmail(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function sendEmailWithRetry(User $user, PasswordChangeContext $context, int $attempt = 1): void
    {
        try {
            $email = $this->createEmail($user, $context);
            $this->mailer->send($email);

            $this->logger->info('Password change notification email sent successfully', [
                'user_id' => $user->getId()?->toString(),
                'user_email' => $user->getEmail(),
            ]);
        } catch (TransportExceptionInterface $e) {
            if ($attempt < self::MAX_RETRY_ATTEMPTS) {
                sleep(5); // Wait 5 seconds before retry
                $this->sendEmailWithRetry($user, $context, $attempt + 1);
            } else {
                throw $e;
            }
        }
    }

    private function createEmail(User $user, PasswordChangeContext $context): Email
    {
        // Determine user's locale preference (default to English)
        $locale = 'en'; // TODO: Get from user preferences when implemented

        $subject = 'en' === $locale
            ? 'Your password was changed'
            : 'Ihr Passwort wurde geändert';

        $body = $this->twig->render(self::EMAIL_TEMPLATE, [
            'user' => $user,
            'locale' => $locale,
            'timestamp' => $context->timestamp,
            'ip_address' => $context->ipAddress,
            'user_agent' => $context->userAgent,
            'changed_by_self' => $context->changedBySelf,
            'changed_by_user' => $context->changedByUser,
        ]);

        return (new Email())
            ->from($this->mailFrom)
            ->to((string) $user->getEmail())
            ->subject($subject)
            ->html($body);
    }
}
