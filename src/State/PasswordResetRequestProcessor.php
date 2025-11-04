<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use C3net\CoreBundle\DTO\PasswordResetRequest;
use C3net\CoreBundle\Service\PasswordResetEmailService;
use C3net\CoreBundle\Service\PasswordResetService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * Handles password reset request operations.
 *
 * @implements ProcessorInterface<PasswordResetRequest, array<string, string>>
 */
final readonly class PasswordResetRequestProcessor implements ProcessorInterface
{
    public function __construct(
        private PasswordResetService $passwordResetService,
        private PasswordResetEmailService $emailService,
        private LoggerInterface $logger,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @param PasswordResetRequest $data
     *
     * @return array<string, string>
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            throw new \RuntimeException('No request available');
        }

        // Check rate limiting
        if (!$this->passwordResetService->canRequestReset($data->email)) {
            throw new TooManyRequestsHttpException(null, 'Too many password reset requests. Please try again later.');
        }

        // Find user by email
        $user = $this->passwordResetService->findUserByEmail($data->email);

        // Always return success to prevent user enumeration
        // But only send email if user exists
        if (null !== $user) {
            try {
                // Create reset token
                $token = $this->passwordResetService->createResetToken($user, $request);

                // Send reset email
                $this->emailService->sendPasswordResetEmail(
                    user: $user,
                    resetToken: $token,
                    tokenLifetimeMinutes: $this->passwordResetService->getTokenLifetimeMinutes(),
                    ipAddress: $request->getClientIp() ?? 'unknown',
                    userAgent: $request->headers->get('User-Agent'),
                    locale: $request->getLocale()
                );

                $this->logger->info('Password reset requested', [
                    'user_id' => $user->getId()?->toString(),
                    'user_email' => $data->email,
                    'ip_address' => $request->getClientIp(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to process password reset request', [
                    'email' => $data->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Return generic success to avoid revealing if user exists
                // The error is logged for monitoring
            }
        } else {
            $this->logger->info('Password reset requested for non-existent email', [
                'email' => $data->email,
                'ip_address' => $request->getClientIp(),
            ]);
        }

        // Always return success message (security best practice)
        return [
            'message' => 'If this email exists in our system, you will receive password reset instructions shortly.',
        ];
    }
}
