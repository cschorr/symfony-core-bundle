<?php

declare(strict_types=1);

namespace C3net\CoreBundle\EventListener;

use C3net\CoreBundle\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_authenticated')]
class JwtAuthenticatedListener
{
    public function __invoke(JWTAuthenticatedEvent $event): void
    {
        $payload = $event->getPayload();
        $token = $event->getToken();
        $user = $token->getUser();

        if (!$user instanceof User) {
            return;
        }

        // Get password_changed_at timestamp from JWT claim
        $jwtPasswordChangedAt = $payload['password_changed_at'] ?? 0;

        // Get password_changed_at timestamp from User entity
        $userPasswordChangedAt = $user->getPasswordChangedAt()
            ?->getTimestamp() ?? 0;

        // If password was changed after JWT was created, reject the token
        if ($userPasswordChangedAt > $jwtPasswordChangedAt) {
            throw new InvalidTokenException('Token invalidated due to password change. Please login again.');
        }
    }
}
