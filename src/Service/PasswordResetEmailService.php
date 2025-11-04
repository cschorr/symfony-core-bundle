<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Service;

use C3net\CoreBundle\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class PasswordResetEmailService
{
    private const string TEMPLATE_RESET_REQUEST = '@C3netCore/emails/password_reset_request.html.twig';

    private const string TEMPLATE_RESET_SUCCESS = '@C3netCore/emails/password_reset_success.html.twig';

    private const int MAX_RETRY_ATTEMPTS = 1;

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly LoggerInterface $logger,
        private readonly string $mailFrom = 'noreply@example.com',
        private readonly string $frontendUrl = 'https://atlas.c3net.io',
    ) {
    }

    /**
     * Send password reset request email with the reset link.
     */
    public function sendPasswordResetEmail(
        User $user,
        string $resetToken,
        int $tokenLifetimeMinutes,
        string $ipAddress,
        ?string $userAgent,
        string $locale = 'en',
    ): void {
        try {
            $resetUrl = sprintf('%s/update-password?token=%s', $this->frontendUrl, $resetToken);

            $this->sendEmailWithRetry(
                $user,
                self::TEMPLATE_RESET_REQUEST,
                $locale,
                [
                    'user' => $user,
                    'reset_url' => $resetUrl,
                    'token_lifetime' => $tokenLifetimeMinutes,
                    'timestamp' => new \DateTimeImmutable(),
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent ?? 'Unknown',
                    'locale' => $locale,
                ]
            );

            $this->logger->info('Password reset email sent successfully', [
                'user_id' => $user->getId()?->toString(),
                'user_email' => $user->getEmail(),
                'ip_address' => $ipAddress,
            ]);
        } catch (\Throwable $throwable) {
            // Log the error but don't block the reset request
            $this->logger->error('Failed to send password reset email', [
                'user_id' => $user->getId()?->toString(),
                'user_email' => $user->getEmail(),
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            // Re-throw for controller to handle
            throw $throwable;
        }
    }

    /**
     * Send password reset success confirmation email.
     */
    public function sendPasswordResetSuccessEmail(
        User $user,
        string $ipAddress,
        ?string $userAgent,
        string $locale = 'en',
    ): void {
        try {
            $loginUrl = sprintf('%s/login', $this->frontendUrl);

            $this->sendEmailWithRetry(
                $user,
                self::TEMPLATE_RESET_SUCCESS,
                $locale,
                [
                    'user' => $user,
                    'login_url' => $loginUrl,
                    'timestamp' => new \DateTimeImmutable(),
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent ?? 'Unknown',
                    'locale' => $locale,
                ]
            );

            $this->logger->info('Password reset success email sent successfully', [
                'user_id' => $user->getId()?->toString(),
                'user_email' => $user->getEmail(),
            ]);
        } catch (\Throwable $throwable) {
            // Log the error but don't block the password reset
            $this->logger->error('Failed to send password reset success email', [
                'user_id' => $user->getId()?->toString(),
                'user_email' => $user->getEmail(),
                'error' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            // Don't re-throw - password reset already succeeded
        }
    }

    /**
     * Send email with retry logic.
     *
     * @param array<string, mixed> $context
     */
    private function sendEmailWithRetry(
        User $user,
        string $template,
        string $locale,
        array $context,
        int $attempt = 1,
    ): void {
        try {
            $email = $this->createEmail($user, $template, $locale, $context);
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $transportException) {
            if ($attempt < self::MAX_RETRY_ATTEMPTS) {
                sleep(5); // Wait 5 seconds before retry
                $this->sendEmailWithRetry($user, $template, $locale, $context, $attempt + 1);
            } else {
                throw $transportException;
            }
        }
    }

    /**
     * Create email message.
     *
     * @param array<string, mixed> $context
     */
    private function createEmail(
        User $user,
        string $template,
        string $locale,
        array $context,
    ): Email {
        $subject = $this->getSubject($template, $locale);

        $htmlBody = $this->twig->render($template, $context);

        $userEmail = $user->getEmail();
        if (null === $userEmail || '' === $userEmail) {
            throw new \InvalidArgumentException('User email is required to send password reset email');
        }

        return (new Email())
            ->from($this->mailFrom)
            ->to($userEmail)
            ->subject($subject)
            ->html($htmlBody);
    }

    /**
     * Get email subject based on template and locale.
     */
    private function getSubject(string $template, string $locale): string
    {
        return match ($template) {
            self::TEMPLATE_RESET_REQUEST => 'de' === $locale
                ? 'Passwort zurücksetzen - Atlas'
                : 'Reset Your Password - Atlas',
            self::TEMPLATE_RESET_SUCCESS => 'de' === $locale
                ? 'Passwort erfolgreich zurückgesetzt - Atlas'
                : 'Password Reset Successful - Atlas',
            default => 'Atlas Notification',
        };
    }
}
