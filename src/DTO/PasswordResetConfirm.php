<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DTO;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use C3net\CoreBundle\State\PasswordResetConfirmProcessor;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for confirming a password reset with a token.
 */
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/password-reset/confirm',
            status: 200,
            processor: PasswordResetConfirmProcessor::class,
            openapi: new Operation(
                tags: ['Password Reset'],
                summary: 'Confirm password reset with token',
                description: 'Validates the reset token and sets a new password for the user.'
            )
        ),
    ]
)]
final readonly class PasswordResetConfirm
{
    private const int MIN_PASSWORD_LENGTH = 12;

    public function __construct(
        #[Assert\NotBlank(message: 'Reset token is required.')]
        public string $token,
        #[Assert\NotBlank(message: 'New password is required.')]
        #[Assert\Length(min: self::MIN_PASSWORD_LENGTH, minMessage: 'Password must be at least {{ limit }} characters long.')]
        #[Assert\Regex(
            pattern: '/[A-Z]/',
            message: 'Password must contain at least one uppercase letter.'
        )]
        #[Assert\Regex(
            pattern: '/[a-z]/',
            message: 'Password must contain at least one lowercase letter.'
        )]
        #[Assert\Regex(
            pattern: '/[0-9]/',
            message: 'Password must contain at least one number.'
        )]
        #[Assert\Regex(
            pattern: '/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/',
            message: 'Password must contain at least one special character.'
        )]
        public string $newPassword,
    ) {
    }
}
