<?php

declare(strict_types=1);

namespace C3net\CoreBundle\EventListener;

use C3net\CoreBundle\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: 'lexik_jwt_authentication.on_jwt_created')]
class JwtCreatedListener
{
    public function __invoke(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $payload = $event->getData();

        // Add password_changed_at claim to JWT payload
        // This timestamp is used to invalidate tokens created before password change
        $payload['password_changed_at'] = $user->getPasswordChangedAt()
            ?->getTimestamp() ?? 0;

        $event->setData($payload);
    }
}
