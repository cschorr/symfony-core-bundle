<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use C3net\CoreBundle\DTO\PasswordResetConfirm;
use C3net\CoreBundle\Service\PasswordResetEmailService;
use C3net\CoreBundle\Service\PasswordResetService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Handles password reset confirmation operations.
 *
 * @implements ProcessorInterface<PasswordResetConfirm, array<string, string>>
 */
final readonly class PasswordResetConfirmProcessor implements ProcessorInterface
{
    public function __construct(
        private PasswordResetService $passwordResetService,
        private PasswordResetEmailService $emailService,
        private LoggerInterface $logger,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @param PasswordResetConfirm $data
     *
     * @return array<string, string>
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            throw new \RuntimeException('No request available');
        }

        // Validate reset token
        $resetToken = $this->passwordResetService->validateToken($data->token);

        if (null === $resetToken) {
            $this->logger->warning('Invalid or expired password reset token used', [
                'ip_address' => $request->getClientIp(),
            ]);

            throw new BadRequestHttpException('Invalid or expired reset token. Please request a new password reset.');
        }

        try {
            $user = $resetToken->getUser();

            // Reset the password
            $this->passwordResetService->resetPassword($resetToken, $data->newPassword);

            // Send success confirmation email
            $this->emailService->sendPasswordResetSuccessEmail(
                user: $user,
                ipAddress: $request->getClientIp() ?? 'unknown',
                userAgent: $request->headers->get('User-Agent'),
                locale: $request->getLocale()
            );

            $this->logger->info('Password reset completed', [
                'user_id' => $user->getId()?->toString(),
                'user_email' => $user->getEmail(),
                'ip_address' => $request->getClientIp(),
            ]);

            return [
                'message' => 'Password reset successful. You can now log in with your new password.',
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to reset password', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new HttpException(500, 'Failed to reset password. Please try again or contact support.');
        }
    }
}
