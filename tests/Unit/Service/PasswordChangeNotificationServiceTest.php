<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Service;

use C3net\CoreBundle\DTO\PasswordChangeContext;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Service\PasswordChangeNotificationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class PasswordChangeNotificationServiceTest extends TestCase
{
    private PasswordChangeNotificationService $service;
    private MailerInterface&MockObject $mailer;
    private Environment&MockObject $twig;
    private LoggerInterface&MockObject $logger;
    private string $fromEmail;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->fromEmail = 'noreply@example.com';

        $this->service = new PasswordChangeNotificationService(
            $this->mailer,
            $this->twig,
            $this->logger,
            $this->fromEmail
        );
    }

    public function testServiceExists(): void
    {
        $this->assertTrue(class_exists(PasswordChangeNotificationService::class));
    }

    public function testSendPasswordChangedEmailSuccess(): void
    {
        $user = $this->createUser('test@example.com');
        $context = $this->createPasswordChangeContext();

        $this->twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@C3netCore/emails/password_changed.html.twig',
                $this->callback(function (array $data) use ($user, $context) {
                    return $data['user'] === $user
                        && 'en' === $data['locale']
                        && $data['timestamp'] === $context->timestamp
                        && $data['ip_address'] === $context->ipAddress
                        && $data['user_agent'] === $context->userAgent
                        && $data['changed_by_self'] === $context->changedBySelf
                        && $data['changed_by_user'] === $context->changedByUser;
                })
            )
            ->willReturn('<html>Password changed email</html>');

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) use ($user) {
                $to = $email->getTo();
                $from = $email->getFrom();

                return 1 === count($to)
                    && $to[0]->getAddress() === $user->getEmail()
                    && 1 === count($from)
                    && $from[0]->getAddress() === $this->fromEmail
                    && str_contains(strtolower($email->getSubject()), 'password')
                    && '<html>Password changed email</html>' === $email->getHtmlBody();
            }));

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Password change notification email sent successfully',
                $this->callback(function (array $logContext) {
                    return isset($logContext['user_id'])
                        && isset($logContext['user_email']);
                })
            );

        $this->service->sendPasswordChangedEmail($user, $context);
    }

    public function testSendPasswordChangedEmailUsesEnglishLocale(): void
    {
        $user = $this->createUser('test@example.com');
        $context = $this->createPasswordChangeContext();

        $this->twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@C3netCore/emails/password_changed.html.twig',
                $this->callback(function (array $data) {
                    // Service currently hardcodes 'en'
                    return 'en' === $data['locale'];
                })
            )
            ->willReturn('<html>Password changed email</html>');

        $this->mailer
            ->expects($this->once())
            ->method('send');

        $this->service->sendPasswordChangedEmail($user, $context);
    }

    public function testSendPasswordChangedEmailWithChangedByAdministrator(): void
    {
        $user = $this->createUser('user@example.com');
        $admin = $this->createUser('admin@example.com');
        $context = $this->createPasswordChangeContext(false, $admin);

        $this->twig
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $data) use ($admin) {
                    return false === $data['changed_by_self']
                        && $data['changed_by_user'] === $admin;
                })
            )
            ->willReturn('<html>Email content</html>');

        $this->mailer
            ->expects($this->once())
            ->method('send');

        $this->service->sendPasswordChangedEmail($user, $context);
    }

    public function testSendPasswordChangedEmailCatchesAndLogsFailures(): void
    {
        $user = $this->createUser('test@example.com');
        $context = $this->createPasswordChangeContext();

        $this->twig
            ->method('render')
            ->willReturn('<html>Email content</html>');

        // Send fails (MAX_RETRY_ATTEMPTS = 1 means no actual retries, just one attempt)
        $exception = new TransportException('Connection failed');
        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->willThrowException($exception);

        // Error should be logged, not rethrown
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Failed to send password change notification email',
                $this->callback(function (array $logContext) {
                    return isset($logContext['user_id'])
                        && isset($logContext['user_email'])
                        && isset($logContext['error']);
                })
            );

        // Should not throw exception - just log it
        $this->service->sendPasswordChangedEmail($user, $context);
    }

    public function testSendPasswordChangedEmailIncludesSecurityDetails(): void
    {
        $user = $this->createUser('test@example.com');
        $timestamp = new \DateTimeImmutable('2024-01-15 10:30:00');
        $context = new PasswordChangeContext(
            ipAddress: '192.168.1.100',
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            timestamp: $timestamp,
            changedBySelf: true
        );

        $this->twig
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $data) use ($timestamp) {
                    return '192.168.1.100' === $data['ip_address']
                        && 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)' === $data['user_agent']
                        && $data['timestamp'] === $timestamp
                        && true === $data['changed_by_self'];
                })
            )
            ->willReturn('<html>Email with security details</html>');

        $this->mailer
            ->expects($this->once())
            ->method('send');

        $this->service->sendPasswordChangedEmail($user, $context);
    }

    public function testServiceConstants(): void
    {
        $reflection = new \ReflectionClass(PasswordChangeNotificationService::class);

        $this->assertTrue($reflection->hasConstant('EMAIL_TEMPLATE'));
        $this->assertTrue($reflection->hasConstant('MAX_RETRY_ATTEMPTS'));

        $this->assertSame('@C3netCore/emails/password_changed.html.twig', $reflection->getConstant('EMAIL_TEMPLATE'));
        $this->assertSame(1, $reflection->getConstant('MAX_RETRY_ATTEMPTS'));
    }

    public function testSendPasswordChangedEmailMethodExists(): void
    {
        $reflection = new \ReflectionClass(PasswordChangeNotificationService::class);

        $this->assertTrue($reflection->hasMethod('sendPasswordChangedEmail'));

        $method = $reflection->getMethod('sendPasswordChangedEmail');
        $this->assertTrue($method->isPublic());
        $this->assertCount(2, $method->getParameters());
    }

    public function testSendPasswordChangedEmailMethodSignature(): void
    {
        $reflection = new \ReflectionClass(PasswordChangeNotificationService::class);
        $method = $reflection->getMethod('sendPasswordChangedEmail');

        $parameters = $method->getParameters();

        $this->assertSame('user', $parameters[0]->getName());
        $this->assertSame('context', $parameters[1]->getName());
        $this->assertSame('void', $method->getReturnType()?->getName());
    }

    public function testEmailFromAddressIsConfigurable(): void
    {
        $customFromEmail = 'custom@example.com';
        $service = new PasswordChangeNotificationService(
            $this->mailer,
            $this->twig,
            $this->logger,
            $customFromEmail
        );

        $user = $this->createUser('test@example.com');
        $context = $this->createPasswordChangeContext();

        $this->twig
            ->method('render')
            ->willReturn('<html>Email content</html>');

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) use ($customFromEmail) {
                $from = $email->getFrom();

                return $from[0]->getAddress() === $customFromEmail;
            }));

        $service->sendPasswordChangedEmail($user, $context);
    }

    public function testEmailSubjectContainsPasswordKeyword(): void
    {
        $user = $this->createUser('test@example.com');
        $context = $this->createPasswordChangeContext();

        $this->twig
            ->method('render')
            ->willReturn('<html>Email content</html>');

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) {
                $subject = strtolower($email->getSubject());

                return str_contains($subject, 'password');
            }));

        $this->service->sendPasswordChangedEmail($user, $context);
    }

    public function testEmailHasHtmlContent(): void
    {
        $user = $this->createUser('test@example.com');
        $context = $this->createPasswordChangeContext();

        $htmlContent = '<html><body><h1>Your password was changed</h1></body></html>';

        $this->twig
            ->method('render')
            ->willReturn($htmlContent);

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email) use ($htmlContent) {
                return $email->getHtmlBody() === $htmlContent;
            }));

        $this->service->sendPasswordChangedEmail($user, $context);
    }

    private function createUser(string $email): User
    {
        $user = new User();
        $user->setEmail($email);

        // Use reflection to set the ID - search through class hierarchy
        $reflection = new \ReflectionClass($user);
        $property = null;
        while ($reflection && !$property) {
            try {
                $property = $reflection->getProperty('id');
            } catch (\ReflectionException $e) {
                $reflection = $reflection->getParentClass();
            }
        }

        if ($property) {
            $property->setAccessible(true);
            $property->setValue($user, \Symfony\Component\Uid\Uuid::v7());
        }

        return $user;
    }

    private function createPasswordChangeContext(bool $changedBySelf = true, ?User $changedByUser = null): PasswordChangeContext
    {
        return new PasswordChangeContext(
            ipAddress: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
            timestamp: new \DateTimeImmutable(),
            changedBySelf: $changedBySelf,
            changedByUser: $changedByUser
        );
    }
}
