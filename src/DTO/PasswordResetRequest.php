<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DTO;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use C3net\CoreBundle\State\PasswordResetRequestProcessor;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for requesting a password reset.
 */
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/password-reset/request',
            status: 200,
            processor: PasswordResetRequestProcessor::class,
            openapi: new Operation(
                tags: ['Password Reset'],
                summary: 'Request a password reset',
                description: 'Initiates a password reset by sending a reset link to the provided email address. Always returns success to prevent user enumeration.'
            )
        ),
    ]
)]
final readonly class PasswordResetRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email is required.')]
        #[Assert\Email(message: 'Invalid email format.')]
        public string $email,
    ) {
    }
}
